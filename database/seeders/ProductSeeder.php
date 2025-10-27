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
            // Minuman
            ['sku' => 'DRK-AM600', 'name' => 'Air Mineral 600ml', 'category' => 'Minuman', 'price' => 4000, 'stock' => 200],
            ['sku' => 'DRK-SOD330', 'name' => 'Minuman Bersoda 330ml', 'category' => 'Minuman', 'price' => 8000, 'stock' => 120],
            ['sku' => 'DRK-TEHBOT', 'name' => 'Teh Botol 350ml', 'category' => 'Minuman', 'price' => 7000, 'stock' => 150],

            // Makanan Ringan
            ['sku' => 'SNK-KRP200', 'name' => 'Keripik Kentang 200g', 'category' => 'Makanan Ringan', 'price' => 12000, 'stock' => 90],
            ['sku' => 'SNK-BSK150', 'name' => 'Biskuit Cokelat 150g', 'category' => 'Makanan Ringan', 'price' => 10000, 'stock' => 100],

            // Mie & Instan
            ['sku' => 'INS-MIE001', 'name' => 'Mie Instan Goreng', 'category' => 'Mie & Instan', 'price' => 3500, 'stock' => 500],
            ['sku' => 'INS-MIE002', 'name' => 'Mie Instan Kuah', 'category' => 'Mie & Instan', 'price' => 3500, 'stock' => 500],

            // Roti & Kue
            ['sku' => 'BKB-RTTWAR', 'name' => 'Roti Tawar 400g', 'category' => 'Roti & Kue', 'price' => 18000, 'stock' => 40],
            ['sku' => 'BKB-RTIMAN', 'name' => 'Roti Manis Cokelat', 'category' => 'Roti & Kue', 'price' => 6000, 'stock' => 80],

            // Susu & Olahan
            ['sku' => 'DRY-SUSUHT', 'name' => 'Susu UHT 1L', 'category' => 'Susu & Olahan', 'price' => 22000, 'stock' => 60],
            ['sku' => 'DRY-KENTMAN', 'name' => 'Susu Kental Manis 370g', 'category' => 'Susu & Olahan', 'price' => 15000, 'stock' => 70],

            // Bahan Pokok
            ['sku' => 'STP-BER05KG', 'name' => 'Beras 5kg', 'category' => 'Bahan Pokok', 'price' => 75000, 'stock' => 30],
            ['sku' => 'STP-MINYK1L', 'name' => 'Minyak Goreng 1L', 'category' => 'Bahan Pokok', 'price' => 16000, 'stock' => 100],

            // Bumbu & Saus
            ['sku' => 'SAS-KECAP20', 'name' => 'Kecap Manis 220ml', 'category' => 'Bumbu & Saus', 'price' => 14000, 'stock' => 80],
            ['sku' => 'SAS-SAMB200', 'name' => 'Saus Sambal 200ml', 'category' => 'Bumbu & Saus', 'price' => 12000, 'stock' => 75],

            // Kopi & Teh
            ['sku' => 'DRK-KOPINS', 'name' => 'Kopi Instan 10s', 'category' => 'Kopi & Teh', 'price' => 13000, 'stock' => 120],
            ['sku' => 'DRK-TEHCEL', 'name' => 'Teh Celup 25s', 'category' => 'Kopi & Teh', 'price' => 10000, 'stock' => 90],

            // Perawatan Pribadi
            ['sku' => 'PRC-SABMAND', 'name' => 'Sabun Mandi 85g', 'category' => 'Perawatan Pribadi', 'price' => 4000, 'stock' => 200],
            ['sku' => 'PRC-SHAM200', 'name' => 'Shampo 200ml', 'category' => 'Perawatan Pribadi', 'price' => 18000, 'stock' => 60],

            // Kebersihan Rumah
            ['sku' => 'HCL-DET1KG', 'name' => 'Deterjen Bubuk 1kg', 'category' => 'Kebersihan Rumah', 'price' => 28000, 'stock' => 50],
            ['sku' => 'HCL-CUCI450', 'name' => 'Cairan Cuci Piring 450ml', 'category' => 'Kebersihan Rumah', 'price' => 12000, 'stock' => 80],

            // Bayi & Anak
            ['sku' => 'BBY-POPOKM', 'name' => 'Popok M 20s', 'category' => 'Bayi & Anak', 'price' => 45000, 'stock' => 40],
            ['sku' => 'BBY-TISUBS', 'name' => 'Tisu Basah 50s', 'category' => 'Bayi & Anak', 'price' => 12000, 'stock' => 60],

            // Beku
            ['sku' => 'FRZ-NUG250', 'name' => 'Nugget Ayam 250g', 'category' => 'Beku', 'price' => 28000, 'stock' => 35],
            ['sku' => 'FRZ-SOS300', 'name' => 'Sosis Ayam 300g', 'category' => 'Beku', 'price' => 26000, 'stock' => 40],
            ['sku' => 'FRZ-ESCRM65', 'name' => 'Es Krim Cup 65ml', 'category' => 'Beku', 'price' => 6000, 'stock' => 100],
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
