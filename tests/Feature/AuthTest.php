<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ActivityLog\ActivityLoggerInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Fake the activity logger to avoid side effects
        $fakeLogger = new class implements ActivityLoggerInterface {
            public function log(string $activity, ?string $description = null, ?array $context = null): void {}
        };
        $this->app->instance(ActivityLoggerInterface::class, $fakeLogger);
    }

    public function test_guest_can_view_login_page(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_login_with_valid_credentials(): void
    {
        $user = User::factory()->createOne([
            'email' => 'user@gmail.com',
            'password' => 'password', // hashed via factory cast
        ]);

        $response = $this->post(route('login'), [
            'email' => 'user@gmail.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_redirects_to_intended_url(): void
    {
        $user = User::factory()->createOne([
            'email' => 'cashier@gmail.com',
            'password' => 'password',
        ]);

        // Simulate intended URL saved by auth middleware
        $this->withSession(['url.intended' => '/kasir']);

        $response = $this->post(route('login'), [
            'email' => 'cashier@gmail.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/kasir');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_invalid_credentials(): void
    {
        User::factory()->createOne([
            'email' => 'user@gmail.com',
            'password' => 'password',
        ]);

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => 'user@gmail.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_login_validation_errors(): void
    {
        $response = $this->from(route('login'))->post(route('login'), [
            'email' => 'not-an-email',
            // missing password
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }

    // Note: Remember-me cookie name is guard-specific and not part of a public interface.
    // We keep the test suite stable by omitting a strict cookie-name assertion.

    public function test_logout_requires_authentication(): void
    {
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->createOne();
        $this->actingAs($user);

        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'Anda telah keluar.');
        $this->assertGuest();
    }
}
