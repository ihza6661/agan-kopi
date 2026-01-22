<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * AuditTrail - Immutable audit record.
 * 
 * Once created, records cannot be updated or deleted.
 * This provides a legal-grade audit trail for financial transactions.
 */
class AuditTrail extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'entity_type',
        'entity_id',
        'action',
        'before',
        'after',
        'metadata',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Prevent updates - audit trails are immutable.
     */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new InvalidArgumentException('Audit trails are immutable and cannot be updated.');
        }

        return parent::save($options);
    }

    /**
     * Prevent deletes - audit trails are permanent.
     */
    public function delete(): bool
    {
        throw new InvalidArgumentException('Audit trails cannot be deleted.');
    }

    /**
     * The user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper to create a transaction audit entry.
     */
    public static function logTransaction(
        int $userId,
        int $transactionId,
        string $action,
        ?array $before = null,
        ?array $after = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'entity_type' => 'transaction',
            'entity_id' => $transactionId,
            'action' => $action,
            'before' => $before,
            'after' => $after,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Helper to create a stock mutation audit entry.
     */
    public static function logStockMutation(
        int $userId,
        int $productId,
        string $action,
        int $previousStock,
        int $newStock,
        ?array $metadata = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'entity_type' => 'product',
            'entity_id' => $productId,
            'action' => $action,
            'before' => ['stock' => $previousStock],
            'after' => ['stock' => $newStock],
            'metadata' => $metadata,
        ]);
    }
}
