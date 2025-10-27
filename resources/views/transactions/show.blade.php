@extends('layouts.app')

@section('title', 'Detail Transaksi')

@section('content')
    <section class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h3 mb-0"><i class="bi bi-receipt"></i> Transaksi {{ $trx->invoice_number }}</h1>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('transaksi') }}"><i class="bi bi-arrow-left"></i>
                    Kembali</a>
                <a class="btn btn-primary" href="{{ route('transaksi.struk', $trx) }}" target="_blank"
                    rel="noopener noreferrer"><i class="bi bi-printer"></i> Cetak Struk</a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="row small">
                            <div class="col-md-6">
                                <div><span class="text-muted">Tanggal</span>
                                    <div>{{ $trx->created_at->format('d/m/Y H:i') }}</div>
                                </div>
                                <div class="mt-2"><span class="text-muted">Kasir</span>
                                    <div>{{ $trx->user->name ?? '-' }}</div>
                                </div>
                                <div class="mt-2"><span class="text-muted">Metode</span>
                                    <div class="text-uppercase">{{ $trx->payment_method->value ?? $trx->payment_method }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                @php $s = $trx->status->value ?? $trx->status; @endphp
                                <div><span class="text-muted">Status</span>
                                    <div><span
                                            class="badge {{ $s === 'paid' ? 'bg-success' : ($s === 'pending' ? 'bg-warning text-dark' : 'bg-secondary') }}">{{ strtoupper($s) }}</span>
                                    </div>
                                </div>
                                <div class="mt-2"><span class="text-muted">Total</span>
                                    <div class="fw-semibold">@money($trx->total)</div>
                                </div>
                                <div class="mt-2"><span class="text-muted">Bayar</span>
                                    <div>@money($trx->amount_paid)</div>
                                </div>
                                <div class="mt-2"><span class="text-muted">Kembali</span>
                                    <div>@money($trx->change)</div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive mt-3">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-end" style="width:60px;">Qty</th>
                                        <th class="text-end" style="width:120px;">Harga</th>
                                        <th class="text-end" style="width:120px;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($trx->details as $d)
                                        <tr>
                                            <td>{{ $d->product->name ?? '#' . $d->product_id }}</td>
                                            <td class="text-end">{{ (int) $d->quantity }}</td>
                                            <td class="text-end">Rp {{ number_format($d->price, 0, ',', '.') }}</td>
                                            <td class="text-end">Rp {{ number_format($d->total, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 text-muted">Ringkasan</h2>
                        <div class="small">
                            <div class="d-flex justify-content-between"><span>Subtotal</span><span>@money($trx->subtotal)</span>
                            </div>
                            <div class="d-flex justify-content-between"><span>Diskon</span><span>@money($trx->discount)</span>
                            </div>
                            <div class="d-flex justify-content-between"><span>Pajak</span><span>@money($trx->tax)</span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold border-top pt-1">
                                <span>Total</span><span>@money($trx->total)</span>
                            </div>
                        </div>
                        @php $isPendingQris = (($trx->payment_method->value ?? $trx->payment_method) === 'qris') && (($trx->status->value ?? $trx->status) === 'pending'); @endphp
                        <div class="d-grid gap-2 mt-3">
                            <a class="btn btn-outline-secondary" href="{{ route('transaksi.struk', $trx) }}"
                                target="_blank" rel="noopener noreferrer"><i class="bi bi-receipt-cutoff"></i> Lihat
                                Struk</a>
                            @if ($isPendingQris)
                                <a class="btn btn-primary" href="{{ route('pembayaran.show', $trx) }}"><i
                                        class="bi bi-qr-code"></i> Lanjutkan QRIS</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
