<?php

namespace Tests\Feature;

use App\Enums\RoleStatus;
use App\Models\Category;
use App\Models\User;
use App\Services\ActivityLog\ActivityLoggerInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
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

        $res = $this->get(route('kategori.index'));
        $res->assertOk();
    }

    public function test_non_admin_forbidden(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne(['role' => RoleStatus::CASHIER->value]);
        $this->actingAs($user, 'web');

        $this->get(route('kategori.index'))->assertForbidden();
    }

    public function test_store_category(): void
    {
        $this->mockLogger();
        $this->actingAsAdmin();

        $payload = [
            'name' => 'Perlengkapan',
            'description' => 'Peralatan toko',
        ];

        $res = $this->post(route('kategori.store'), $payload);

        $res->assertRedirect(route('kategori.index'));
        $this->assertDatabaseHas('categories', [
            'name' => 'Perlengkapan',
            'description' => 'Peralatan toko',
        ]);
    }

    public function test_update_category(): void
    {
        $this->mockLogger();
        $this->actingAsAdmin();
        $cat = Category::factory()->create([
            'name' => 'A',
        ]);

        $res = $this->put(route('kategori.update', $cat), [
            'name' => 'B',
            'description' => 'desc',
        ]);

        $res->assertRedirect(route('kategori.index'));
        $this->assertDatabaseHas('categories', [
            'id' => $cat->id,
            'name' => 'B',
            'description' => 'desc',
        ]);
    }

    public function test_delete_category(): void
    {
        $this->mockLogger();
        $this->actingAsAdmin();
        $cat = Category::factory()->create();

        $res = $this->delete(route('kategori.destroy', $cat));

        $res->assertRedirect(route('kategori.index'));
        $this->assertDatabaseMissing('categories', ['id' => $cat->id]);
    }

    public function test_validation_on_store(): void
    {
        $this->mockLogger();
        $this->actingAsAdmin();

        $res = $this->post(route('kategori.store'), ['name' => '']);
        $res->assertSessionHasErrors('name');
    }

    public function test_validation_on_update_unique_rule(): void
    {
        $this->mockLogger();
        $this->actingAsAdmin();
        Category::factory()->create(['name' => 'Satu']);
        $cat = Category::factory()->create(['name' => 'Dua']);

        $res = $this->put(route('kategori.update', $cat), [
            'name' => 'Satu', // duplicate
            'description' => null,
        ]);

        $res->assertSessionHasErrors('name');
    }
}
