<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        if (!Auth::check() || Auth::user()->role !== \App\Enums\RoleStatus::ADMIN->value) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        return view('payments.index', [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status'),
            'method' => $request->query('method'),
            'provider' => $request->query('provider'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'statuses' => PaymentStatus::cases(),
            'methods' => PaymentMethod::cases(),
        ]);
    }

    public function data(Request $request)
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

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('invoice', function (Payment $p) {
                $inv = $p->transaction?->invoice_number ?? ('#' . $p->transaction_id);
                $url = route('transaksi.show', $p->transaction_id);
                return '<a href="' . e($url) . '">' . e($inv) . '</a>';
            })
            ->addColumn('cashier', function (Payment $p) {
                return e($p->transaction?->user?->name ?? '-');
            })
            ->addColumn('method_text', function (Payment $p) {
                $m = is_string($p->method) ? $p->method : ($p->method?->value ?? '');
                return strtoupper($m);
            })
            ->addColumn('status_badge', function (Payment $p) {
                $s = is_string($p->status) ? $p->status : ($p->status?->value ?? '');
                $class = match ($s) {
                    'settlement' => 'bg-success',
                    'pending' => 'bg-warning text-dark',
                    'expire', 'cancel', 'deny', 'failure' => 'bg-danger',
                    default => 'bg-secondary',
                };
                return '<span class="badge ' . $class . '">' . strtoupper($s) . '</span>';
            })
            ->editColumn('amount', function (Payment $p) {
                return 'Rp ' . number_format((float) $p->amount, 0, ',', '.');
            })
            ->addColumn('created', fn(Payment $p) => $p->created_at?->format('d/m/Y H:i'))
            ->addColumn('paid', fn(Payment $p) => $p->paid_at?->format('d/m/Y H:i') ?? '-')
            ->addColumn('action', function (Payment $p) {
                $trx = $p->transaction;
                $showUrl = $trx ? route('transaksi.show', $trx) : '#';
                $receiptUrl = $trx ? route('transaksi.struk', $trx) : '#';
                $isQris = ($trx && strtolower((string)($trx->payment_method?->value ?? $trx->payment_method ?? '')) === 'qris');
                $status = strtolower((string)($p->status->value ?? $p->status ?? ''));
                $qrisUrl = ($isQris && $status === 'pending') ? route('pembayaran.show', $trx) : null;
                $btns = '<div class="d-flex justify-content-end gap-1">';
                $btns .= '<a class="btn btn-sm btn-outline-primary" href="' . e($showUrl) . '"><i class="bi bi-eye"></i></a>';
                if ($qrisUrl) {
                    $btns .= '<a class="btn btn-sm btn-outline-success" href="' . e($qrisUrl) . '"><i class="bi bi-qr-code"></i></a>';
                }
                $btns .= '<a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener noreferrer" href="' . e($receiptUrl) . '"><i class="bi bi-receipt-cutoff"></i></a>';
                $btns .= '</div>';
                return $btns;
            })
            ->rawColumns(['invoice', 'status_badge', 'action'])
            ->toJson();
    }
    public function show(Transaction $transaction): View
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

        return view('payments.show', compact('transaction', 'payment'));
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
