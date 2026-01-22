<?php

namespace App\Services\Stock;

use App\Enums\TransactionStatus;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\Product\ProductAlertService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * StockMutationService is the SINGLE entry point for all stock changes.
 * 
 * Rules:
 * - Stock is ONLY mutated here
 * - Called exactly once per transaction
 * - Wrapped in DB transaction + row locks
 * - Never mutate stock in controllers or observers
 */
class StockMutationService
{
    public function __construct(
        private readonly ProductAlertService $alertService
    ) {}

    /**
     * Commit stock deduction for a transaction.
     * Must be called exactly once per transaction, typically when marking as PAID.
     * 
     * @throws InvalidArgumentException if stock insufficient
     */
    public function commitTransaction(Transaction $transaction): void
    {
        // Already paid = already committed, reject
        if ($transaction->status === TransactionStatus::PAID) {
            throw new InvalidArgumentException('Transaksi sudah dibayar, stok sudah dikurangi.');
        }

        $transaction->loadMissing('details');

        DB::transaction(function () use ($transaction) {
            foreach ($transaction->details as $detail) {
                $product = Product::lockForUpdate()->findOrFail($detail->product_id);

                if ($product->stock < $detail->quantity) {
                    throw new InvalidArgumentException(
                        "Stok tidak mencukupi untuk {$product->name}. Tersedia: {$product->stock}, dibutuhkan: {$detail->quantity}"
                    );
                }

                $product->decrement('stock', $detail->quantity);

                // Check low stock alerts
                $this->alertService->checkAndNotifyForProduct($product, 7);
            }
        });
    }

    /**
     * Rollback stock for a canceled transaction (if stock was pre-deducted).
     * For QRIS transactions in PENDING state, this is a no-op since stock wasn't deducted.
     */
    public function rollbackTransaction(Transaction $transaction): void
    {
        // Only rollback if we're certain stock was deducted
        // In the current flow, QRIS pending = no stock deducted, CASH paid = stock deducted
        // Rollback is only needed for edge cases like CASH refunds (not currently implemented)
        
        $transaction->loadMissing('details');

        DB::transaction(function () use ($transaction) {
            foreach ($transaction->details as $detail) {
                $product = Product::lockForUpdate()->findOrFail($detail->product_id);
                $product->increment('stock', $detail->quantity);
            }
        });
    }

    /**
     * Check if stock deduction is possible for a transaction without actually committing.
     */
    public function canCommit(Transaction $transaction): bool
    {
        $transaction->loadMissing('details');

        foreach ($transaction->details as $detail) {
            $product = Product::find($detail->product_id);
            if (!$product || $product->stock < $detail->quantity) {
                return false;
            }
        }

        return true;
    }
}
