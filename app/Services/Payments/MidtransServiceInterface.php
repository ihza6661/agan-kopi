<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\Transaction;

interface MidtransServiceInterface
{
    public function createQrisPayment(Transaction $transaction): Payment;

    public function handleNotification(): void;

    public function createSnapTransaction(Transaction $trx): array;
}
