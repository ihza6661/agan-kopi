<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ProductNearExpiry extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Product $product, public readonly int $daysRemaining) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'near_expiry',
            'product_id' => $this->product->id,
            'sku' => $this->product->sku,
            'name' => $this->product->name,
            'expiry_date' => optional($this->product->expiry_date)->format('Y-m-d'),
            'days_remaining' => $this->daysRemaining,
            'edit_url' => route('produk.edit', $this->product),
            'message' => "Produk {$this->product->name} (SKU: {$this->product->sku}) mendekati kadaluarsa dalam {$this->daysRemaining} hari.",
        ];
    }
}
