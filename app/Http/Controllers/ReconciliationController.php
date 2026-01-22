<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\RoleStatus;
use App\Enums\TransactionStatus;
use App\Models\Shift;
use App\Models\Transaction;
use App\Services\Settings\SettingsServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    /**
     * Get available shifts for the date selector.
     */
    public function shifts(Request $request): JsonResponse
    {
        $date = $request->input('date', now()->toDateString());
        $isAdmin = Auth::user()->role === RoleStatus::ADMIN->value;

        $shifts = Shift::query()
            ->with('user:id,name')
            ->whereDate('started_at', $date)
            ->when(!$isAdmin, fn($q) => $q->where('user_id', Auth::id()))
            ->orderBy('started_at', 'desc')
            ->get()
            ->map(fn(Shift $s) => [
                'id' => $s->id,
                'user_name' => $s->user?->name ?? '-',
                'started_at' => $s->started_at->format('H:i'),
                'ended_at' => $s->ended_at?->format('H:i'),
                'is_active' => $s->isActive(),
                'total_sales' => $s->total_sales,
            ]);

        // Find active shift for current user (default selection)
        $activeShift = Shift::getActiveForUser(Auth::id());

        return response()->json([
            'date' => $date,
            'shifts' => $shifts,
            'active_shift_id' => $activeShift?->id,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $date = $request->input('date', now()->toDateString());
        $shiftId = $request->input('shift_id'); // null = all shifts for date

        // Base query builder for filtering
        $baseQuery = fn() => Transaction::query()
            ->when($shiftId, fn($q) => $q->where('shift_id', $shiftId))
            ->when(!$shiftId, fn($q) => $q->whereDate('updated_at', $date));

        // Paid transactions by payment method
        $cashPaid = (clone $baseQuery())
            ->where('status', TransactionStatus::PAID)
            ->where('payment_method', PaymentMethod::CASH)
            ->sum('total');

        $qrisPaid = (clone $baseQuery())
            ->where('status', TransactionStatus::PAID)
            ->where('payment_method', PaymentMethod::QRIS)
            ->sum('total');

        // Transaction counts
        $cashCount = (clone $baseQuery())
            ->where('status', TransactionStatus::PAID)
            ->where('payment_method', PaymentMethod::CASH)
            ->count();

        $qrisCount = (clone $baseQuery())
            ->where('status', TransactionStatus::PAID)
            ->where('payment_method', PaymentMethod::QRIS)
            ->count();

        // Pending QRIS (use created_at for pending)
        $pendingQuery = Transaction::query()
            ->where('status', TransactionStatus::PENDING)
            ->where('payment_method', PaymentMethod::QRIS)
            ->when($shiftId, fn($q) => $q->where('shift_id', $shiftId))
            ->when(!$shiftId, fn($q) => $q->whereDate('created_at', $date));

        $pendingQris = $pendingQuery->count();

        // Canceled QRIS
        $canceledQris = (clone $baseQuery())
            ->where('status', TransactionStatus::CANCELED)
            ->where('payment_method', PaymentMethod::QRIS)
            ->count();

        // Pending QRIS list (for follow-up)
        $pendingList = (clone $pendingQuery)
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

        // Shift details if specific shift selected
        $shiftInfo = null;
        if ($shiftId) {
            $shift = Shift::with('user:id,name')->find($shiftId);
            if ($shift) {
                $shiftInfo = [
                    'id' => $shift->id,
                    'user_name' => $shift->user?->name ?? '-',
                    'started_at' => $shift->started_at->toIso8601String(),
                    'ended_at' => $shift->ended_at?->toIso8601String(),
                    'opening_cash' => (float) $shift->opening_cash,
                    'closing_cash' => $shift->closing_cash !== null ? (float) $shift->closing_cash : null,
                    'expected_cash' => $shift->expected_cash,
                    'variance' => $shift->variance,
                ];
            }
        }

        return response()->json([
            'date' => $date,
            'shift_id' => $shiftId ? (int) $shiftId : null,
            'shift' => $shiftInfo,
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

