# Core Freeze Policy - Agan Kopi POS

This document defines the critical payment and stock management code that should remain frozen after stabilization.

## Frozen Components

### 1. Checkout Flow
**Files**: 
- `app/Services/Cashier/CashierService.php` (checkout method)
- `app/Http/Requests/Cashier/CheckoutRequest.php`

**Rules**:
- Do NOT modify the checkout method without full test coverage review
- Any changes require review by system owner
- New payment methods go through approval process

### 2. Stock Mutation
**Files**:
- `app/Services/Stock/StockMutationService.php`

**Rules**:
- Stock changes ONLY through `StockMutationService::commitTransaction()`
- Never add stock mutation logic in controllers or observers
- Rollback functionality requires extensive testing

### 3. Payment Confirmation
**Files**:
- `app/Http/Controllers/CashierController.php` (confirmQris, cancelQris)
- `app/Services/Transaction/TransactionStateMachine.php`

**Rules**:
- State transitions ONLY through StateMachine
- Confirmation metadata (`confirmed_by`, `confirmed_at`) must always be recorded
- Double-click protection must remain in place

### 4. Transaction State Machine
**File**: `app/Services/Transaction/TransactionStateMachine.php`

**Allowed Transitions**:
```
SUSPENDED → PENDING, PAID
PENDING → PAID, CANCELED
PAID → (terminal)
CANCELED → (terminal)
```

**Rules**:
- Do NOT add new transitions without security review
- Terminal states cannot transition out

## How to Add Features

1. **New Payment Methods**: Create new service, do NOT modify existing flow
2. **New Transaction Types**: Extend with new status, do NOT reuse existing ones
3. **Reporting**: Query only, never mutate core data
4. **Integrations**: Add adapter layer, keep core untouched

## Required for Any Core Change

1. Full test coverage for changed code
2. Review by system owner
3. Rollback plan documented
4. Audit trail verification

## Version
Last Updated: 2026-01-22
Status: FROZEN
