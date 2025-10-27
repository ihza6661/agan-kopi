<?php

namespace App\Services\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

interface AuthServiceInterface
{
    public function login(string $email, string $password, bool $remember = false): bool;

    public function logout(): void;

    public function user(): ?Authenticatable;
}
