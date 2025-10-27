<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'max:100'],
            'currency' => ['required', 'string', 'alpha', 'size:3'],
            'discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'store_address' => ['nullable', 'string', 'max:255'],
            'store_phone' => ['nullable', 'string', 'max:30'],
            'receipt_format' => ['nullable', 'string', 'max:100', 'regex:/^[-_A-Za-z0-9{}:]+$/', 'regex:/^(?=.*\{(YYYY|YY)\}|.*\{MM\}|.*\{DD\}|.*\{SEQ:\d+\}).*$/'],
            'store_logo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return [
            'store_name' => 'Nama Toko',
            'currency' => 'Mata Uang',
            'discount_percent' => 'Diskon (%)',
            'tax_percent' => 'Pajak (%)',
            'store_address' => 'Alamat Toko',
            'store_phone' => 'No. Telepon',
            'receipt_format' => 'Format Struk',
            'store_logo' => 'Logo Toko',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'max' => ':attribute maksimal :max karakter.',
            'size' => ':attribute harus :size karakter.',
            'alpha' => ':attribute hanya boleh berisi huruf.',
            'numeric' => ':attribute harus berupa angka.',
            'min' => ':attribute minimal :min.',
            'max.numeric' => ':attribute maksimal :max.',
            'image' => ':attribute harus berupa gambar.',
            'mimes' => ':attribute harus berformat: :values.',
            'regex' => ':attribute mengandung karakter/placeholder yang tidak valid.',
        ];
    }
}
