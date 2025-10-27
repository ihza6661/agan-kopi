<?php

namespace App\Services\Cashier;

use App\Models\Transaction;

interface CashierServiceInterface
{
    public function checkout(array $items, string $paymentMethod, float $paidAmount = 0, ?string $note = null, ?int $suspendedFromId = null): Transaction;

    public function generateInvoiceNumber(int $transactionId, string $format): string;

    public function hold(array $items, ?string $note = null, ?int $suspendedId = null): Transaction;
}
