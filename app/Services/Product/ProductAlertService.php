<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\User;
use App\Notifications\ProductLowStock;
use App\Notifications\ProductNearExpiry;
use Illuminate\Notifications\DatabaseNotification;
use Carbon\Carbon;

class ProductAlertService
{
    public function scanAndNotify(int $expiryThresholdDays = 7): void
    {
        $today = Carbon::now()->startOfDay();
        $thresholdDate = (clone $today)->addDays($expiryThresholdDays);

        $nearExpiry = Product::query()
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', $today->toDateString())
            ->whereDate('expiry_date', '<=', $thresholdDate->toDateString())
            ->get();

        $lowStock = Product::query()
            ->whereColumn('stock', '<=', 'min_stock')
            ->get();

        $recipients = User::query()
            ->whereIn('role', [\App\Enums\RoleStatus::ADMIN->value, \App\Enums\RoleStatus::CASHIER->value])
            ->get();

        foreach ($nearExpiry as $p) {
            $days = $today->diffInDays($p->expiry_date);
            foreach ($recipients as $user) {
                $exists = DatabaseNotification::query()
                    ->where('notifiable_id', $user->id)
                    ->where('notifiable_type', User::class)
                    ->where('type', ProductNearExpiry::class)
                    ->whereNull('read_at')
                    ->where('data->product_id', $p->id)
                    ->exists();
                if (!$exists) {
                    $user->notifyNow(new ProductNearExpiry($p, $days));
                }
            }
        }

        foreach ($lowStock as $p) {
            foreach ($recipients as $user) {
                $exists = DatabaseNotification::query()
                    ->where('notifiable_id', $user->id)
                    ->where('notifiable_type', User::class)
                    ->where('type', ProductLowStock::class)
                    ->whereNull('read_at')
                    ->where('data->product_id', $p->id)
                    ->exists();
                if (!$exists) {
                    $user->notifyNow(new ProductLowStock($p));
                }
            }
        }
    }

    public function checkAndNotifyForProduct(Product $product, int $expiryThresholdDays = 7): void
    {
        $today = Carbon::now()->startOfDay();
        $recipients = User::query()
            ->whereIn('role', [\App\Enums\RoleStatus::ADMIN->value, \App\Enums\RoleStatus::CASHIER->value])
            ->get();

        // Low stock
        if ((int) $product->stock <= (int) $product->min_stock) {
            foreach ($recipients as $user) {
                $exists = DatabaseNotification::query()
                    ->where('notifiable_id', $user->id)
                    ->where('notifiable_type', User::class)
                    ->where('type', ProductLowStock::class)
                    ->whereNull('read_at')
                    ->where('data->product_id', $product->id)
                    ->exists();
                if (!$exists) {
                    $user->notifyNow(new ProductLowStock($product));
                }
            }
        }

        // Near expiry
        if ($product->expiry_date) {
            $days = $today->diffInDays($product->expiry_date, false);
            if ($days >= 0 && $days <= $expiryThresholdDays) {
                foreach ($recipients as $user) {
                    $exists = DatabaseNotification::query()
                        ->where('notifiable_id', $user->id)
                        ->where('notifiable_type', User::class)
                        ->where('type', ProductNearExpiry::class)
                        ->whereNull('read_at')
                        ->where('data->product_id', $product->id)
                        ->exists();
                    if (!$exists) {
                        $user->notifyNow(new ProductNearExpiry($product, $days));
                    }
                }
            }
        }
    }
}
