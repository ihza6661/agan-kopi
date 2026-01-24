<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\RoleStatus;
use App\Http\Requests\Cashier\CheckoutRequest;
use App\Http\Requests\Cashier\HoldRequest;
use App\Models\Transaction;
use App\Enums\TransactionStatus;
use App\Models\Product;
use App\Services\Cashier\CashierServiceInterface;
use App\Services\Settings\SettingsServiceInterface;
use App\Services\ActivityLog\ActivityLoggerInterface;
use App\Services\Stock\StockMutationService;
use App\Services\Transaction\TransactionStateMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CashierController extends Controller
{
    public function __construct(
        private readonly CashierServiceInterface $cashier,
        private readonly SettingsServiceInterface $settings,
        private readonly ActivityLoggerInterface $logger,
        private readonly StockMutationService $stockService
    ) {
        $this->middleware(function ($request, $next) {
            if (!Auth::check() || !in_array(Auth::user()->role, [RoleStatus::ADMIN->value, RoleStatus::CASHIER->value], true)) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
            }
            return $next($request);
        });
    }

    public function index(): Response
    {
        return Inertia::render('Cashier/Index', [
            'currency' => $this->settings->currency(),
            'discount_percent' => $this->settings->discountPercent(),
            'tax_percent' => $this->settings->taxPercent(),
        ]);
    }

    public function products(): JsonResponse
    {
        $q = trim((string) request('q', ''));
        $limit = max(1, min(20, (int) request('limit', 10)));

        $query = Product::query()->select(['id', 'sku', 'name', 'price', 'stock']);
        if ($q !== '') {
            if (ctype_digit($q) && (int) $q > 0) {
                $query->where('id', (int) $q);
            } else {
                $query->where(function ($w) use ($q) {
                    $escaped = addcslashes($q, "%_\\");
                    $w->where('sku', 'like', "%{$escaped}%")
                        ->orWhere('name', 'like', "%{$escaped}%");
                });
            }
        }
        $products = $query->orderBy('name')->limit($limit)->get();

        return response()->json($products);
    }

    public function checkout(CheckoutRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $isAjax = $request->ajax() || $request->wantsJson() || $request->expectsJson();

        try {
            $order = $this->cashier->checkout(
                $data['items'],
                $data['payment_method'],
                (float) ($data['paid_amount'] ?? 0),
                $data['note'] ?? null,
                isset($data['suspended_from_id']) ? (int) $data['suspended_from_id'] : null
            );

            $this->logger->log('Buat Transaksi', 'Transaksi baru dibuat', [
                'transaction_id' => $order->id,
                'invoice' => $order->invoice_number,
                'payment_method' => $data['payment_method'],
                'items_count' => count($data['items'] ?? []),
                'note' => $data['note'] ?? null,
            ]);

            // Return JSON for all AJAX requests
            if ($isAjax) {
                $response = [
                    'transaction_id' => $order->id,
                    'invoice' => $order->invoice_number,
                    'status' => strtolower($order->status->value),
                ];

                return response()->json($response);
            }
        } catch (\Throwable $e) {
            if ($isAjax) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('kasir')
            ->with('success', 'Transaksi berhasil. Nomor: ' . $order->invoice_number)
            ->with('printed_transaction_id', $order->id)
            ->with('printed_invoice', $order->invoice_number);
    }

    public function hold(HoldRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        try {
            $trx = $this->cashier->hold($data['items'], $data['note'] ?? null, isset($data['suspended_from_id']) ? (int)$data['suspended_from_id'] : null);
            $this->logger->log('Tunda Transaksi', 'Transaksi ditunda', [
                'transaction_id' => $trx->id,
                'invoice' => $trx->invoice_number,
                'items_count' => count($data['items'] ?? []),
                'note' => $data['note'] ?? null,
            ]);
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage())->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'transaction_id' => $trx->id,
                'invoice' => $trx->invoice_number,
                'status' => $trx->status?->value ?? 'suspended',
            ]);
        }
        return redirect()->route('kasir')->with('success', 'Transaksi ditunda. Nomor: ' . $trx->invoice_number);
    }

    public function holds(): JsonResponse
    {
        $list = Transaction::query()
            ->withCount('details')
            ->where('status', TransactionStatus::SUSPENDED->value)
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'invoice_number', 'total', 'created_at', 'note']);

        return response()->json($list);
    }

    public function resume(Transaction $transaction): JsonResponse
    {
        abort_unless($transaction->status === TransactionStatus::SUSPENDED, 400, 'Transaksi bukan status ditunda.');
        $transaction->loadMissing('details');
        return response()->json([
            'id' => $transaction->id,
            'invoice' => $transaction->invoice_number,
            'note' => $transaction->note,
            'suspended_from_id' => $transaction->id,
            'items' => $transaction->details->map(fn($d) => [
                'product_id' => $d->product_id,
                'qty' => $d->quantity,
                'price' => (float) $d->price,
            ])->values(),
        ]);
    }

    public function destroyHold(Transaction $transaction): JsonResponse
    {
        abort_unless($transaction->status === TransactionStatus::SUSPENDED, 400, 'Transaksi bukan status ditunda.');
        $transaction->delete();
        return response()->json(['deleted' => true]);
    }

    /**
     * Manually confirm a QRIS payment.
     * Only QRIS transactions with status PENDING can be confirmed.
     * Deducts stock atomically and marks transaction as PAID.
     */
    public function confirmQris(Transaction $transaction): JsonResponse
    {
        // Validate: must be QRIS and PENDING
        abort_unless(
            $transaction->payment_method === \App\Enums\PaymentMethod::QRIS,
            400,
            'Transaksi bukan metode QRIS.'
        );
        abort_unless(
            $transaction->status === TransactionStatus::PENDING,
            400,
            'Transaksi tidak dalam status menunggu konfirmasi.'
        );

        // Check if already confirmed (double-click protection)
        if ($transaction->confirmed_at !== null) {
            return response()->json([
                'message' => 'Transaksi sudah dikonfirmasi sebelumnya.',
                'transaction_id' => $transaction->id,
                'invoice' => $transaction->invoice_number,
                'status' => $transaction->status->value,
            ], 409); // Conflict
        }

        try {
            DB::transaction(function () use ($transaction) {
                // Deduct stock via centralized service
                $this->stockService->commitTransaction($transaction);

                // Transition state via StateMachine
                TransactionStateMachine::transition($transaction, TransactionStatus::PAID);

                // Mark as PAID and record confirmation
                $transaction->update([
                    'status' => TransactionStatus::PAID,
                    'amount_paid' => $transaction->total,
                    'change' => 0,
                    'confirmed_by' => Auth::id(),
                    'confirmed_at' => now(),
                ]);

                // Update payment record to SETTLEMENT
                $payment = $transaction->payments()->where('method', PaymentMethod::QRIS)->first();
                if ($payment) {
                    $payment->update([
                        'status' => \App\Enums\PaymentStatus::SETTLEMENT,
                        'paid_at' => now(),
                    ]);
                }

                // Clean up suspended transaction if this was resumed from one
                if ($transaction->suspended_from_id) {
                    $original = Transaction::where('id', $transaction->suspended_from_id)
                        ->where('status', TransactionStatus::SUSPENDED)
                        ->first();
                    if ($original) {
                        $original->delete();
                    }
                }

                // Log the confirmation
                $this->logger->log('Konfirmasi QRIS', 'Pembayaran QRIS dikonfirmasi', [
                    'transaction_id' => $transaction->id,
                    'invoice' => $transaction->invoice_number,
                    'total' => $transaction->total,
                    'confirmed_by' => Auth::id(),
                ]);
            });
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('QRIS confirmation failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Gagal mengonfirmasi pembayaran.'], 500);
        }

        return response()->json([
            'success' => true,
            'transaction_id' => $transaction->id,
            'invoice' => $transaction->invoice_number,
            'status' => 'paid',
        ]);
    }

    /**
     * Cancel a pending QRIS payment.
     * PENDING → CANCELED, no stock changes (none were made).
     */
    public function cancelQris(Transaction $transaction): JsonResponse
    {
        abort_unless(
            $transaction->payment_method === PaymentMethod::QRIS,
            400,
            'Transaksi bukan metode QRIS.'
        );
        abort_unless(
            $transaction->status === TransactionStatus::PENDING,
            400,
            'Hanya transaksi pending yang dapat dibatalkan.'
        );

        try {
            DB::transaction(function () use ($transaction) {
                // Transition state via StateMachine
                TransactionStateMachine::transition($transaction, TransactionStatus::CANCELED);

                $transaction->update([
                    'status' => TransactionStatus::CANCELED,
                ]);

                // Update payment record to CANCELED
                $payment = $transaction->payments()->where('method', PaymentMethod::QRIS)->first();
                if ($payment) {
                    $payment->update([
                        'status' => \App\Enums\PaymentStatus::CANCEL,
                    ]);
                }

                $this->logger->log('Batalkan QRIS', 'Pembayaran QRIS dibatalkan', [
                    'transaction_id' => $transaction->id,
                    'invoice' => $transaction->invoice_number,
                    'canceled_by' => Auth::id(),
                ]);
            });
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'transaction_id' => $transaction->id,
            'invoice' => $transaction->invoice_number,
            'status' => 'canceled',
        ]);
    }
}
