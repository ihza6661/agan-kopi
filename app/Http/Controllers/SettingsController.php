<?php

namespace App\Http\Controllers;

use App\Enums\RoleStatus;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Services\Settings\SettingsServiceInterface;
use App\Services\ActivityLog\ActivityLoggerInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsServiceInterface $settings,
        private readonly ActivityLoggerInterface $logger
    ) {
        $this->middleware(function ($request, $next) {
            if (!Auth::check() || Auth::user()->role !== RoleStatus::ADMIN->value) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
            }
            return $next($request);
        });
    }

    public function index(): Response
    {
        return Inertia::render('Settings/Index', [
            'store_name'       => $this->settings->storeName(),
            'currency'         => $this->settings->currency(),
            'discount_percent' => $this->settings->discountPercent(),
            'tax_percent'      => $this->settings->taxPercent(),
            'store_address'    => $this->settings->storeAddress(),
            'store_phone'      => $this->settings->storePhone(),
            'receipt_format'   => $this->settings->receiptNumberFormat(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $before = [
            'store_name' => $this->settings->storeName(),
            'currency' => $this->settings->currency(),
            'discount_percent' => $this->settings->discountPercent(),
            'tax_percent' => $this->settings->taxPercent(),
            'store_address' => $this->settings->storeAddress(),
            'store_phone' => $this->settings->storePhone(),
            'receipt_format' => $this->settings->receiptNumberFormat(),
        ];

        $this->settings->set('store.name', $validated['store_name'], 'store', 'Nama Toko');
        $this->settings->set('store.address', $validated['store_address'] ?? '', 'store', 'Alamat Toko');
        
        $phone = $validated['store_phone'] ?? '';
        if ($phone !== '') {
            $phone = preg_replace('/[^0-9+]/', '', $phone);
            $phone = ltrim($phone, '+');
            $phone = ($validated['store_phone'][0] === '+') ? ('+' . $phone) : $phone;
        }

        $this->settings->set('store.phone', $phone, 'store', 'No. Telepon Toko');
        $this->settings->set('pos.currency', strtoupper($validated['currency']), 'pos', 'Mata Uang');
        $this->settings->set('pos.discount_percent', (float) $validated['discount_percent'], 'pos', 'Diskon Global (%)');
        $this->settings->set('pos.tax_percent', (float) $validated['tax_percent'], 'pos', 'Pajak (%)');
        $this->settings->set('pos.receipt_format', $validated['receipt_format'] ?? 'INV-{YYYY}{MM}{DD}-{SEQ:6}', 'pos', 'Format Penomoran Struk');

        if ($request->hasFile('store_logo')) {
            $file = $request->file('store_logo');
            $path = $file->store('assets/images', 'public');
            $this->settings->set('store.logo_path', 'storage/' . $path, 'store', 'Path Logo Toko');
        }

        $after = [
            'store_name' => $this->settings->storeName(),
            'currency' => $this->settings->currency(),
            'discount_percent' => $this->settings->discountPercent(),
            'tax_percent' => $this->settings->taxPercent(),
            'store_address' => $this->settings->storeAddress(),
            'store_phone' => $this->settings->storePhone(),
            'receipt_format' => $this->settings->receiptNumberFormat(),
        ];
        $this->logger->log('Ubah Pengaturan', 'Mengubah pengaturan aplikasi', ['before' => $before, 'after' => $after]);

        return redirect()->route('pengaturan.index')->with('success', 'Pengaturan berhasil disimpan.');
    }

    public function previewReceipt(Request $request)
    {
        $format = (string) $request->query('format', $this->settings->receiptNumberFormat());
        $count = max(1, min(20, (int) $request->query('count', 5)));
        $start = max(1, (int) $request->query('start', 1));
        $seqWidth = $this->extractSeqWidth($format) ?? 6;
        $examples = [];
        for ($i = 0; $i < $count; $i++) {
            $seq = $start + $i;
            $examples[] = $this->generateReceiptFromFormat($format, $seq, $seqWidth);
        }
        return response()->json([
            'format' => $format,
            'examples' => $examples,
        ]);
    }

    private function extractSeqWidth(string $format): ?int
    {
        if (preg_match('/\{SEQ:(\d{1,9})\}/', $format, $m)) {
            return (int) $m[1];
        }
        return null;
    }

    private function generateReceiptFromFormat(string $format, int $seq, int $seqWidth = 6): string
    {
        $now = Carbon::now();
        $map = [
            '{YYYY}' => $now->format('Y'),
            '{YY}' => $now->format('y'),
            '{MM}' => $now->format('m'),
            '{DD}' => $now->format('d'),
        ];

        $result = strtr($format, $map);
        $seqPad = str_pad((string) $seq, $seqWidth, '0', STR_PAD_LEFT);
        $result = preg_replace('/\{SEQ:\d{1,9}\}/', $seqPad, $result);
        
        return $result;
    }
}

