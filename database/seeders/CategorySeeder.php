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
                'name' => 'Kopi',
                'description' => 'Berbagai macam kopi panas dan dingin seperti kopi hitam, kopi susu, kopi tubruk.',
            ],
            [
                'name' => 'Teh',
                'description' => 'Teh manis, teh tawar, teh susu, dan varian teh lainnya.',
            ],
            [
                'name' => 'Minuman Dingin',
                'description' => 'Es jeruk, es teh, es campur, dan minuman dingin lainnya.',
            ],
            [
                'name' => 'Gorengan',
                'description' => 'Pisang goreng, tempe goreng, tahu goreng, bakwan, dan gorengan lainnya.',
            ],
            [
                'name' => 'Mie & Nasi',
                'description' => 'Mie goreng, mie rebus, nasi goreng, dan makanan berat lainnya.',
            ],
            [
                'name' => 'Snack',
                'description' => 'Keripik, kacang, dan cemilan kemasan lainnya.',
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
