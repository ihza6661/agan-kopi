<?php

namespace App\Services\User;

use App\Enums\RoleStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserService implements UserServiceInterface
{
    public function create(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? RoleStatus::CASHIER->value,
        ]);
    }

    public function update(User $user, array $data): User
    {
        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'] ?? $user->role,
        ];

        if (
            isset($data['role'])
            && $user->id === Auth::id()
            && $user->role === RoleStatus::ADMIN->value
            && $data['role'] === RoleStatus::CASHIER->value
        ) {
            $adminCount = User::query()->where('role', RoleStatus::ADMIN->value)->count();
            if ($adminCount <= 1) {
                $payload['role'] = $user->role;
            }
        }

        if (!empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);
        return $user;
    }

    public function delete(User $user): void
    {
        // Check if user has transactions
        if ($user->transactions()->exists()) {
            throw new \Exception('Tidak dapat menghapus user ini karena masih memiliki transaksi terkait.');
        }
        
        $user->delete();
    }

    public function findOrFail(int $id): User
    {
        return User::findOrFail($id);
    }
}
