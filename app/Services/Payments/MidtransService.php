<?php

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MidtransService implements MidtransServiceInterface
{
    private function init(): void
    {
        \Midtrans\Config::$serverKey    = (string) config('midtrans.server_key');
        \Midtrans\Config::$isProduction = (bool) config('midtrans.is_production', false);
        \Midtrans\Config::$isSanitized  = (bool) config('midtrans.is_sanitized', true);
        \Midtrans\Config::$is3ds        = (bool) config('midtrans.is_3ds', true);
        \Midtrans\Config::$overrideNotifUrl = (string) config('midtrans.override_notification_url', null);
    }

    public function createQrisPayment(Transaction $transaction): Payment
    {
        $this->init();

        $orderId = 'TRX-' . $transaction->id . '-' . now()->format('YmdHis');
        $gross   = (int) ceil((float) $transaction->total);

        $params = [
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $gross,
            ],
            'custom_expiry' => [
                'expiry_duration' => 30,
                'unit' => 'minute',
            ],
        ];

        $resp = \Midtrans\CoreApi::charge($params);

        $r = is_array($resp) ? $resp : json_decode(json_encode($resp), true);

        $qrString = $r['qr_string'] ?? null;
        $qrUrl = null;
        if (!empty($r['actions']) && is_array($r['actions'])) {
            foreach ($r['actions'] as $a) {
                if (($a['name'] ?? '') === 'generate-qr-code') {
                    $qrUrl = $a['url'] ?? null;
                    break;
                }
            }
        }

        return Payment::create([
            'transaction_id'          => $transaction->id,
            'method'                  => PaymentMethod::QRIS,
            'provider'                => 'midtrans',
            'provider_transaction_id' => $r['transaction_id'] ?? null,
            'provider_order_id'       => $r['order_id'] ?? $orderId,
            'status'                  => PaymentStatus::PENDING,
            'amount'                  => $gross,
            'qr_string'               => $qrString,
            'qr_url'                  => $qrUrl,
            'metadata'                => $r,
            'expired_at'              => now()->addMinutes(30),
        ]);
    }

    public function createSnapTransaction(Transaction $trx): array
    {
        $this->init();

        $orderId = 'TRX-' . $trx->id . '-' . now()->format('YmdHis');

        $params = [
            'transaction_details' => [
                'order_id'      => $orderId,
                'gross_amount'  => (int) round((float) $trx->total),
            ],
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit'       => 'minute',
                'duration'   => 15,
            ],
            'customer_details' => [
                'first_name' => optional($trx->user)->name,
                'email'      => optional($trx->user)->email,
            ],
        ];

        $snap = \Midtrans\Snap::createTransaction($params);

        Payment::create([
            'transaction_id'          => $trx->id,
            'method'                  => PaymentMethod::QRIS,
            'provider'                => 'midtrans',
            'provider_transaction_id' => null,
            'provider_order_id'       => $orderId,
            'status'                  => PaymentStatus::PENDING,
            'amount'                  => $trx->total,
            'qr_string'               => null,
            'qr_url'                  => null,
            'metadata'                => [
                'snap_token'   => $snap->token ?? null,
                'redirect_url' => $snap->redirect_url ?? null,
            ],
        ]);

        return [
            'token'        => $snap->token ?? null,
            'redirect_url' => $snap->redirect_url ?? null,
            'order_id'     => $orderId,
        ];
    }

    public function handleNotification(): void
    {
        $this->init();

        $notif = new \Midtrans\Notification();
        $trxStatus = (string) $notif->transaction_status;
        $orderId = (string) $notif->order_id;
        $providerTrxId = (string) $notif->transaction_id;

        $payment = Payment::query()
            ->where('provider', 'midtrans')
            ->where(fn($q) => $q->where('provider_order_id', $orderId)->orWhere('provider_transaction_id', $providerTrxId))
            ->latest()->first();

        if (!$payment) {
            Log::warning('Midtrans notification for unknown payment', ['order_id' => $orderId, 'trx_id' => $providerTrxId]);
            return;
        }

        $payment->provider_transaction_id = $providerTrxId ?: $payment->provider_transaction_id;
        $payment->metadata = array_merge((array) $payment->metadata ?? [], ['notification' => json_decode(json_encode($notif), true)]);

        $transaction = $payment->transaction;

        switch ($trxStatus) {
            case 'settlement':
                $payment->status = PaymentStatus::SETTLEMENT;
                $payment->paid_at = now();
                $payment->save();

                $transaction->update([
                    'status' => TransactionStatus::PAID,
                    'amount_paid' => $transaction->total,
                    'change' => 0,
                ]);

                if ($transaction->suspended_from_id) {
                    $orig = Transaction::where('id', $transaction->suspended_from_id)
                        ->where('status', TransactionStatus::SUSPENDED)
                        ->first();
                    if ($orig) {
                        $orig->delete();
                    }
                }
                break;

            case 'pending':
                $payment->status = PaymentStatus::PENDING;
                $payment->save();
                if ($transaction->status !== TransactionStatus::PENDING) {
                    $transaction->update(['status' => TransactionStatus::PENDING]);
                }
                break;

            case 'expire':
                $payment->status = PaymentStatus::EXPIRE;
                $payment->expired_at = now();
                $payment->save();

                $transaction->update(['status' => TransactionStatus::CANCELED]);
                break;

            case 'cancel':
                $payment->status = PaymentStatus::CANCEL;
                $payment->save();

                $transaction->update(['status' => TransactionStatus::CANCELED]);
                break;

            case 'deny':
                $payment->status = PaymentStatus::DENY;
                $payment->save();
                break;

            default:
                $payment->status = PaymentStatus::FAILURE;
                $payment->save();
                break;
        }
    }
}
