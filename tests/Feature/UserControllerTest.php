<?php

namespace Tests\Feature;

use App\Enums\RoleStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
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

    public function test_admin_can_view_index(): void
    {
        $this->actingAsAdmin();

        $res = $this->get(route('pengguna.index'));
        $res->assertOk();
    }

    public function test_non_admin_forbidden(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne(['role' => RoleStatus::CASHIER->value]);
        $this->actingAs($user, 'web');

        $this->get(route('pengguna.index'))->assertForbidden();
    }

    public function test_store_user(): void
    {
        $this->actingAsAdmin();

        $payload = [
            'name' => 'Test User',
            'email' => 'test@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'cashier',
        ];

        $res = $this->post(route('pengguna.store'), $payload);

        $res->assertRedirect(route('pengguna.index'));
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@gmail.com',
            'role' => 'cashier',
        ]);
    }

    public function test_update_user(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@gmail.com',
            'role' => RoleStatus::CASHIER->value,
        ]);

        $res = $this->put(route('pengguna.update', $user), [
            'name' => 'New Name',
            'email' => 'new@gmail.com',
            'role' => 'cashier',
        ]);

        $res->assertRedirect(route('pengguna.index'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@gmail.com',
        ]);
    }

    public function test_delete_user(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create(['role' => RoleStatus::CASHIER->value]);

        $res = $this->delete(route('pengguna.destroy', $user));

        $res->assertRedirect(route('pengguna.index'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_cannot_delete_self(): void
    {
        $admin = $this->actingAsAdmin();

        $res = $this->delete(route('pengguna.destroy', $admin));

        $res->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_validation_on_store(): void
    {
        $this->actingAsAdmin();

        $res = $this->post(route('pengguna.store'), ['name' => '']);
        $res->assertSessionHasErrors(['name', 'email', 'password', 'role']);
    }

    public function test_validation_unique_email(): void
    {
        $this->actingAsAdmin();
        User::factory()->create(['email' => 'duplicate@gmail.com']);

        $res = $this->post(route('pengguna.store'), [
            'name' => 'Test User',
            'email' => 'duplicate@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'cashier',
        ]);

        $res->assertSessionHasErrors('email');
    }
}
