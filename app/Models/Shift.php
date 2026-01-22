<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    protected $fillable = [
        'user_id',
        'started_at',
        'ended_at',
        'opening_cash',
        'closing_cash',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'opening_cash' => 'decimal:2',
        'closing_cash' => 'decimal:2',
    ];

    // ─────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // ─────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────

    /**
     * Scope: active shifts (not ended).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('ended_at');
    }

    /**
     * Scope: shifts for a specific user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    // ─────────────────────────────────────────────
    // Computed Properties
    // ─────────────────────────────────────────────

    /**
     * Check if shift is currently active.
     */
    public function isActive(): bool
    {
        return $this->ended_at === null;
    }

    /**
     * Total sales from PAID transactions in this shift.
     */
    public function getTotalSalesAttribute(): float
    {
        return (float) $this->transactions()
            ->where('status', TransactionStatus::PAID)
            ->sum('total');
    }

    /**
     * Total cash sales in this shift.
     */
    public function getCashTotalAttribute(): float
    {
        return (float) $this->transactions()
            ->where('status', TransactionStatus::PAID)
            ->where('payment_method', PaymentMethod::CASH)
            ->sum('total');
    }

    /**
     * Total QRIS sales in this shift.
     */
    public function getQrisTotalAttribute(): float
    {
        return (float) $this->transactions()
            ->where('status', TransactionStatus::PAID)
            ->where('payment_method', PaymentMethod::QRIS)
            ->sum('total');
    }

    /**
     * Number of PAID transactions in this shift.
     */
    public function getTransactionCountAttribute(): int
    {
        return $this->transactions()
            ->where('status', TransactionStatus::PAID)
            ->count();
    }

    /**
     * Expected cash in drawer = opening + cash sales.
     */
    public function getExpectedCashAttribute(): float
    {
        return (float) $this->opening_cash + $this->cash_total;
    }

    /**
     * Variance = closing - expected (if closed).
     */
    public function getVarianceAttribute(): ?float
    {
        if ($this->closing_cash === null) {
            return null;
        }
        return (float) $this->closing_cash - $this->expected_cash;
    }

    // ─────────────────────────────────────────────
    // Static Helpers
    // ─────────────────────────────────────────────

    /**
     * Get active shift for a user (if any).
     */
    public static function getActiveForUser(int $userId): ?self
    {
        return static::active()->forUser($userId)->first();
    }
}
