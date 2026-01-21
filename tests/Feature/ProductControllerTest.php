<?php

namespace Tests\Feature;

use App\Enums\RoleStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ActivityLog\ActivityLoggerInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin()
    {
        /** @var User $admin */
        $admin = User::factory()->createOne([
            'role' => RoleStatus::ADMIN->value,
            'password' => 'password',
        ]);
        $this->actingAs($admin, 'web');
        return $admin;
    }

    private function mockLogger(): void
    {
        $fake = new class implements ActivityLoggerInterface {
            public function log(string $activity, ?string $description = null, ?array $context = null): void
            {
                // no-op for tests
            }
        };
        $this->app->instance(ActivityLoggerInterface::class, $fake);
    }

    public function test_admin_can_view_index(): void
    {
        $this->mockLogger();
        $this->actingAsAdmin();

        $res = $this->get(route('produk.index'));
        $res->assertOk();
    }

    public function test_non_admin_forbidden(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne(['role' => RoleStatus::CASHIER->value]);
        $this->actingAs($user, 'web');

        $this->get(route('produk.index'))->assertForbidden();
    }

    public function test_store_product(): void
    {
        $this->mockLogger();
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $payload = [
            'category_id' => $category->id,
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'price' => 10000,
            'stock' => 100,
            'min_stock' => 10,
        ];

        $res = $this->post(route('produk.store'), $payload);

        $res->assertRedirect(route('produk.index'));
        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'price' => 10000,
        ]);
    }

    public function test_update_product(): void
    {
        $this->mockLogger();
        $this->actingAsAdmin();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Old Name',
            'sku' => 'OLD-SKU',
        ]);

        $res = $this->put(route('produk.update', $product), [
            'category_id' => $category->id,
            'name' => 'New Name',
            'sku' => 'NEW-SKU',
            'price' => 15000,
            'stock' => 50,
        ]);

        $res->assertRedirect(route('produk.index'));
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'New Name',
            'sku' => 'NEW-SKU',
            'price' => 15000,
        ]);
    }

    public function test_delete_product(): void
    {
        $this->mockLogger();
        $this->actingAsAdmin();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $res = $this->delete(route('produk.destroy', $product));

        $res->assertRedirect(route('produk.index'));
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_validation_on_store(): void
    {
        $this->mockLogger();
        $this->actingAsAdmin();

        $res = $this->post(route('produk.store'), ['name' => '']);
        $res->assertSessionHasErrors(['category_id', 'name', 'sku', 'price']);
    }

    public function test_validation_unique_sku(): void
    {
        $this->mockLogger();
        $this->actingAsAdmin();
        $category = Category::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'DUPLICATE-SKU',
        ]);

        $res = $this->post(route('produk.store'), [
            'category_id' => $category->id,
            'name' => 'New Product',
            'sku' => 'DUPLICATE-SKU',
            'price' => 10000,
        ]);

        $res->assertSessionHasErrors('sku');
    }

    public function test_cannot_delete_product_with_transactions(): void
    {
        $this->mockLogger();
        $admin = $this->actingAsAdmin();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        // Create a transaction with this product manually
        $transaction = \App\Models\Transaction::create([
            'user_id' => $admin->id,
            'invoice_number' => 'INV-TEST-001',
            'subtotal' => 10000,
            'discount' => 0,
            'tax' => 0,
            'total' => 10000,
            'payment_method' => 'cash',
            'payment_status' => 'settlement',
            'status' => 'paid',
        ]);

        \App\Models\TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'price' => $product->price,
            'quantity' => 1,
            'total' => $product->price,
        ]);

        $res = $this->deleteJson(route('produk.destroy', $product));

        $res->assertStatus(422);
        $res->assertJson(['message' => 'Produk tidak dapat dihapus karena sudah memiliki riwayat transaksi.']);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }
}
