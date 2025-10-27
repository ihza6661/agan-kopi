<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Minuman',
                'description' => 'Air mineral, minuman bersoda, teh, isotonik, dan minuman siap saji lainnya.',
            ],
            [
                'name' => 'Makanan Ringan',
                'description' => 'Snack kemasan seperti keripik, biskuit, cokelat, dan permen.',
            ],
            [
                'name' => 'Mie & Instan',
                'description' => 'Mie instan, bubur instan, dan makanan cepat saji lainnya.',
            ],
            [
                'name' => 'Roti & Kue',
                'description' => 'Roti tawar, roti manis, dan kue-kue kemasan.',
            ],
            [
                'name' => 'Susu & Olahan',
                'description' => 'Susu UHT, kental manis, yogurt, dan olahan susu lainnya.',
            ],
            [
                'name' => 'Bahan Pokok',
                'description' => 'Beras, gula, minyak goreng, garam, dan kebutuhan dapur dasar.',
            ],
            [
                'name' => 'Bumbu & Saus',
                'description' => 'Kecap, saus, sambal, kaldu instan, dan bumbu masak.',
            ],
            [
                'name' => 'Kopi & Teh',
                'description' => 'Kopi bubuk, kopi instan, teh celup dan bubuk.',
            ],
            [
                'name' => 'Perawatan Pribadi',
                'description' => 'Sabun, sampo, pasta gigi, tisu, dan kebutuhan personal care.',
            ],
            [
                'name' => 'Kebersihan Rumah',
                'description' => 'Deterjen, cairan pencuci piring, pembersih lantai, dan kebutuhan rumah tangga.',
            ],
            [
                'name' => 'Bayi & Anak',
                'description' => 'Popok, tisu basah, dan kebutuhan bayi-anak.',
            ],
            [
                'name' => 'Beku',
                'description' => 'Makanan beku seperti nugget, sosis beku, dan es krim.',
            ],
        ];

        foreach ($categories as $data) {
            Category::updateOrCreate(
                ['name' => $data['name']],
                ['description' => $data['description']]
            );
        }
    }
}
