<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Services\Settings\SettingsServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function __construct(private readonly SettingsServiceInterface $settings) {}

    public function index(Request $request): Response
    {
        $statuses = array_values(array_filter(TransactionStatus::cases(), fn($s) => $s->value !== 'suspended'));
        $methods = PaymentMethod::cases();

        return Inertia::render('Transactions/Index', [
            'currency' => $this->settings->currency(),
            'statuses' => array_map(fn($s) => ['value' => $s->value, 'name' => strtoupper($s->value)], $statuses),
            'methods' => array_map(fn($m) => ['value' => $m->value, 'name' => strtoupper($m->value)], $methods),
            'filters' => [
                'q' => $request->query('q', ''),
                'status' => $request->query('status', ''),
                'method' => $request->query('method', ''),
                'from' => $request->query('from', ''),
                'to' => $request->query('to', ''),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        $status = $request->input('status');
        $method = $request->input('method');
        $from = $request->input('from');
        $to = $request->input('to');
        $perPage = max(1, min(50, (int) $request->input('per_page', 15)));

        $query = Transaction::query()
            ->with(['user:id,name'])
            ->where('status', '!=', TransactionStatus::SUSPENDED->value)
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($qq) use ($q) {
                    $qq->where('invoice_number', 'like', "%{$q}%")
                        ->orWhere('note', 'like', "%{$q}%");
                });
            })
            ->when($status && in_array($status, array_column(TransactionStatus::cases(), 'value'), true), function ($w) use ($status) {
                $w->where('status', $status);
            })
            ->when($method && in_array($method, array_column(PaymentMethod::cases(), 'value'), true), function ($w) use ($method) {
                $w->where('payment_method', $method);
            })
            ->when($from, function ($w) use ($from) {
                $w->whereDate('created_at', '>=', $from);
            })
            ->when($to, function ($w) use ($to) {
                $w->whereDate('created_at', '<=', $to);
            })
            ->orderByDesc('created_at')
            ->select(['id', 'user_id', 'invoice_number', 'payment_method', 'status', 'total', 'created_at']);

        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => $paginated->items(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
        ]);
    }

    public function show(Transaction $transaction): Response
    {
        $transaction->loadMissing(['details.product:id,name,sku', 'user:id,name', 'latestPayment']);

        return Inertia::render('Transactions/Show', [
            'trx' => [
                'id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'payment_method' => is_string($transaction->payment_method) 
                    ? $transaction->payment_method 
                    : ($transaction->payment_method?->value ?? ''),
                'status' => is_string($transaction->status) 
                    ? $transaction->status 
                    : ($transaction->status?->value ?? ''),
                'subtotal' => (float) ($transaction->subtotal ?? $transaction->total),
                'discount_amount' => (float) ($transaction->discount_amount ?? 0),
                'tax_amount' => (float) ($transaction->tax_amount ?? 0),
                'total' => (float) $transaction->total,
                'paid_amount' => (float) ($transaction->paid_amount ?? $transaction->total),
                'change_amount' => (float) ($transaction->change_amount ?? 0),
                'note' => $transaction->note,
                'created_at' => $transaction->created_at?->toIso8601String(),
                'user' => $transaction->user ? [
                    'id' => $transaction->user->id,
                    'name' => $transaction->user->name,
                ] : null,
                'details' => $transaction->details->map(fn($d) => [
                    'id' => $d->id,
                    'product_id' => $d->product_id,
                    'product_name' => $d->product?->name ?? '',
                    'quantity' => $d->quantity,
                    'price' => (float) $d->price,
                    'total' => (float) $d->total,
                    'product' => $d->product ? [
                        'id' => $d->product->id,
                        'name' => $d->product->name,
                        'sku' => $d->product->sku,
                    ] : null,
                ])->values(),
                'latest_payment' => $transaction->latestPayment ? [
                    'id' => $transaction->latestPayment->id,
                    'transaction_id' => $transaction->latestPayment->transaction_id,
                    'method' => is_string($transaction->latestPayment->method) 
                        ? $transaction->latestPayment->method 
                        : ($transaction->latestPayment->method?->value ?? ''),
                    'amount' => (float) $transaction->latestPayment->amount,
                    'status' => is_string($transaction->latestPayment->status) 
                        ? $transaction->latestPayment->status 
                        : ($transaction->latestPayment->status?->value ?? ''),
                    'reference' => $transaction->latestPayment->reference,
                    'created_at' => $transaction->latestPayment->created_at?->toIso8601String(),
                ] : null,
            ],
            'currency' => $this->settings->currency(),
        ]);
    }

    public function receipt(Transaction $transaction)
    {
        // Guard: only allow printing receipts for PAID transactions
        abort_unless(
            $transaction->status === TransactionStatus::PAID,
            403,
            'Struk hanya dapat dicetak untuk transaksi yang sudah dibayar.'
        );

        $transaction->loadMissing(['details.product', 'user']);

        return view('transactions.receipt', [
            'transaction'    => $transaction,
            'store_name'     => $this->settings->storeName(),
            'store_address'  => $this->settings->storeAddress(),
            'store_phone'    => $this->settings->storePhone(),
            'store_logo'     => $this->settings->storeLogoPath(),
            'currency'       => $this->settings->currency(),
            'discount_percent' => $this->settings->discountPercent(),
            'tax_percent'      => $this->settings->taxPercent(),
        ]);
    }
}

