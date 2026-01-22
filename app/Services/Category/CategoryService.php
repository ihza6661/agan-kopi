<?php

namespace App\Services\Category;

use App\Models\Category;

class CategoryService implements CategoryServiceInterface
{
    public function create(array $data): Category
    {
        return Category::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        return $category;
    }

    public function delete(Category $category): void
    {
        // Check if category has products
        $productsCount = $category->products()->count();
        
        if ($productsCount > 0) {
            throw new \Exception("Kategori tidak dapat dihapus karena masih memiliki {$productsCount} produk. Silakan hapus atau pindahkan produk terlebih dahulu.");
        }
        
        $category->delete();
    }

    public function findOrFail(int $id): Category
    {
        return Category::findOrFail($id);
    }
}
