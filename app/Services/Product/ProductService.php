<?php

namespace App\Services\Product;

use App\Models\Product;

class ProductService implements ProductServiceInterface
{
    public function create(array $data): Product
    {
        return Product::create([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'sku' => $data['sku'],
            'price' => $data['price'],
            'stock' => $data['stock'] ?? 0,
            'min_stock' => $data['min_stock'] ?? 0,
            'expiry_date' => $data['expiry_date'] ?? null,
        ]);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'sku' => $data['sku'],
            'price' => $data['price'],
            'stock' => $data['stock'] ?? 0,
            'min_stock' => $data['min_stock'] ?? 0,
            'expiry_date' => $data['expiry_date'] ?? null,
        ]);

        return $product;
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function findOrFail(int $id): Product
    {
        return Product::findOrFail($id);
    }
}
