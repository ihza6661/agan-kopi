<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Models\Payment;
use App\Models\Transaction;
use App\Services\Settings\SettingsServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(private readonly SettingsServiceInterface $settings) {}

    public function index(Request $request): Response
    {
        if (!Auth::check() || Auth::user()->role !== \App\Enums\RoleStatus::ADMIN->value) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        return Inertia::render('Payments/Index', [
            'currency' => $this->settings->currency(),
            'statuses' => array_map(fn($s) => ['value' => $s->value], PaymentStatus::cases()),
            'methods' => array_map(fn($m) => ['value' => $m->value], PaymentMethod::cases()),
            'filters' => [
                'q' => trim((string) $request->query('q', '')),
                'status' => $request->query('status', ''),
                'method' => $request->query('method', ''),
                'provider' => $request->query('provider', ''),
                'from' => $request->query('from', ''),
                'to' => $request->query('to', ''),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        if (!Auth::check() || Auth::user()->role !== \App\Enums\RoleStatus::ADMIN->value) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        $q = trim((string) $request->input('q', ''));
        $status = $request->input('status');
        $method = $request->input('method');
        $provider = $request->input('provider');
        $from = $request->input('from');
        $to = $request->input('to');
        $perPage = max(1, min(50, (int) $request->input('per_page', 15)));

        $query = Payment::query()
            ->with(['transaction.user'])
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($qq) use ($q) {
                    $qq->where('provider_order_id', 'like', "%{$q}%")
                        ->orWhereHas('transaction', function ($tt) use ($q) {
                            $tt->where('invoice_number', 'like', "%{$q}%");
                        });
                });
            })
            ->when($status && in_array($status, array_column(PaymentStatus::cases(), 'value'), true), function ($w) use ($status) {
                $w->where('status', $status);
            })
            ->when($method && in_array($method, array_column(PaymentMethod::cases(), 'value'), true), function ($w) use ($method) {
                $w->where('method', $method);
            })
            ->when($provider, function ($w) use ($provider) {
                $w->where('provider', $provider);
            })
            ->when($from, function ($w) use ($from) {
                $w->whereDate('created_at', '>=', $from);
            })
            ->when($to, function ($w) use ($to) {
                $w->whereDate('created_at', '<=', $to);
            })
            ->orderByDesc('created_at')
            ->select(['id', 'transaction_id', 'method', 'provider', 'provider_order_id', 'status', 'amount', 'paid_at', 'created_at']);

        $paginated = $query->paginate($perPage);

        $data = $paginated->getCollection()->map(function (Payment $p) {
            $trx = $p->transaction;
            $isQris = $trx && strtolower((string)($trx->payment_method?->value ?? $trx->payment_method ?? '')) === 'qris';
            $status = strtolower((string)($p->status->value ?? $p->status ?? ''));

            return [
                'id' => $p->id,
                'invoice' => $trx?->invoice_number ?? ('#' . $p->transaction_id),
                'transaction_id' => $p->transaction_id,
                'cashier' => $trx?->user?->name ?? '-',
                'method' => is_string($p->method) ? $p->method : ($p->method?->value ?? ''),
                'provider' => $p->provider ?? '-',
                'status' => $status,
                'amount' => (float) $p->amount,
                'created_at' => $p->created_at?->toIso8601String(),
                'paid_at' => $p->paid_at?->toIso8601String(),
                'is_qris_pending' => $isQris && $status === 'pending',
            ];
        });

        return response()->json([
            'data' => $data,
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
        ]);
    }

    public function show(Transaction $transaction): Response
    {
        $method = strtolower((string) ($transaction->payment_method?->value ?? $transaction->payment_method ?? ''));
        if ($method !== 'qris') {
            abort(404);
        }

        $payment = Payment::where('transaction_id', $transaction->id)->latest()->firstOrFail();
        $status = strtolower((string) ($payment->status?->value ?? $payment->status ?? 'pending'));
        if ($status !== 'pending') {
            abort(404);
        }

        $transaction->loadMissing('user:id,name');

        return Inertia::render('Payments/Show', [
            'transaction' => [
                'id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'payment_method' => is_string($transaction->payment_method)
                    ? $transaction->payment_method
                    : ($transaction->payment_method?->value ?? ''),
                'total' => (float) $transaction->total,
                'user' => $transaction->user ? [
                    'id' => $transaction->user->id,
                    'name' => $transaction->user->name,
                ] : null,
            ],
            'payment' => [
                'id' => $payment->id,
                'method' => is_string($payment->method) ? $payment->method : ($payment->method?->value ?? ''),
                'provider' => $payment->provider,
                'status' => $status,
                'amount' => (float) $payment->amount,
                'created_at' => $payment->created_at?->toIso8601String(),
                'paid_at' => $payment->paid_at?->toIso8601String(),
                'snap_token' => $payment->metadata['snap_token'] ?? null,
                'qr_url' => $payment->qr_url ?? null,
                'qr_string' => $payment->qr_string ?? null,
            ],
            'currency' => $this->settings->currency(),
            'midtrans_client_key' => config('midtrans.client_key'),
            'midtrans_is_production' => config('midtrans.is_production'),
        ]);
    }

    public function status(Transaction $transaction): JsonResponse
    {
        $transaction->loadMissing('latestPayment');
        $pay = $transaction->latestPayment;

        $status = $pay?->status?->value
            ?? $transaction->status?->value
            ?? 'pending';

        return response()->json([
            'transaction_id' => $transaction->id,
            'invoice' => $transaction->invoice_number,
            'status' => $status,
            'paid' => in_array($status, [
                PaymentStatus::SETTLEMENT->value,
                TransactionStatus::PAID->value,
            ], true),
        ]);
    }

    public function complete(Transaction $transaction)
    {
        if ($transaction->suspended_from_id) {
            $orig = Transaction::where('id', $transaction->suspended_from_id)
                ->where('status', TransactionStatus::SUSPENDED)
                ->first();
            if ($orig) {
                $orig->delete();
            }
        }

        $method = strtolower((string) ($transaction->payment_method?->value ?? $transaction->payment_method ?? ''));
        return redirect()->route('kasir')
            ->with('success', 'Transaksi berhasil. Nomor: ' . $transaction->invoice_number)
            ->with('printed_transaction_id', $transaction->id)
            ->with('printed_invoice', $transaction->invoice_number)
            ->with('printed_payment_method', $method);
    }
}
