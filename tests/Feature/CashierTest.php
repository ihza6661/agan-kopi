<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\RoleStatus;
use App\Enums\TransactionStatus;
use App\Models\Product;
use App\Models\Shift;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ActivityLog\ActivityLoggerInterface;
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

    private function createActiveShift(User $user): Shift
    {
        return Shift::create([
            'user_id' => $user->id,
            'started_at' => now(),
            'opening_cash' => 0,
        ]);
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
        $user = $this->actingAsCashier();
        $this->createActiveShift($user);
        $p = Product::factory()->create(['price' => 25.00, 'stock' => 10]);

        $payload = [
            'items' => [['product_id' => $p->id, 'qty' => 2]],
            'payment_method' => 'cash',
            'paid_amount' => 100.00,
        ];

        $res = $this->post(route('kasir.checkout'), $payload);
        $res->assertRedirect(route('kasir'));

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

    // ============ QRIS-specific tests ============

    public function test_qris_checkout_creates_pending_transaction(): void
    {
        $user = $this->actingAsCashier();
        $this->createActiveShift($user);
        $p = Product::factory()->create(['price' => 50.00, 'stock' => 5]);

        $payload = [
            'items' => [['product_id' => $p->id, 'qty' => 1]],
            'payment_method' => 'qris',
            'paid_amount' => 0,
        ];

        $res = $this->postJson(route('kasir.checkout'), $payload);
        $res->assertOk()->assertJsonStructure(['transaction_id', 'invoice', 'status']);
        $this->assertEquals('pending', $res->json('status'));

        $this->assertDatabaseHas('transactions', [
            'id' => $res->json('transaction_id'),
            'payment_method' => PaymentMethod::QRIS->value,
            'status' => TransactionStatus::PENDING->value,
        ]);
    }

    public function test_qris_checkout_does_not_deduct_stock(): void
    {
        $user = $this->actingAsCashier();
        $this->createActiveShift($user);
        $originalStock = 10;
        $p = Product::factory()->create(['price' => 30.00, 'stock' => $originalStock]);

        $payload = [
            'items' => [['product_id' => $p->id, 'qty' => 3]],
            'payment_method' => 'qris',
            'paid_amount' => 0,
        ];

        $res = $this->postJson(route('kasir.checkout'), $payload);
        $res->assertOk();

        // Stock should remain unchanged
        $p->refresh();
        $this->assertEquals($originalStock, $p->stock, 'Stock should NOT be deducted on QRIS checkout');
    }

    public function test_confirm_qris_deducts_stock_and_marks_paid(): void
    {
        $user = $this->actingAsCashier();
        $originalStock = 10;
        $qty = 2;
        $p = Product::factory()->create(['price' => 20.00, 'stock' => $originalStock]);

        // Create QRIS transaction
        $trx = Transaction::create([
            'user_id' => $user->id,
            'invoice_number' => 'TEST-QRIS-001',
            'payment_method' => PaymentMethod::QRIS,
            'status' => TransactionStatus::PENDING,
            'subtotal' => 40.00,
            'discount' => 0,
            'tax' => 0,
            'total' => 40.00,
            'amount_paid' => 0,
            'change' => 0,
        ]);

        $trx->details()->create([
            'product_id' => $p->id,
            'price' => 20.00,
            'quantity' => $qty,
            'total' => 40.00,
        ]);

        // Confirm QRIS
        $res = $this->postJson(route('kasir.confirm-qris', ['transaction' => $trx->id]));
        $res->assertOk()->assertJson(['success' => true, 'status' => 'paid']);

        // Verify transaction is now PAID
        $trx->refresh();
        $this->assertEquals(TransactionStatus::PAID, $trx->status);
        $this->assertNotNull($trx->confirmed_by);
        $this->assertNotNull($trx->confirmed_at);
        $this->assertEquals($user->id, $trx->confirmed_by);

        // Verify stock was deducted
        $p->refresh();
        $this->assertEquals($originalStock - $qty, $p->stock, 'Stock should be deducted after QRIS confirmation');
    }

    public function test_confirm_qris_records_confirmation_metadata(): void
    {
        $user = $this->actingAsCashier();
        $p = Product::factory()->create(['stock' => 10]);

        $trx = Transaction::create([
            'user_id' => $user->id,
            'invoice_number' => 'TEST-QRIS-META',
            'payment_method' => PaymentMethod::QRIS,
            'status' => TransactionStatus::PENDING,
            'subtotal' => 10.00,
            'discount' => 0,
            'tax' => 0,
            'total' => 10.00,
            'amount_paid' => 0,
            'change' => 0,
        ]);
        $trx->details()->create([
            'product_id' => $p->id,
            'price' => 10.00,
            'quantity' => 1,
            'total' => 10.00,
        ]);

        $res = $this->postJson(route('kasir.confirm-qris', ['transaction' => $trx->id]));
        $res->assertOk();

        $trx->refresh();
        $this->assertEquals($user->id, $trx->confirmed_by);
        $this->assertNotNull($trx->confirmed_at);
        // Just verify confirmed_at is a valid timestamp close to now
        $this->assertTrue($trx->confirmed_at->diffInMinutes(now()) < 1);
    }

    public function test_confirm_qris_blocked_for_non_qris_transaction(): void
    {
        $user = $this->actingAsCashier();

        // Create a CASH transaction (not QRIS)
        $trx = Transaction::create([
            'user_id' => $user->id,
            'invoice_number' => 'TEST-CASH-001',
            'payment_method' => PaymentMethod::CASH,
            'status' => TransactionStatus::PAID,
            'subtotal' => 50.00,
            'discount' => 0,
            'tax' => 0,
            'total' => 50.00,
            'amount_paid' => 50.00,
            'change' => 0,
        ]);

        $res = $this->postJson(route('kasir.confirm-qris', ['transaction' => $trx->id]));
        $res->assertStatus(400);
    }

    public function test_confirm_qris_blocked_for_already_paid_transaction(): void
    {
        $user = $this->actingAsCashier();

        // Create a QRIS transaction that's already PAID
        $trx = Transaction::create([
            'user_id' => $user->id,
            'invoice_number' => 'TEST-QRIS-PAID',
            'payment_method' => PaymentMethod::QRIS,
            'status' => TransactionStatus::PAID,
            'subtotal' => 30.00,
            'discount' => 0,
            'tax' => 0,
            'total' => 30.00,
            'amount_paid' => 30.00,
            'change' => 0,
            'confirmed_by' => $user->id,
            'confirmed_at' => now()->subMinutes(5),
        ]);

        $res = $this->postJson(route('kasir.confirm-qris', ['transaction' => $trx->id]));
        $res->assertStatus(400); // Status is not PENDING
    }

    public function test_double_confirmation_blocked(): void
    {
        $user = $this->actingAsCashier();
        $p = Product::factory()->create(['stock' => 10]);

        $trx = Transaction::create([
            'user_id' => $user->id,
            'invoice_number' => 'TEST-QRIS-DOUBLE',
            'payment_method' => PaymentMethod::QRIS,
            'status' => TransactionStatus::PENDING,
            'subtotal' => 15.00,
            'discount' => 0,
            'tax' => 0,
            'total' => 15.00,
            'amount_paid' => 0,
            'change' => 0,
        ]);
        $trx->details()->create([
            'product_id' => $p->id,
            'price' => 15.00,
            'quantity' => 1,
            'total' => 15.00,
        ]);

        // First confirmation should succeed
        $res1 = $this->postJson(route('kasir.confirm-qris', ['transaction' => $trx->id]));
        $res1->assertOk();

        // Second confirmation should be blocked (not PENDING anymore)
        $res2 = $this->postJson(route('kasir.confirm-qris', ['transaction' => $trx->id]));
        $res2->assertStatus(400);
    }

    public function test_confirm_qris_blocked_for_unauthorized_user(): void
    {
        $cashier = $this->actingAsCashier();
        $p = Product::factory()->create(['stock' => 10]);

        $trx = Transaction::create([
            'user_id' => $cashier->id,
            'invoice_number' => 'TEST-QRIS-UNAUTH',
            'payment_method' => PaymentMethod::QRIS,
            'status' => TransactionStatus::PENDING,
            'subtotal' => 20.00,
            'discount' => 0,
            'tax' => 0,
            'total' => 20.00,
            'amount_paid' => 0,
            'change' => 0,
        ]);
        $trx->details()->create([
            'product_id' => $p->id,
            'price' => 20.00,
            'quantity' => 1,
            'total' => 20.00,
        ]);

        // Create an unauthenticated request (logged out)
        $this->app['auth']->forgetGuards();
        
        $res = $this->postJson(route('kasir.confirm-qris', ['transaction' => $trx->id]));
        $res->assertStatus(401); // Unauthenticated
    }
}
