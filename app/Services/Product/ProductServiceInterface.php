<?php

namespace App\Services\Product;

use App\Models\Product;

interface ProductServiceInterface
{
    public function create(array $data): Product;

    public function update(Product $product, array $data): Product;

    public function delete(Product $product): void;

    public function findOrFail(int $id): Product;
}
