<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Services\Category\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class CategoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private CategoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CategoryService();
    }

    public function test_create_category_success(): void
    {
        $data = [
            'name' => 'Makanan',
            'description' => 'Semua jenis makanan',
        ];

        $cat = $this->service->create($data);

        $this->assertInstanceOf(Category::class, $cat);
        $this->assertDatabaseHas('categories', [
            'id' => $cat->id,
            'name' => 'Makanan',
            'description' => 'Semua jenis makanan',
        ]);
    }

    public function test_update_category_success(): void
    {
        $cat = Category::factory()->create([
            'name' => 'Minuman',
            'description' => 'Dingin & hangat',
        ]);

        $updated = $this->service->update($cat, [
            'name' => 'Minuman & Jus',
            'description' => 'Segala jenis minuman',
        ]);

        $this->assertSame($cat->id, $updated->id);
        $this->assertDatabaseHas('categories', [
            'id' => $cat->id,
            'name' => 'Minuman & Jus',
            'description' => 'Segala jenis minuman',
        ]);
    }

    public function test_delete_category_success(): void
    {
        $cat = Category::factory()->create();

        $this->service->delete($cat);

        $this->assertDatabaseMissing('categories', [
            'id' => $cat->id,
        ]);
    }

    public function test_find_or_fail_success(): void
    {
        $cat = Category::factory()->create();

        $found = $this->service->findOrFail($cat->id);

        $this->assertTrue($cat->is($found));
    }

    public function test_unique_name_constraint(): void
    {
        $name = 'Unik-Category';
        Category::factory()->create(['name' => $name]);

        $this->expectException(QueryException::class);

        // Creating with duplicate name should fail due to unique index
        $this->service->create([
            'name' => $name,
            'description' => null,
        ]);
    }
}
