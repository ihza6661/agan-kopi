@extends('layouts.app')

@section('title', 'Pengaturan')

@section('content')
    <section class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h3 mb-0">Pengaturan</h1>
        </div>

        @if (session('success'))
            <div class="alert alert-success" role="alert" aria-live="polite">
                {{ session('success') }}
            </div>
        @endif

        <div class="row g-3">
            <div class="col-12 col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form action="{{ route('pengaturan.update') }}" method="POST" class="row g-3" novalidate
                            enctype="multipart/form-data"
                            data-default-store-name="{{ $appStoreName ?? config('app.name', 'POS') }}"
                            data-default-currency="{{ $appCurrency ?? 'IDR' }}"
                            data-default-discount="{{ $appDiscountPercent ?? 0 }}"
                            data-default-tax="{{ $appTaxPercent ?? 0 }}" data-default-address="{{ $appStoreAddress ?? '' }}"
                            data-default-phone="{{ $appStorePhone ?? '' }}"
                            data-default-receipt="{{ $appReceiptFormat ?? 'INV-{YYYY}{MM}{DD}-{SEQ:6}' }}">
                            @csrf
                            @method('PUT')

                            <div class="col-12">
                                <label for="store_name" class="form-label">Nama Toko</label>
                                <input type="text" id="store_name" name="store_name"
                                    value="{{ old('store_name', $store_name ?? $appStoreName) }}"
                                    class="form-control @error('store_name') is-invalid @enderror" required maxlength="100">
                                @error('store_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="currency" class="form-label">Mata Uang (Kode 3 huruf)</label>
                                <div class="input-group">
                                    <input type="text" id="currency" name="currency"
                                        value="{{ old('currency', $currency ?? 'IDR') }}"
                                        class="form-control @error('currency') is-invalid @enderror" required maxlength="3"
                                        aria-describedby="currencyPreset">
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false" id="currencyPreset">Preset</button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @foreach (['IDR', 'USD', 'EUR', 'SGD', 'JPY', 'AUD', 'GBP'] as $cur)
                                            <li><button class="dropdown-item" type="button"
                                                    onclick="document.getElementById('currency').value='{{ $cur }}'">{{ $cur }}</button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                @error('currency')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-3">
                                <label for="discount_percent" class="form-label">Diskon (%)</label>
                                <input type="number" step="0.01" min="0" max="100" id="discount_percent"
                                    name="discount_percent" value="{{ old('discount_percent', $discount_percent ?? 0) }}"
                                    class="form-control @error('discount_percent') is-invalid @enderror" required>
                                @error('discount_percent')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-3">
                                <label for="tax_percent" class="form-label">Pajak (%)</label>
                                <input type="number" step="0.01" min="0" max="100" id="tax_percent"
                                    name="tax_percent" value="{{ old('tax_percent', $tax_percent ?? 0) }}"
                                    class="form-control @error('tax_percent') is-invalid @enderror" required>
                                @error('tax_percent')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="store_address" class="form-label">Alamat Toko</label>
                                <textarea id="store_address" name="store_address" rows="2"
                                    class="form-control @error('store_address') is-invalid @enderror" placeholder="Alamat lengkap toko">{{ old('store_address', $store_address ?? ($appStoreAddress ?? '')) }}</textarea>
                                @error('store_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="store_phone" class="form-label">No. Telepon</label>
                                <input type="text" id="store_phone" name="store_phone"
                                    value="{{ old('store_phone', $store_phone ?? ($appStorePhone ?? '')) }}"
                                    class="form-control @error('store_phone') is-invalid @enderror"
                                    placeholder="0812xxxxxxx">
                                @error('store_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="receipt_format" class="form-label">Format Penomoran Struk</label>
                                <input type="text" id="receipt_format" name="receipt_format"
                                    value="{{ old('receipt_format', $receipt_format ?? ($appReceiptFormat ?? 'INV-{YYYY}{MM}{DD}-{SEQ:6}')) }}"
                                    class="form-control @error('receipt_format') is-invalid @enderror"
                                    placeholder="INV-{YYYY}{MM}{DD}-{SEQ:6}">
                                <div class="form-text">Gunakan placeholder: {YYYY}, {YY}, {MM}, {DD}, {SEQ:n}.</div>
                                <div class="form-text">Contoh nomor:</div>
                                <ul class="small" id="receiptPreviewList" aria-live="polite" aria-busy="false">
                                    <li class="text-muted">(memuat...)</li>
                                </ul>
                                @error('receipt_format')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="store_logo" class="form-label">Logo Toko</label>
                                <input class="form-control @error('store_logo') is-invalid @enderror" type="file"
                                    id="store_logo" name="store_logo" accept="image/*">
                                @error('store_logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @if (!empty($appStoreLogoPath))
                                    <div class="mt-2">
                                        <img src="{{ asset($appStoreLogoPath) }}" alt="Logo saat ini" width="64"
                                            height="64" class="border rounded p-1 bg-white" />
                                    </div>
                                @endif
                            </div>

                            <div class="col-12 d-flex justify-content-between gap-2">
                                <button type="button" class="btn btn-outline-secondary" id="btnResetDefaults">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset ke Default
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Simpan Pengaturan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4 settings-aside">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 text-muted">Informasi</h2>
                        <p class="mb-2">Pengaturan ini mempengaruhi tampilan nama toko, mata uang, serta perhitungan
                            diskon dan pajak di sistem POS.</p>
                        <ul class="mb-0">
                            <li>Nama Toko akan tampil di header dan judul halaman.</li>
                            <li>Mata Uang (contoh: IDR, USD).</li>
                            <li>Diskon dan Pajak dalam persen, dapat menggunakan desimal.</li>
                        </ul>
                    </div>
                </div>
                <div class="card h-100 shadow-sm mt-3 receipt-sticky">
                    <div class="card-body">
                        <h2 class="h6 text-muted d-flex justify-content-between align-items-center">
                            <span>Preview Struk</span>
                        </h2>
                        <div id="receiptPreviewContainer" class="receipt border rounded p-3 bg-white"
                            style="max-width: 320px;">
                            <div class="text-center">
                                <img id="prev_logo"
                                    src="{{ $appStoreLogoPath ? asset($appStoreLogoPath) : asset('assets/images/logo.webp') }}"
                                    alt="Logo" width="48" height="48" class="mb-2" />
                                <div id="prev_store_name" class="fw-bold">{{ $store_name ?? $appStoreName }}</div>
                                <div id="prev_store_address" class="muted">{{ $store_address ?? $appStoreAddress }}
                                </div>
                                <div id="prev_store_phone" class="muted">{{ $store_phone ?? $appStorePhone }}</div>
                            </div>
                            <div class="hr"></div>
                            <div class="d-flex flex-column">
                                <div class="d-flex justify-content-between"><span class="muted">No</span><span
                                        id="prev_no">-</span></div>
                                <div class="d-flex justify-content-between"><span class="muted">Tanggal</span><span
                                        id="prev_date"></span></div>
                                <div class="d-flex justify-content-between"><span class="muted">Kasir</span><span
                                        id="prev_cashier">{{ auth()->user()->name ?? 'Kasir' }}</span></div>
                            </div>
                            <div class="hr"></div>
                            <table class="table table-sm mb-2">
                                <colgroup>
                                    <col style="width: 50%">
                                    <col style="width: 12%">
                                    <col style="width: 19%">
                                    <col style="width: 19%">
                                </colgroup>
                                <thead>
                                    <tr class="small">
                                        <th>Item</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Harga</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="prev_items" class="small"></tbody>
                            </table>
                            <div class="hr"></div>
                            <div class="small">
                                <div class="d-flex justify-content-between"><span>Subtotal</span><span
                                        id="prev_subtotal">-</span></div>
                                <div class="d-flex justify-content-between"><span>Diskon (<span
                                            id="prev_disc_p">0</span>%)</span><span id="prev_discount">-</span></div>
                                <div class="d-flex justify-content-between"><span>Pajak (<span
                                            id="prev_tax_p">0</span>%)</span><span id="prev_tax">-</span></div>
                                <div class="d-flex justify-content-between fw-bold border-top pt-1"><span>Total</span><span
                                        id="prev_total">-</span></div>
                            </div>
                            <div class="hr"></div>
                            <div class="small">
                                <div class="d-flex justify-content-between"><span>Metode</span><span>Tunai</span></div>
                                <div class="d-flex justify-content-between"><span>Bayar</span><span
                                        id="prev_paid">-</span></div>
                                <div class="d-flex justify-content-between"><span>Kembali</span><span
                                        id="prev_change">-</span></div>
                            </div>
                            <div class="hr"></div>
                            <div class="text-center muted">Simpan struk sebagai bukti pembelian</div>
                            <div class="text-center small">Terima kasih telah berbelanja!</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @push('script')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const currencyInput = document.getElementById('currency');
                if (currencyInput) {
                    currencyInput.addEventListener('input', () => {
                        currencyInput.value = currencyInput.value.replace(/[^a-zA-Z]/g, '').toUpperCase().slice(
                            0, 3);
                    });
                }

                const btnReset = document.getElementById('btnResetDefaults');
                if (btnReset) {
                    btnReset.addEventListener('click', () => {
                        const form = btnReset.closest('form');
                        const get = (k, d = '') => form?.dataset[k] ?? d;
                        const setVal = (id, val) => {
                            const el = document.getElementById(id);
                            if (el) el.value = val;
                        };
                        setVal('store_name', get('defaultStoreName'));
                        setVal('currency', get('defaultCurrency', 'IDR'));
                        setVal('discount_percent', get('defaultDiscount', '0'));
                        setVal('tax_percent', get('defaultTax', '0'));
                        setVal('store_address', get('defaultAddress', ''));
                        setVal('store_phone', get('defaultPhone', ''));
                        setVal('receipt_format', get('defaultReceipt', 'INV-{YYYY}{MM}{DD}-{SEQ:6}'));
                        updateReceiptPreview();
                        updateReceiptPreviewHtml();
                    });
                }

                const receiptInput = document.getElementById('receipt_format');
                const previewList = document.getElementById('receiptPreviewList');
                const updateReceiptPreview = () => {
                    if (!receiptInput || !previewList) return;
                    previewList.setAttribute('aria-busy', 'true');
                    previewList.innerHTML = '<li class="text-muted">(memuat...)</li>';
                    const url = new URL(@json(route('pengaturan.preview')));
                    url.searchParams.set('format', receiptInput.value);
                    url.searchParams.set('count', '5');
                    fetch(url.toString())
                        .then(r => r.json())
                        .then(d => {
                            previewList.innerHTML = '';
                            if (Array.isArray(d.examples) && d.examples.length) {
                                d.examples.forEach((ex) => {
                                    const li = document.createElement('li');
                                    li.textContent = ex;
                                    previewList.appendChild(li);
                                });
                                const first = d.examples[0] || '-';
                                const prevNo = document.getElementById('prev_no');
                                if (prevNo) prevNo.textContent = first;
                            } else {
                                const li = document.createElement('li');
                                li.textContent = '-';
                                previewList.appendChild(li);
                                const prevNo = document.getElementById('prev_no');
                                if (prevNo) prevNo.textContent = '-';
                            }
                        })
                        .catch(() => {
                            previewList.innerHTML = '<li>-</li>';
                            const prevNo = document.getElementById('prev_no');
                            if (prevNo) prevNo.textContent = '-';
                        })
                        .finally(() => {
                            previewList.setAttribute('aria-busy', 'false');
                        });
                };
                if (receiptInput && previewList) {
                    receiptInput.addEventListener('input', () => {
                        updateReceiptPreview();
                        updateReceiptPreviewHtml();
                    });
                    updateReceiptPreview();
                }

                // Preview struk lengkap (header + items)
                const fmtMoney = (n) => Number(n).toLocaleString('id-ID');
                const sampleItems = [{
                        name: 'Indomie Goreng',
                        qty: 2,
                        price: 4500
                    },
                    {
                        name: 'Teh Botol Sosro 350ml',
                        qty: 1,
                        price: 6000
                    },
                    {
                        name: 'Roti Tawar Mini',
                        qty: 1,
                        price: 8000
                    },
                ];
                const el = (id) => document.getElementById(id);
                const updateReceiptPreviewHtml = () => {
                    const currency = (el('currency')?.value || 'IDR').toUpperCase();
                    const discP = parseFloat(el('discount_percent')?.value || '0') || 0;
                    const taxP = parseFloat(el('tax_percent')?.value || '0') || 0;
                    const storeName = el('store_name')?.value || '';
                    const storeAddr = el('store_address')?.value || '';
                    const storePhone = el('store_phone')?.value || '';

                    if (el('prev_store_name')) el('prev_store_name').textContent = storeName;
                    if (el('prev_store_address')) el('prev_store_address').textContent = storeAddr;
                    if (el('prev_store_phone')) el('prev_store_phone').textContent = storePhone;
                    if (el('prev_date')) el('prev_date').textContent = new Date().toLocaleString('id-ID');

                    // Items
                    const tbody = el('prev_items');
                    if (tbody) {
                        tbody.innerHTML = '';
                        let subtotal = 0;
                        sampleItems.forEach((it) => {
                            const line = it.qty * it.price;
                            subtotal += line;
                            const tr = document.createElement('tr');
                            tr.innerHTML =
                                `<td>${it.name}</td><td class="text-end">${it.qty}</td><td class="text-end">${fmtMoney(it.price)}</td><td class="text-end">${fmtMoney(line)}</td>`;
                            tbody.appendChild(tr);
                        });

                        const disc = subtotal * (discP / 100);
                        const taxedBase = subtotal - disc;
                        const tax = taxedBase * (taxP / 100);
                        const total = taxedBase + tax;
                        const paid = total;
                        const change = paid - total;

                        if (el('prev_disc_p')) el('prev_disc_p').textContent = discP.toString();
                        if (el('prev_tax_p')) el('prev_tax_p').textContent = taxP.toString();
                        if (el('prev_subtotal')) el('prev_subtotal').textContent = fmtMoney(subtotal);
                        if (el('prev_discount')) el('prev_discount').textContent = fmtMoney(disc);
                        if (el('prev_tax')) el('prev_tax').textContent = fmtMoney(tax);
                        if (el('prev_total')) el('prev_total').textContent = fmtMoney(total);
                        if (el('prev_paid')) el('prev_paid').textContent = fmtMoney(paid);
                        if (el('prev_change')) el('prev_change').textContent = fmtMoney(change);
                    }

                    const list = document.querySelectorAll('#receiptPreviewList li');
                    if (list && list.length) {
                        const first = list[0]?.textContent || '-';
                        if (el('prev_no')) el('prev_no').textContent = first;
                    }
                };

                // Update saat input lain berubah
                ['store_name', 'store_address', 'store_phone', 'currency', 'discount_percent', 'tax_percent'].forEach((
                    id) => {
                    const x = el(id);
                    if (x) x.addEventListener('input', updateReceiptPreviewHtml);
                });
                updateReceiptPreviewHtml();

                const logoInput = document.getElementById('store_logo');
                if (logoInput) {
                    logoInput.addEventListener('change', (e) => {
                        const file = e.target.files?.[0];
                        if (file) {
                            const url = URL.createObjectURL(file);
                            const img = document.getElementById('prev_logo');
                            if (img) img.src = url;
                        }
                    });
                }
            });
        </script>
        @push('css')
            <style>
                .receipt {
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
                    font-size: 12px;
                    color: #333;
                }

                .receipt .muted {
                    color: #6c757d;
                    font-size: 11px;
                }

                .receipt .hr {
                    border-top: 1px dashed #dee2e6;
                    margin: .5rem 0;
                }

                .receipt table {
                    width: 100%;
                    table-layout: fixed;
                    border-collapse: collapse;
                }

                .receipt th,
                .receipt td {
                    padding: 2px 0;
                    border: 0;
                    vertical-align: top;
                }

                .receipt thead th {
                    border-bottom: 1px solid #e9ecef;
                }

                .receipt .text-end {
                    text-align: right;
                }

                @media (min-width: 992px) {
                    .settings-aside {
                        align-self: flex-start;
                    }

                    .receipt-sticky {
                        position: sticky;
                        top: 80px;
                    }
                }
            </style>
        @endpush
    @endpush
@endsection
