<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryIds = Category::pluck('id', 'name');

        $products = [
            // Kopi
            ['sku' => 'KOPI-HIT', 'name' => 'Kopi Hitam', 'category' => 'Kopi', 'price' => 5000, 'stock' => 100],
            ['sku' => 'KOPI-SUS', 'name' => 'Kopi Susu', 'category' => 'Kopi', 'price' => 8000, 'stock' => 100],
            ['sku' => 'KOPI-TUB', 'name' => 'Kopi Tubruk', 'category' => 'Kopi', 'price' => 6000, 'stock' => 100],
            ['sku' => 'KOPI-ESP', 'name' => 'Espresso', 'category' => 'Kopi', 'price' => 10000, 'stock' => 80],
            ['sku' => 'KOPI-CAP', 'name' => 'Cappuccino', 'category' => 'Kopi', 'price' => 12000, 'stock' => 80],
            ['sku' => 'KOPI-LAT', 'name' => 'Cafe Latte', 'category' => 'Kopi', 'price' => 13000, 'stock' => 80],
            ['sku' => 'ES-KOPI', 'name' => 'Es Kopi', 'category' => 'Kopi', 'price' => 8000, 'stock' => 100],
            ['sku' => 'ES-KOPSU', 'name' => 'Es Kopi Susu', 'category' => 'Kopi', 'price' => 10000, 'stock' => 100],

            // Teh
            ['sku' => 'TEH-MAN', 'name' => 'Teh Manis Panas', 'category' => 'Teh', 'price' => 4000, 'stock' => 150],
            ['sku' => 'TEH-TAW', 'name' => 'Teh Tawar Panas', 'category' => 'Teh', 'price' => 3000, 'stock' => 150],
            ['sku' => 'TEH-SUS', 'name' => 'Teh Susu Panas', 'category' => 'Teh', 'price' => 7000, 'stock' => 100],
            ['sku' => 'ES-TEH', 'name' => 'Es Teh Manis', 'category' => 'Teh', 'price' => 5000, 'stock' => 150],
            ['sku' => 'ES-TEHSU', 'name' => 'Es Teh Susu', 'category' => 'Teh', 'price' => 8000, 'stock' => 100],
            ['sku' => 'TEH-JAH', 'name' => 'Teh Jahe', 'category' => 'Teh', 'price' => 6000, 'stock' => 80],

            // Minuman Dingin
            ['sku' => 'ES-JER', 'name' => 'Es Jeruk', 'category' => 'Minuman Dingin', 'price' => 7000, 'stock' => 100],
            ['sku' => 'ES-JERPR', 'name' => 'Es Jeruk Peras', 'category' => 'Minuman Dingin', 'price' => 10000, 'stock' => 80],
            ['sku' => 'ES-CAM', 'name' => 'Es Campur', 'category' => 'Minuman Dingin', 'price' => 12000, 'stock' => 60],
            ['sku' => 'ES-KEL', 'name' => 'Es Kelapa Muda', 'category' => 'Minuman Dingin', 'price' => 10000, 'stock' => 50],
            ['sku' => 'ES-BUH', 'name' => 'Es Buah', 'category' => 'Minuman Dingin', 'price' => 12000, 'stock' => 60],
            ['sku' => 'ES-CEN', 'name' => 'Es Cendol', 'category' => 'Minuman Dingin', 'price' => 8000, 'stock' => 80],
            ['sku' => 'AIR-MIN', 'name' => 'Air Mineral', 'category' => 'Minuman Dingin', 'price' => 3000, 'stock' => 200],

            // Gorengan
            ['sku' => 'GOR-PIS', 'name' => 'Pisang Goreng', 'category' => 'Gorengan', 'price' => 5000, 'stock' => 80],
            ['sku' => 'GOR-TEM', 'name' => 'Tempe Goreng', 'category' => 'Gorengan', 'price' => 5000, 'stock' => 80],
            ['sku' => 'GOR-TAH', 'name' => 'Tahu Goreng', 'category' => 'Gorengan', 'price' => 5000, 'stock' => 80],
            ['sku' => 'GOR-BAK', 'name' => 'Bakwan', 'category' => 'Gorengan', 'price' => 5000, 'stock' => 80],
            ['sku' => 'GOR-SIN', 'name' => 'Singkong Goreng', 'category' => 'Gorengan', 'price' => 5000, 'stock' => 70],
            ['sku' => 'GOR-TEM-M', 'name' => 'Tempe Mendoan', 'category' => 'Gorengan', 'price' => 6000, 'stock' => 70],
            ['sku' => 'GOR-RIS', 'name' => 'Risoles', 'category' => 'Gorengan', 'price' => 6000, 'stock' => 60],

            // Mie & Nasi
            ['sku' => 'MIE-GOR', 'name' => 'Mie Goreng', 'category' => 'Mie & Nasi', 'price' => 15000, 'stock' => 50],
            ['sku' => 'MIE-REB', 'name' => 'Mie Rebus', 'category' => 'Mie & Nasi', 'price' => 15000, 'stock' => 50],
            ['sku' => 'MIE-GOR-T', 'name' => 'Mie Goreng Telur', 'category' => 'Mie & Nasi', 'price' => 18000, 'stock' => 50],
            ['sku' => 'NASI-GOR', 'name' => 'Nasi Goreng', 'category' => 'Mie & Nasi', 'price' => 18000, 'stock' => 50],
            ['sku' => 'NASI-GOR-T', 'name' => 'Nasi Goreng Telur', 'category' => 'Mie & Nasi', 'price' => 20000, 'stock' => 50],
            ['sku' => 'INDOMIE', 'name' => 'Indomie Rebus', 'category' => 'Mie & Nasi', 'price' => 10000, 'stock' => 80],

            // Snack
            ['sku' => 'SNK-KEP', 'name' => 'Keripik Singkong', 'category' => 'Snack', 'price' => 8000, 'stock' => 100],
            ['sku' => 'SNK-KAC', 'name' => 'Kacang Goreng', 'category' => 'Snack', 'price' => 8000, 'stock' => 100],
            ['sku' => 'SNK-KEP-P', 'name' => 'Keripik Pisang', 'category' => 'Snack', 'price' => 10000, 'stock' => 80],
            ['sku' => 'SNK-REM', 'name' => 'Rempeyek', 'category' => 'Snack', 'price' => 5000, 'stock' => 100],
        ];

        foreach ($products as $p) {
            $categoryName = $p['category'];
            $categoryId = $categoryIds[$categoryName] ?? Category::where('name', $categoryName)->value('id');
            if (!$categoryId) {
                continue;
            }

            Product::updateOrCreate(
                ['sku' => $p['sku']],
                [
                    'category_id' => $categoryId,
                    'name' => $p['name'],
                    'price' => $p['price'],
                    'stock' => $p['stock'],
                ]
            );
        }
    }
}
