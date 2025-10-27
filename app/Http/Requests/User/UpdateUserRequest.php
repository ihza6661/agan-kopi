<?php

namespace App\Http\Requests\User;

use App\Enums\RoleStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $roles = array_map(fn($e) => $e->value, RoleStatus::cases());

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => ['nullable', Password::min(8), 'confirmed'],
            'role' => ['required', Rule::in($roles)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal :max karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.min' => 'Kata sandi minimal :min karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'role.required' => 'Peran wajib dipilih.',
            'role.in' => 'Peran tidak valid.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $target = $this->route('user');
            if (!$target) {
                return;
            }

            $isSelf = (int) ($target->id) === (int) (Auth::id());
            $roleInput = (string) $this->input('role');

            if ($isSelf && $target->role === RoleStatus::ADMIN->value && $roleInput === RoleStatus::CASHIER->value) {
                $adminCount = User::query()->where('role', RoleStatus::ADMIN->value)->count();
                if ($adminCount <= 1) {
                    $validator->errors()->add('role', 'Anda adalah satu-satunya admin. Tidak dapat mengubah peran menjadi kasir.');
                }
            }
        });
    }
}
