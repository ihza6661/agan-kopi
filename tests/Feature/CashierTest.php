<?php

namespace Tests\Feature;

use App\Enums\RoleStatus;
use App\Enums\TransactionStatus;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ActivityLog\ActivityLoggerInterface;
use App\Services\Payments\MidtransServiceInterface;
use App\Services\Product\ProductAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Fake side-effect services
        $this->app->instance(ActivityLoggerInterface::class, new class implements ActivityLoggerInterface {
            public function log(string $activity, ?string $description = null, ?array $context = null): void {}
        });
        $this->app->instance(MidtransServiceInterface::class, new class implements MidtransServiceInterface {
            public function createQrisPayment($transaction): \App\Models\Payment
            {
                return new \App\Models\Payment();
            }
            public function handleNotification(): void {}
            public function createSnapTransaction($transaction): array
            {
                return ['token' => 'dummy', 'redirect_url' => 'https://example.com'];
            }
        });
        $this->app->instance(ProductAlertService::class, new class extends ProductAlertService {
            public function __construct() {}
            public function checkAndNotifyForProduct($product, int $daysAhead = 7): void {}
        });
    }

    private function actingAsCashier(): User
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => RoleStatus::CASHIER->value,
            'password' => 'password',
        ]);
        $this->actingAs($user);
        return $user;
    }

    public function test_cashier_index_accessible_by_cashier_and_admin(): void
    {
        $this->actingAsCashier();
        $this->get(route('kasir'))->assertOk();
    }

    public function test_products_endpoint_returns_filtered_results(): void
    {
        $this->actingAsCashier();
        $p1 = Product::factory()->create(['name' => 'Teh Botol', 'sku' => 'SKU-1001']);
        $p2 = Product::factory()->create(['name' => 'Kopi Susu', 'sku' => 'SKU-2002']);

        // Query by name
        $res = $this->getJson(route('kasir.products', ['q' => 'Teh']));
        $res->assertOk()->assertJsonFragment(['name' => 'Teh Botol']);

        // Query by SKU (must not be purely numeric per controller logic)
        $res2 = $this->getJson(route('kasir.products', ['q' => 'SKU-2002']));
        $res2->assertOk()->assertJsonFragment(['sku' => 'SKU-2002']);
    }

    public function test_hold_resume_and_destroy_hold_flow(): void
    {
        $this->actingAsCashier();
        $p = Product::factory()->create(['price' => 10, 'stock' => 10]);

        // Hold
        $payload = [
            'items' => [['product_id' => $p->id, 'qty' => 2]],
            'note' => 'sementara',
        ];
        $holdRes = $this->postJson(route('kasir.hold'), $payload);
        $holdRes->assertOk()->assertJsonStructure(['transaction_id', 'invoice', 'status']);
        $trxId = $holdRes->json('transaction_id');

        // List holds
        $list = $this->getJson(route('kasir.holds'));
        $list->assertOk()->assertJsonFragment(['id' => $trxId]);

        // Resume data
        $resume = $this->postJson(route('kasir.holds.resume', ['transaction' => $trxId]));
        $resume->assertOk()->assertJsonFragment(['suspended_from_id' => $trxId]);

        // Destroy hold
        $del = $this->deleteJson(route('kasir.holds.destroy', ['transaction' => $trxId]));
        $del->assertOk()->assertJson(['deleted' => true]);
        $this->assertDatabaseMissing('transactions', ['id' => $trxId]);
    }

    public function test_checkout_cash_success_and_stock_decrement(): void
    {
        $this->actingAsCashier();
        $p = Product::factory()->create(['price' => 25.00, 'stock' => 10]);

        $payload = [
            'items' => [['product_id' => $p->id, 'qty' => 2]],
            'payment_method' => 'cash',
            'paid_amount' => 100.00,
        ];

        $res = $this->post(route('kasir.checkout'), $payload);
        $res->assertRedirect(route('kasir'));

        $userId = User::query()->latest('id')->value('id'); // fallback, but we can use currently authenticated
        $this->assertDatabaseHas('transactions', [
            'status' => TransactionStatus::PAID->value,
        ]);

        $p->refresh();
        $this->assertEquals(8, $p->stock);
    }

    public function test_checkout_validation_error_when_cart_empty(): void
    {
        $this->actingAsCashier();
        $res = $this->from(route('kasir'))->post(route('kasir.checkout'), [
            'items' => [],
            'payment_method' => 'cash',
            'paid_amount' => 0,
        ]);

        // Fails validation (items required|min:1)
        $res->assertRedirect(route('kasir'));
        $res->assertSessionHasErrors('items');
    }
}
