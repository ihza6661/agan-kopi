<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk {{ $transaction->invoice_number }}</title>
    <link href="{{ asset('assets/vendor/bootstrap.min.css') }}" rel="stylesheet" />
    <style>
        body {
            background: #f8f9fa;
        }

        .receipt {
            width: 320px;
            margin: 12px auto;
            background: #fff;
            border: 1px solid #e9ecef;
            padding: 12px;
        }

        .receipt .hr {
            border-top: 1px dashed #dee2e6;
            margin: .5rem 0;
        }

        .receipt .muted {
            color: #6c757d;
            font-size: 11px;
        }

        .receipt table {
            width: 100%;
            font-size: 12px;
        }

        .text-end {
            text-align: right;
        }

        @media print {
            body {
                background: #fff;
            }

            .no-print {
                display: none !important;
            }

            .receipt {
                margin: 0;
                border: 0;
                width: auto;
            }
        }
    </style>
    @if (request()->boolean('print'))
        <script>
            window.addEventListener('load', function() {
                window.print();
            });
        </script>
    @endif
    @php
        $fmt = fn($n) => number_format((float) $n, 0, ',', '.');
    @endphp
</head>

<body>
    <div class="receipt">
        <div class="text-center">
            @if ($store_logo)
                <img src="{{ asset($store_logo) }}" alt="Logo" width="48" height="48" class="mb-2" />
            @endif
            <div class="fw-bold">{{ $store_name }}</div>
            @if ($store_address)
                <div class="muted">{{ $store_address }}</div>
            @endif
            @if ($store_phone)
                <div class="muted">{{ $store_phone }}</div>
            @endif
        </div>
        <div class="hr"></div>
        <div class="d-flex flex-column" style="font-size:12px;">
            <div class="d-flex justify-content-between"><span
                    class="muted">No</span><span>{{ $transaction->invoice_number }}</span></div>
            <div class="d-flex justify-content-between"><span
                    class="muted">Tanggal</span><span>{{ $transaction->created_at->format('d/m/Y H:i') }}</span></div>
            <div class="d-flex justify-content-between"><span
                    class="muted">Kasir</span><span>{{ $transaction->user->name ?? '-' }}</span></div>
        </div>
        <div class="hr"></div>
        <table class="table table-sm mb-2">
            <thead>
                <tr class="small">
                    <th>Item</th>
                    <th class="text-end" style="width:44px;">Qty</th>
                    <th class="text-end" style="width:74px;">Harga</th>
                    <th class="text-end" style="width:74px;">Total</th>
                </tr>
            </thead>
            <tbody class="small">
                @foreach ($transaction->details as $d)
                    <tr>
                        <td>{{ $d->product->name ?? '#' . $d->product_id }}</td>
                        <td class="text-end">{{ (int) $d->quantity }}</td>
                        <td class="text-end">{{ $fmt($d->price) }}</td>
                        <td class="text-end">{{ $fmt($d->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="hr"></div>
        <div class="small">
            <div class="d-flex justify-content-between">
                <span>Subtotal</span><span>{{ $fmt($transaction->subtotal) }}</span></div>
            <div class="d-flex justify-content-between">
                <span>Diskon</span><span>{{ $fmt($transaction->discount) }}</span></div>
            <div class="d-flex justify-content-between"><span>Pajak</span><span>{{ $fmt($transaction->tax) }}</span>
            </div>
            <div class="d-flex justify-content-between fw-bold border-top pt-1">
                <span>Total</span><span>{{ $fmt($transaction->total) }}</span></div>
        </div>
        <div class="hr"></div>
        <div class="small">
            <div class="d-flex justify-content-between">
                <span>Metode</span><span>{{ strtoupper($transaction->payment_method->value ?? (string) $transaction->payment_method) }}</span>
            </div>
            <div class="d-flex justify-content-between">
                <span>Bayar</span><span>{{ $fmt($transaction->amount_paid) }}</span></div>
            <div class="d-flex justify-content-between">
                <span>Kembali</span><span>{{ $fmt($transaction->change) }}</span></div>
        </div>
        <div class="hr"></div>
        <div class="text-center muted">Simpan struk sebagai bukti pembelian</div>
        <div class="text-center small">Terima kasih telah berbelanja!</div>

        <div class="no-print mt-3 d-grid gap-2">
            <a class="btn btn-primary btn-sm"
                href="{{ route('transaksi.struk', ['transaction' => $transaction->id, 'print' => 1]) }}"
                target="_blank" rel="noopener noreferrer"><i class="bi bi-printer"></i> Cetak</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('kasir') }}">Kembali ke Kasir</a>
        </div>
    </div>
</body>

</html>
