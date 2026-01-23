<?php

namespace Database\Seeders;

use App\Services\Settings\SettingsServiceInterface;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var SettingsServiceInterface $settings */
        $settings = app(SettingsServiceInterface::class);

        $settings->set('store.name', 'POS Mutiara Kasih', 'store', 'Nama toko yang tampil di aplikasi');
        $settings->set('store.address', 'Jl. Contoh Alamat No. 1, Kota', 'store', 'Alamat Toko');
        $settings->set('store.phone', '081234567890', 'store', 'No. Telepon Toko');
        $settings->set('store.logo_path', 'assets/images/logo.jpg', 'store', 'Path Logo Toko');
        $settings->set('pos.currency', 'IDR', 'pos', 'Mata uang transaksi');
        $settings->set('pos.tax_percent', 0, 'pos', 'PPN dalam persen');
        $settings->set('pos.discount_percent', 0, 'pos', 'Diskon default dalam persen');
        $settings->set('pos.receipt_format', 'INV-{YYYY}{MM}{DD}-{SEQ:6}', 'pos', 'Format Penomoran Struk');
    }
}
