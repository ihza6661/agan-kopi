<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => $this->faker->words(2, true),
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-####')),
            'price' => $this->faker->numberBetween(1000, 100000) / 100,
            'stock' => $this->faker->numberBetween(5, 50),
            'min_stock' => 5,
            'expiry_date' => null,
        ];
    }
}
