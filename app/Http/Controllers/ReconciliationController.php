<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\RoleStatus;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Services\Settings\SettingsServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReconciliationController extends Controller
{
    public function __construct(private readonly SettingsServiceInterface $settings)
    {
        $this->middleware(function ($request, $next) {
            if (!Auth::check() || !in_array(Auth::user()->role, [RoleStatus::ADMIN->value, RoleStatus::CASHIER->value], true)) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
            }
            return $next($request);
        });
    }

    public function index(): Response
    {
        return Inertia::render('Reconciliation/Index', [
            'currency' => $this->settings->currency(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $date = $request->input('date', now()->toDateString());

        // Paid transactions by payment method
        $cashPaid = Transaction::where('status', TransactionStatus::PAID)
            ->where('payment_method', PaymentMethod::CASH)
            ->whereDate('updated_at', $date)
            ->sum('total');

        $qrisPaid = Transaction::where('status', TransactionStatus::PAID)
            ->where('payment_method', PaymentMethod::QRIS)
            ->whereDate('updated_at', $date)
            ->sum('total');

        // Pending QRIS count
        $pendingQris = Transaction::where('status', TransactionStatus::PENDING)
            ->where('payment_method', PaymentMethod::QRIS)
            ->whereDate('created_at', $date)
            ->count();

        // Canceled QRIS count
        $canceledQris = Transaction::where('status', TransactionStatus::CANCELED)
            ->where('payment_method', PaymentMethod::QRIS)
            ->whereDate('updated_at', $date)
            ->count();

        // Transaction counts
        $cashCount = Transaction::where('status', TransactionStatus::PAID)
            ->where('payment_method', PaymentMethod::CASH)
            ->whereDate('updated_at', $date)
            ->count();

        $qrisCount = Transaction::where('status', TransactionStatus::PAID)
            ->where('payment_method', PaymentMethod::QRIS)
            ->whereDate('updated_at', $date)
            ->count();

        // Pending QRIS list (for follow-up)
        $pendingList = Transaction::where('status', TransactionStatus::PENDING)
            ->where('payment_method', PaymentMethod::QRIS)
            ->whereDate('created_at', $date)
            ->with('user:id,name')
            ->orderBy('created_at', 'asc')
            ->get(['id', 'invoice_number', 'total', 'created_at', 'user_id'])
            ->map(fn($t) => [
                'id' => $t->id,
                'invoice' => $t->invoice_number,
                'total' => (float) $t->total,
                'created_at' => $t->created_at->toIso8601String(),
                'cashier' => $t->user?->name ?? '-',
                'age_minutes' => (int) $t->created_at->diffInMinutes(now()),
            ]);

        return response()->json([
            'date' => $date,
            'summary' => [
                'cash_total' => (float) $cashPaid,
                'qris_total' => (float) $qrisPaid,
                'grand_total' => (float) ($cashPaid + $qrisPaid),
                'cash_count' => $cashCount,
                'qris_count' => $qrisCount,
                'pending_qris_count' => $pendingQris,
                'canceled_qris_count' => $canceledQris,
            ],
            'pending_transactions' => $pendingList,
        ]);
    }
}
