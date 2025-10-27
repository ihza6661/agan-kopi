<?php

namespace App\Http\Requests\Cashier;

use Illuminate\Foundation\Http\FormRequest;

class HoldRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $items = $this->input('items');
        if (is_string($items)) {
            try {
                $decoded = json_decode($items, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $this->merge(['items' => $decoded]);
                }
            } catch (\Throwable $e) {
            }
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
            'suspended_from_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'items' => 'Daftar item',
            'items.*.product_id' => 'Produk',
            'items.*.qty' => 'Jumlah',
            'note' => 'Catatan',
            'suspended_from_id' => 'Transaksi tertunda',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'array' => ':attribute harus berupa daftar.',
            'min' => ':attribute minimal :min.',
            'integer' => ':attribute harus berupa angka bulat.',
            'numeric' => ':attribute harus berupa angka.',
            'max' => ':attribute maksimal :max karakter.',
        ];
    }
}
