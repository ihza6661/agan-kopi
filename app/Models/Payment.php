<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'method',
        'provider',
        'provider_transaction_id',
        'provider_order_id',
        'status',
        'amount',
        'qr_string',
        'qr_url',
        'metadata',
        'paid_at',
        'expired_at',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'metadata'   => 'array',
        'method'     => PaymentMethod::class,
        'status'     => PaymentStatus::class,
        'paid_at'    => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
