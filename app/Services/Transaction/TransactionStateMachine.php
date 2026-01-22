<?php

namespace App\Services\Transaction;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use InvalidArgumentException;

/**
 * TransactionStateMachine enforces explicit state transitions for transactions.
 * 
 * Allowed transitions:
 * - SUSPENDED → PENDING (resume as QRIS)
 * - SUSPENDED → PAID (resume as cash)
 * - PENDING → PAID (QRIS manual confirmation)
 * - PENDING → CANCELED (QRIS cancellation)
 * 
 * Terminal states (no transitions out):
 * - PAID
 * - CANCELED
 */
class TransactionStateMachine
{
    /**
     * Map of allowed transitions: from => [allowed to states]
     */
    private static array $allowedTransitions = [
        'suspended' => ['pending', 'paid'],
        'pending'   => ['paid', 'canceled'],
        'paid'      => [], // Terminal
        'canceled'  => [], // Terminal
    ];

    /**
     * Check if a transition is allowed.
     */
    public static function canTransition(TransactionStatus $from, TransactionStatus $to): bool
    {
        $fromValue = strtolower($from->value);
        $toValue = strtolower($to->value);

        if (!isset(self::$allowedTransitions[$fromValue])) {
            return false;
        }

        return in_array($toValue, self::$allowedTransitions[$fromValue], true);
    }

    /**
     * Transition a transaction to a new status.
     * Throws if transition is not allowed.
     */
    public static function transition(Transaction $transaction, TransactionStatus $to): void
    {
        $from = $transaction->status;

        if (!self::canTransition($from, $to)) {
            throw new InvalidArgumentException(
                "Transisi status tidak valid: {$from->value} → {$to->value}"
            );
        }

        $transaction->status = $to;
    }

    /**
     * Get all possible next states for a transaction.
     */
    public static function possibleTransitions(TransactionStatus $current): array
    {
        $value = strtolower($current->value);
        
        return array_map(
            fn($s) => TransactionStatus::from($s),
            self::$allowedTransitions[$value] ?? []
        );
    }

    /**
     * Check if a status is terminal (no further transitions possible).
     */
    public static function isTerminal(TransactionStatus $status): bool
    {
        return empty(self::$allowedTransitions[strtolower($status->value)] ?? []);
    }
}
