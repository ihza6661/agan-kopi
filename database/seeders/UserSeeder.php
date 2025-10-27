<?php

namespace Database\Seeders;

use App\Enums\RoleStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin',
                'email' => 'admin@admin.com',
                'password' => Hash::make('password'),
                'role' => RoleStatus::ADMIN->value,
            ],
            [
                'name' => 'Kasir',
                'email' => 'kasir@kasir.com',
                'password' => Hash::make('password'),
                'role' => RoleStatus::CASHIER->value,
            ],
        ];
        foreach ($users as $user) {
            \App\Models\User::create($user);
        }
    }
}
