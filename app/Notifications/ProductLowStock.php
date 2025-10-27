<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ProductLowStock extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Product $product) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'product_id' => $this->product->id,
            'sku' => $this->product->sku,
            'name' => $this->product->name,
            'stock' => (int) $this->product->stock,
            'min_stock' => (int) $this->product->min_stock,
            'edit_url' => route('produk.edit', $this->product),
            'message' => "Stok minimum tercapai untuk {$this->product->name} (SKU: {$this->product->sku}).",
        ];
    }
}
