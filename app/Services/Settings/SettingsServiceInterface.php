<?php

namespace App\Services\Settings;

use App\Models\Setting;

interface SettingsServiceInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value, ?string $group = null, ?string $description = null): Setting;

    public function discountPercent(): float;
    public function taxPercent(): float;
    public function currency(): string;
    public function storeName(): string;
    public function storeAddress(): string;
    public function storePhone(): string;
    public function storeLogoPath(): ?string;
    public function receiptNumberFormat(): string;
}
