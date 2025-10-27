@extends('layouts.app')

@section('title', 'Kasir')

@section('content')
    <section class="container-fluid py-4">
        <header class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-cash-stack"></i> Kasir
                </h1>
                <p class="text-muted mb-0">Scan SKU atau cari produk, tambahkan ke keranjang, lalu proses pembayaran.</p>
            </div>
            <div class="text-muted small">
                • Diskon: {{ number_format($discount_percent, 2, ',', '.') }}% • Pajak:
                {{ number_format($tax_percent, 2, ',', '.') }}% • Mata Uang: {{ $currency }}
            </div>
        </header>

        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
        @endif

        {{-- Modal sukses pembayaran (tunai/QRIS) --}}
        <div class="modal fade" id="cashSuccessModal" tabindex="-1" aria-labelledby="cashSuccessLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cashSuccessLabel"><i class="bi bi-receipt-cutoff"></i> Pembayaran
                            Berhasil</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-1">Transaksi <span
                                class="text-uppercase">{{ session('printed_payment_method', 'cash') }}</span> telah berhasil
                            diproses.</p>
                        @if (session('printed_transaction_id'))
                            <p class="text-muted small mb-0">No. Transaksi: <span
                                    class="fw-semibold">{{ session('printed_invoice') }}</span></p>
                        @endif
                        <p class="text-muted small">Anda dapat mencetak struk untuk pelanggan.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                        @if (session('printed_transaction_id'))
                            <a id="btnPrintReceipt" class="btn btn-primary" target="_blank"
                                href="{{ route('transaksi.struk', ['transaction' => session('printed_transaction_id'), 'print' => 1]) }}"><i
                                    class="bi bi-printer"></i> Cetak Struk</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <section class="row g-3">
            <div class="col-12 col-lg-8">
                <section class="card shadow-sm h-100">
                    <div class="card-body">
                        <form id="productSearchForm" class="row g-2" role="search" aria-label="Pencarian produk"
                            onsubmit="return false;">
                            <div class="col-12 col-md-8">
                                <label for="q" class="visually-hidden">Cari produk</label>
                                <div class="w-100 position-relative" id="searchDropdown">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                                        <input id="q" type="search" class="form-control"
                                            placeholder="Scan SKU atau ketik nama produk..." autocomplete="off"
                                            aria-expanded="false" aria-haspopup="listbox">
                                    </div>
                                    <div class="p-3 w-100 position-absolute bg-white border rounded shadow"
                                        id="inlineDropMenu"
                                        style="max-height: 420px; overflow: auto; z-index: 1000; top: 100%; left: 0; margin-top: .25rem; display: none;">
                                        <div id="inlineDropResults" aria-live="polite"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4 d-grid">
                                <button id="btnSearch" type="button" class="btn btn-outline-secondary">
                                    <i class="bi bi-search"></i> Cari
                                </button>
                            </div>
                        </form>



                        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mt-3">
                            <div class="fw-semibold">Keranjang</div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnShowHolds">
                                    <i class="bi bi-inboxes"></i> Tertunda
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-warning" id="btnHold" disabled>
                                    <i class="bi bi-pause-circle"></i> Tunda Transaksi
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="btnClearCart" disabled>
                                    <i class="bi bi-trash"></i> Hapus Semua
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive mt-2">
                            <table class="table align-middle" id="cartTable">
                                <caption>Keranjang</caption>
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th class="text-end" style="width:120px;">Harga</th>
                                        <th class="text-center" style="width:140px;">Qty</th>
                                        <th class="text-end" style="width:140px;">Total</th>
                                        <th class="text-end" style="width:80px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="cartBody"></tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>

            <div class="col-12 col-lg-4">
                <aside class="card shadow-sm h-100" role="complementary">
                    <div class="card-body">
                        <h2 class="h6 text-muted">Ringkasan</h2>
                        <dl class="row small mb-0">
                            <dt class="col-6">Subtotal</dt>
                            <dd class="col-6 text-end" id="sumSubtotal">0</dd>

                            <dt class="col-6">Diskon ({{ number_format($discount_percent, 2, ',', '.') }}%)</dt>
                            <dd class="col-6 text-end" id="sumDiscount">0</dd>

                            <dt class="col-6">Pajak ({{ number_format($tax_percent, 2, ',', '.') }}%)</dt>
                            <dd class="col-6 text-end" id="sumTax">0</dd>

                            <dt class="col-6 fw-bold border-top pt-2">Total</dt>
                            <dd class="col-6 text-end fw-bold border-top pt-2" id="sumTotal">0</dd>
                        </dl>

                        <form id="checkoutForm" action="{{ route('kasir.checkout') }}" method="POST" class="mt-3"
                            novalidate>
                            @csrf
                            <input type="hidden" name="payment_method" id="payment_method" value="cash" />
                            <input type="hidden" name="items" id="items_json" />

                            <fieldset class="mb-3">
                                <label class="form-label">Metode Pembayaran</label>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-primary active" data-method="cash"><i
                                            class="bi bi-cash"></i> Tunai</button>
                                    <button type="button" class="btn btn-outline-secondary" data-method="qris"><i
                                            class="bi bi-qr-code"></i> QRIS</button>
                                </div>
                            </fieldset>

                            <fieldset class="mb-3" id="cashSection">
                                <label for="paid_amount" class="form-label">Jumlah Bayar ({{ $currency }})</label>
                                <input type="text" inputmode="numeric" id="paid_amount" name="paid_amount"
                                    class="form-control" placeholder="Rp 0">
                                <div id="changeDisplay" class="mt-2 fw-semibold"></div>
                            </fieldset>

                            <fieldset class="mb-3">
                                <label for="note" class="form-label">Catatan/Pelanggan</label>
                                <input type="text" name="note" id="note" class="form-control"
                                    maxlength="255" placeholder="Misal: Nama pelanggan / no. telp / catatan">
                            </fieldset>
                            <input type="hidden" name="suspended_from_id" id="suspended_from_id" />

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg" id="btnCheckout" disabled>
                                    <i class="bi bi-check2-circle"></i> Proses Pembayaran
                                </button>
                            </div>
                            <div id="snapSection" class="mt-3" style="display:none;">
                                <div class="card">
                                    <div class="card-header">
                                        <i class="bi bi-qr-code"></i> Pembayaran QRIS
                                    </div>
                                    <div class="card-body">
                                        <div id="snapContainer"></div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </aside>
            </div>
        </section>
    </section>
    <!-- Modal Holds -->
    <div class="modal fade" id="holdsModal" tabindex="-1" aria-labelledby="holdsLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="holdsLabel"><i class="bi bi-inboxes"></i> Transaksi Tertunda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div id="holdsList" class="list-group small"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script
        src="{{ config('midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
        data-client-key="{{ config('midtrans.client_key') }}"></script>
    <script src="{{ asset('assets/vendor/jquery-3.7.0.min.js') }}"></script>

    <script>
        (function() {
            const fmt = (n) => Number(n || 0).toLocaleString('id-ID');
            const $q = $('#q');
            const $cartBody = $('#cartBody');
            const $sumSubtotal = $('#sumSubtotal');
            const $sumDiscount = $('#sumDiscount');
            const $sumTax = $('#sumTax');
            const $sumTotal = $('#sumTotal');
            const discount = {{ json_encode((float) $discount_percent) }};
            const tax = {{ json_encode((float) $tax_percent) }};
            const $btnCheckout = $('#btnCheckout');
            const $itemsJson = $('#items_json');
            const $paymentMethod = $('#payment_method');
            const $paidAmount = $('#paid_amount');
            const $btnClearCart = $('#btnClearCart');
            const $btnHold = $('#btnHold');
            const $btnShowHolds = $('#btnShowHolds');

            const $searchDropdown = $('#searchDropdown');
            const $inlineDropMenu = $('#inlineDropMenu');
            const $inlineDropResults = $('#inlineDropResults');
            const $changeDisplay = $('#changeDisplay');

            let cart = [];
            let lastSubtotal = 0;
            let lastTotal = 0;
            let dropdownReq = null;
            let dropDebounce = null;
            let pollTimer = null;

            function renderInlineResults(list) {
                if (!Array.isArray(list) || !list.length) {
                    $inlineDropResults.html('<div class="text-muted small">Produk tidak ditemukan.</div>');
                    return;
                }
                const rows = list.map(p => {
                    const disabled = p.stock <= 0 ? 'disabled' : '';
                    const stockInfo = p.stock <= 0 ? '<span class="badge bg-secondary">Habis</span>' :
                        `<span class="badge bg-success">Stok: ${p.stock}</span>`;
                    return `
                    <div class="d-flex justify-content-between align-items-center border rounded p-2 mb-2">
                        <div class="me-2" style="min-width:0;">
                            <div class="fw-semibold text-truncate" title="${p.name}">${p.name}</div>
                            <div class="small text-muted">SKU: ${p.sku} • Rp ${fmt(p.price)} ${stockInfo}</div>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="number" class="form-control form-control-sm" style="width: 70px" min="1" value="1" id="qty_${p.id}" ${disabled}>
                            <button class="btn btn-sm btn-primary" data-add="${p.id}" ${disabled} title="Tambah ke keranjang"><i class="bi bi-cart-plus"></i></button>
                        </div>
                    </div>
                `;
                }).join('');
                $inlineDropResults.html(rows);
            }

            function upsertCart(product, qty) {
                const idx = cart.findIndex(x => x.product_id === product.id);
                if (idx >= 0) {
                    cart[idx].qty = Math.min((cart[idx].qty + qty), product.stock);
                } else {
                    cart.push({
                        product_id: product.id,
                        name: product.name,
                        price: Number(product.price),
                        qty: Math.min(qty, product.stock),
                        stock: product.stock
                    });
                }
                renderCart();
            }

            function renderCart() {
                if (!cart.length) {
                    $cartBody.html('<tr><td colspan="5" class="text-center text-muted">Keranjang kosong.</td></tr>');
                    calcSummary();
                    $btnClearCart.prop('disabled', true);
                    $btnHold.prop('disabled', true);
                    return;
                }
                const rows = cart.map((it, i) => {
                    const line = Number(it.price) * Number(it.qty);
                    return `
                    <tr>
                        <td>
                            <div class="fw-semibold">${it.name}</div>
                            <div class="small text-muted">ID: ${it.product_id}</div>
                        </td>
                        <td class="text-end">Rp ${fmt(it.price)}</td>
                        <td class="text-center">
                            <div class="input-group input-group-sm justify-content-center" style="max-width: 140px;">
                                <button class="btn btn-outline-secondary" data-dec="${i}" ${it.qty <= 1 ? 'disabled':''}>-</button>
                                <input type="number" class="form-control text-center" min="1" max="${it.stock}" value="${it.qty}" data-qty="${i}">
                                <button class="btn btn-outline-secondary" data-inc="${i}" ${it.qty >= it.stock ? 'disabled':''}>+</button>
                            </div>
                            <div class="small text-muted mt-1">Stok: ${it.stock}</div>
                        </td>
                        <td class="text-end">Rp ${fmt(line)}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-danger" data-del="${i}"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                `;
                }).join('');
                $cartBody.html(rows);
                calcSummary();
                $btnClearCart.prop('disabled', cart.length === 0);
                $btnHold.prop('disabled', cart.length === 0);
            }

            function calcSummary() {
                const subtotal = cart.reduce((s, it) => s + (Number(it.price) * Number(it.qty)), 0);
                const discountAmount = subtotal * (discount / 100);
                const afterDiscount = subtotal - discountAmount;
                const taxAmount = afterDiscount * (tax / 100);
                const total = afterDiscount + taxAmount;

                $sumSubtotal.text(fmt(subtotal));
                $sumDiscount.text(fmt(discountAmount));
                $sumTax.text(fmt(taxAmount));
                $sumTotal.text(fmt(total));

                $btnCheckout.prop('disabled', cart.length === 0);
                $itemsJson.val(JSON.stringify(cart.map(({
                    product_id,
                    qty
                }) => ({
                    product_id,
                    qty
                }))));

                lastSubtotal = subtotal;
                lastTotal = total;
                updatePaidState();
            }

            function showInlineMenu() {
                if ($inlineDropMenu.css('display') === 'none') {
                    $inlineDropMenu.css('display', 'block');
                    $q.attr('aria-expanded', 'true');
                }
            }

            function hideInlineMenu() {
                if ($inlineDropMenu.css('display') !== 'none') {
                    $inlineDropMenu.css('display', 'none');
                    $q.attr('aria-expanded', 'false');
                }
            }

            function searchInline(q) {
                $inlineDropResults.html('<div class="text-muted small">Memuat…</div>');
                const params = q ? {
                    q,
                    limit: 20
                } : {
                    limit: 20
                };

                if (dropdownReq && typeof dropdownReq.abort === 'function') {
                    try {
                        dropdownReq.abort();
                    } catch (e) {}
                }

                dropdownReq = $.get(@json(route('kasir.products')), params)
                    .done(renderInlineResults)
                    .fail((xhr, status) => {
                        if (status === 'abort') return;
                        $inlineDropResults.html('<div class="text-danger small">Gagal memuat data.</div>');
                    })
                    .always(() => {
                        dropdownReq = null;
                    });
            }

            function parseMoneyToInt(str) {
                if (typeof str !== 'string') str = String(str || '');

                const digits = str.replace(/[^0-9]/g, '');
                return Number(digits || 0);
            }

            function formatMoney(val) {
                return 'Rp ' + fmt(val);
            }

            function updatePaidState() {
                const method = $paymentMethod.val();
                const paid = parseMoneyToInt($paidAmount.val());
                const allow = method !== 'cash' || paid >= Math.ceil(lastTotal);
                const canPay = allow && cart.length > 0;
                $btnCheckout.prop('disabled', !canPay);
                if (method === 'cash') {
                    const change = Math.max(0, paid - Math.ceil(lastTotal));
                    $changeDisplay.text('Kembalian: ' + formatMoney(change));
                } else {
                    $changeDisplay.text('');
                }
            }

            function startStatusPolling(trxId) {
                if (pollTimer) {
                    clearInterval(pollTimer);
                    pollTimer = null;
                }
                const toShowUrl = (id) => @json(route('pembayaran.complete', ['transaction' => '__ID__'])).replace('__ID__', id);
                const statusUrlTmpl = @json(route('pembayaran.status', ['transaction' => '__ID__']));
                const url = statusUrlTmpl.replace('__ID__', trxId);

                pollTimer = setInterval(() => {
                    $.ajax({
                        url: url,
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json'
                        }
                    }).done((res) => {
                        const s = String(res?.status || '').toLowerCase();
                        if (s === 'settlement' || s === 'paid') {
                            clearInterval(pollTimer);
                            pollTimer = null;
                            window.location = toShowUrl(trxId);
                            return;
                        }
                        if (['expire', 'cancel', 'deny', 'failure', 'refunded', 'canceled'].includes(
                                s)) {
                            clearInterval(pollTimer);
                            pollTimer = null;
                            alert('Pembayaran tidak berhasil (' + s + ').');
                        }
                    }).fail(() => {});
                }, 3000);
            }

            $('#btnSearch').on('click', function() {
                const q = ($q.val() || '').trim();
                showInlineMenu();
                searchInline(q);
                $q.trigger('focus');
            });

            $q.on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    const code = ($q.val() || '').trim();
                    if (!code) {
                        showInlineMenu();
                        searchInline('');
                        return;
                    }
                    $.get(@json(route('kasir.products')), {
                        q: code,
                        limit: 5
                    }).done((list) => {
                        let prod = null;
                        if (Array.isArray(list) && list.length) {
                            prod = list.find(p => String(p.sku) === code) || (/^\d+$/.test(code) ? list
                                .find(p => Number(p.id) === Number(code)) : null) || list[0];
                        }
                        if (prod && prod.stock > 0) {
                            upsertCart(prod, 1);
                            $q.val('');
                        } else {
                            showInlineMenu();
                            searchInline(code);
                        }
                    }).fail(() => {
                        showInlineMenu();
                        searchInline(code);
                    });
                }
            });

            $inlineDropResults.on('click', '[data-add]', function() {
                const id = Number($(this).data('add'));
                const qty = Number($('#qty_' + id).val() || 1);
                $.get(@json(route('kasir.products')), {
                    q: id,
                    limit: 1
                }).done((list) => {
                    const p = Array.isArray(list) ? list.find(x => Number(x.id) === id) : null;
                    if (p) upsertCart(p, qty);
                });
            });

            $q.on('focus', function() {
                showInlineMenu();
                if (!$inlineDropResults.children().length) {
                    searchInline('');
                }
            });

            // ESC to hide
            $q.on('keydown', function(e) {
                if (e.key === 'Escape') {
                    e.stopPropagation();
                    hideInlineMenu();
                }
            });

            // ESC to hide
            $inlineDropMenu.on('keydown', function(e) {
                if (e.key === 'Escape') {
                    e.stopPropagation();
                    hideInlineMenu();
                    $q.trigger('focus');
                }
            });

            // Hide when clicking outside
            $(document).on('click', function(e) {
                const el = $searchDropdown[0];
                if (el && !el.contains(e.target)) {
                    hideInlineMenu();
                    if (dropdownReq && typeof dropdownReq.abort === 'function') {
                        try {
                            dropdownReq.abort();
                        } catch (err) {}
                        dropdownReq = null;
                    }
                }
            });

            // Live filtering with debounce on the main input
            $q.on('input', function() {
                const q = ($q.val() || '').trim();
                showInlineMenu();
                if (dropDebounce) clearTimeout(dropDebounce);
                dropDebounce = setTimeout(() => searchInline(q), 250);
            });

            $('#cartTable').on('click', '[data-del]', function() {
                const i = Number($(this).data('del'));
                cart.splice(i, 1);
                renderCart();
            });

            $('#cartTable').on('click', '[data-inc]', function() {
                const i = Number($(this).data('inc'));
                cart[i].qty = Math.min(cart[i].qty + 1, cart[i].stock);
                renderCart();
            });

            $('#cartTable').on('click', '[data-dec]', function() {
                const i = Number($(this).data('dec'));
                cart[i].qty = Math.max(cart[i].qty - 1, 1);
                renderCart();
            });

            $('#cartTable').on('input', '[data-qty]', function() {
                const i = Number($(this).data('qty'));
                let v = Number($(this).val() || 1);
                v = Math.min(Math.max(v, 1), cart[i].stock);
                cart[i].qty = v;
                renderCart();
            });

            $btnClearCart.on('click', function() {
                cart = [];
                renderCart();
                $('#suspended_from_id').val('');
                // If modal open, refresh list to remove badge
                if ($('#holdsModal').hasClass('show')) loadHolds();
            });

            // Create Hold
            $btnHold.on('click', function() {
                if (!cart.length) return;
                const payload = {
                    _token: @json(csrf_token()),
                    items: JSON.parse($itemsJson.val() || '[]'),
                    note: ($('#note').val() || '').trim(),
                    suspended_from_id: ($('#suspended_from_id').val() || '').trim()
                };
                $.ajax({
                    url: @json(route('kasir.hold')),
                    method: 'POST',
                    data: payload,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).done((res) => {
                    alert('Transaksi ditunda: ' + (res?.invoice || ''));
                    cart = [];
                    renderCart();
                    $('#suspended_from_id').val('');
                    $('#note').val('');
                    if ($('#holdsModal').hasClass('show')) loadHolds();
                }).fail((xhr) => {
                    alert(xhr?.responseJSON?.message || 'Gagal menunda transaksi');
                });
            });

            function loadHolds() {
                $('#holdsList').html('<div class="text-muted">Memuat…</div>');
                $.get(@json(route('kasir.holds'))).done((list) => {
                    if (!Array.isArray(list) || !list.length) {
                        $('#holdsList').html('<div class="text-muted">Tidak ada transaksi tertunda.</div>');
                        return;
                    }
                    const currentIdNum = Number(($('#suspended_from_id').val() || '').toString().trim());
                    const rows = list.map(h => {
                        const isCurrent = Number.isFinite(currentIdNum) && currentIdNum > 0 && Number(h
                            .id) === currentIdNum;
                        const badge = isCurrent ?
                            '<span class="badge bg-info ms-2">Sedang dimuat</span>' : '';
                        return `
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">${h.invoice_number} ${badge}</div>
                                <div class="text-muted">Pelanggan/Catatan: <span class="fw-semibold">${(h.note||'-')}</span></div>
                                <div class="small text-muted">${new Date(h.created_at).toLocaleString('id-ID')} • Total Rp ${fmt(h.total)}</div>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-primary" data-resume="${h.id}"><i class="bi bi-download"></i> Muat</button>
                                <button class="btn btn-sm btn-outline-danger" data-delete="${h.id}"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>`;
                    }).join('');
                    $('#holdsList').html(rows);
                }).fail(() => {
                    $('#holdsList').html('<div class="text-danger">Gagal memuat data.</div>');
                });
            }

            $btnShowHolds.on('click', function() {
                const el = document.getElementById('holdsModal');
                if (!el) return;
                const m = new bootstrap.Modal(el);
                m.show();
                loadHolds();
                $('#holdsList').off('click').on('click', '[data-resume]', function() {
                    const id = $(this).data('resume');
                    $.post(@json(route('kasir.holds.resume', ['transaction' => '__ID__'])).replace('__ID__', id), {
                        _token: @json(csrf_token())
                    }).done((res) => {
                        if (Array.isArray(res?.items)) {
                            const items = res.items;
                            $('#suspended_from_id').val(id);
                            $('#note').val(res.note || '');
                            const ids = items.map(i => i.product_id);
                            $.get(@json(route('kasir.products')), {
                                q: '',
                                limit: 100
                            }).done((all) => {
                                cart = items.map(it => {
                                    const p = Array.isArray(all) ? all.find(x =>
                                        Number(x.id) === Number(it
                                            .product_id)) : null;
                                    const stock = p ? Number(p.stock) : it
                                        .qty; // fallback
                                    return {
                                        product_id: it.product_id,
                                        name: p ? p.name : ('Produk #' + it
                                            .product_id),
                                        price: Number(it.price || 0),
                                        qty: Math.min(Number(it.qty), stock),
                                        stock: stock
                                    };
                                });
                                renderCart();
                                loadHolds(); // refresh badge state
                                m.hide();
                            });
                        }
                    }).fail(() => alert('Gagal memuat transaksi.'));
                }).on('click', '[data-delete]', function() {
                    const id = $(this).data('delete');
                    if (!confirm('Hapus transaksi tertunda ini?')) return;
                    $.ajax({
                            url: @json(route('kasir.holds.destroy', ['transaction' => '__ID__'])).replace('__ID__', id),
                            method: 'DELETE',
                            data: {
                                _token: @json(csrf_token())
                            }
                        })
                        .done(() => loadHolds())
                        .fail(() => alert('Gagal menghapus.'));
                });
            });

            $('#checkoutForm [data-method]').on('click', function() {
                $('#checkoutForm [data-method]').removeClass('active');
                $(this).addClass('active');
                const method = $(this).data('method');
                $('#payment_method').val(method);
                const isCash = method === 'cash';
                $('#cashSection').toggle(isCash);
                updatePaidState();
            });

            $paidAmount.on('input', function() {
                const caretEnd = this.selectionEnd;
                const rawNum = parseMoneyToInt($(this).val());
                const formatted = formatMoney(rawNum);
                $(this).val(formatted);
                try {
                    this.setSelectionRange(formatted.length, formatted.length);
                } catch (e) {}
                updatePaidState();
            });

            $('#checkoutForm').on('submit', function(e) {
                e.preventDefault();
                if (!cart.length) {
                    alert('Keranjang kosong.');
                    return false;
                }
                try {
                    const parsed = JSON.parse($('#items_json').val() || '[]');
                    if (!Array.isArray(parsed) || !parsed.length) {
                        alert('Keranjang kosong.');
                        return false;
                    }
                } catch (e) {
                    alert('Keranjang tidak valid.');
                    return false;
                }
                const method = $('#payment_method').val();
                const paidInt = parseMoneyToInt($paidAmount.val());
                $paidAmount.val(String(paidInt));

                // Cash: submit normal
                if (method === 'cash') {
                    HTMLFormElement.prototype.submit.call(this);
                    return false;
                }

                // QRIS: AJAX -> Snap Embedded
                const $form = $(this);
                $.ajax({
                    url: $form.attr('action'),
                    method: 'POST',
                    data: $form.serialize(),
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    beforeSend: function() {
                        $btnCheckout.prop('disabled', true).text('Memproses…');
                    },
                    complete: function() {
                        $btnCheckout.prop('disabled', false).html(
                            '<i class="bi bi-check2-circle"></i> Proses Pembayaran');
                    }
                }).done((res) => {
                    const token = res?.snap_token;
                    const trxId = res?.transaction_id;
                    if (!token || !trxId) {
                        alert('Gagal memulai pembayaran.');
                        return;
                    }
                    $('#snapSection').show();

                    // Start polling status after embed started
                    startStatusPolling(trxId);

                    const toShowUrl = (id) => @json(route('pembayaran.complete', ['transaction' => '__ID__'])).replace('__ID__', id);
                    try {
                        window.snap.embed(token, {
                            embedId: 'snapContainer',
                            onSuccess: function() {
                                if (pollTimer) {
                                    clearInterval(pollTimer);
                                    pollTimer = null;
                                }
                                window.location = toShowUrl(trxId);
                            },
                            onPending: function() {
                                if (pollTimer) {
                                    clearInterval(pollTimer);
                                    pollTimer = null;
                                }
                            },
                            onError: function() {
                                alert('Pembayaran gagal. Coba lagi.');
                            },
                            onClose: function() {}
                        });
                    } catch (err) {
                        alert('Gagal memuat Snap. ' + (err?.message || ''));
                    }
                }).fail((xhr) => {
                    const msg = xhr?.responseJSON?.message || 'Gagal membuat transaksi QRIS.';
                    alert(msg);
                });
                return false;
            });

            renderCart();

            @if (session('printed_transaction_id'))
                try {
                    const el = document.getElementById('cashSuccessModal');
                    if (el) {
                        const m = new bootstrap.Modal(el);
                        m.show();
                        const btn = document.getElementById('btnPrintReceipt');
                        if (btn) {
                            btn.addEventListener('click', function() {
                                cart = [];
                                renderCart();
                                $('#suspended_from_id').val('');
                            });
                        }
                    }
                } catch (e) {}
            @endif
        })();
    </script>
@endpush
