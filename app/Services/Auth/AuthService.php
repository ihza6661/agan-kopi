<?php

namespace App\Services\Auth;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\StatefulGuard;

class AuthService implements AuthServiceInterface
{
    protected StatefulGuard $guard;

    public function __construct(AuthManager $auth)
    {
        $this->guard = $auth->guard();
    }

    public function login(string $email, string $password, bool $remember = false): bool
    {
        $ok = $this->guard->attempt(
            ['email' => $email, 'password' => $password],
            $remember
        );

        if ($ok) {
            session()->regenerate();
        }

        return $ok;
    }

    public function logout(): void
    {
        $this->guard->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    public function user(): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        return $this->guard->user();
    }
}