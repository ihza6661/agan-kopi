@extends('layouts.app')

@section('title', 'Pembayaran QRIS')

@section('content')
    <section class="container py-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h4 mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-qr-code"></i> Pembayaran QRIS • <span
                    class="text-muted">{{ $transaction->invoice_number }}</span>
            </h1>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('pembayaran') }}"><i class="bi bi-arrow-left"></i> Daftar
                    Pembayaran</a>
                <a class="btn btn-outline-primary" href="{{ route('transaksi.show', $transaction) }}"><i
                        class="bi bi-eye"></i> Detail Transaksi</a>
                <a class="btn btn-primary" target="_blank" rel="noopener noreferrer"
                    href="{{ route('transaksi.struk', $transaction) }}"><i class="bi bi-printer"></i> Cetak Struk</a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <p class="mb-2">Total: <strong>@money($transaction->total)</strong>
                        </p>
                        @php
                            $pm = strtolower((string) ($payment->method->value ?? ($payment->method ?? '')));
                            $ps = strtolower((string) ($payment->status->value ?? ($payment->status ?? 'pending')));
                            $snapToken = $payment->metadata['snap_token'] ?? null;
                        @endphp
                        @if ($snapToken && $ps === 'pending')
                            <div id="snapSection">
                                <div id="snapContainer"></div>
                                <p class="small text-muted mt-2">Tampilkan QR ini ke pelanggan untuk dipindai.</p>
                            </div>
                        @elseif ($payment->qr_url)
                            <img src="{{ $payment->qr_url }}" alt="QRIS" class="img-fluid border rounded"
                                style="max-width: 320px;">
                        @elseif ($payment->qr_string)
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=320x320&data={{ urlencode($payment->qr_string) }}"
                                alt="QRIS" class="img-fluid border rounded" style="max-width: 320px;">
                            <p class="small text-muted mt-2">Tampilkan QR ini ke pelanggan untuk dipindai.</p>
                        @else
                            <div class="alert alert-warning mb-0">QR belum tersedia.</div>
                        @endif
                        <div id="payStatus" class="mt-3"></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 text-muted mb-3">Ringkasan Pembayaran</h2>
                        <div class="row small g-2">
                            <div class="col-6">
                                <div class="text-muted">Invoice</div>
                                <div class="fw-semibold">{{ $transaction->invoice_number }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted">Kasir</div>
                                <div class="fw-semibold">{{ $transaction->user->name ?? '-' }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted">Metode</div>
                                <div class="text-uppercase">
                                    {{ $transaction->payment_method->value ?? $transaction->payment_method }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted">Provider</div>
                                <div class="text-uppercase">{{ $payment->provider ?? '-' }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted">Status Pembayaran</div>
                                @php $s = strtolower((string)($payment->status->value ?? $payment->status ?? 'pending')); @endphp
                                @php $class = $s==='settlement' ? 'bg-success' : ($s==='pending' ? 'bg-warning text-dark' : 'bg-danger'); @endphp
                                <div><span class="badge {{ $class }}">{{ strtoupper($s) }}</span></div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted">Dibuat</div>
                                <div>{{ $payment->created_at?->format('d/m/Y H:i') }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted">Dibayar</div>
                                <div>{{ $payment->paid_at?->format('d/m/Y H:i') ?? '-' }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted">Jumlah</div>
                                <div class="fw-semibold">@money($payment->amount)</div>
                            </div>
                        </div>
                        <div class="alert alert-info d-flex align-items-start gap-2 mt-3" id="statusAlert" role="status"
                            style="display:none;"><i class="bi bi-info-circle"></i>
                            <div class="flex-grow-1" id="statusText"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('script')
        @php
            $snapToken = $payment->metadata['snap_token'] ?? null;
            $ps = strtolower((string) ($payment->status->value ?? ($payment->status ?? 'pending')));
        @endphp
        @if ($snapToken && $ps === 'pending')
            <script
                src="{{ config('midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
                data-client-key="{{ config('midtrans.client_key') }}"></script>
            <script>
                (function() {
                    const token = @json($snapToken);
                    try {
                        window.snap.embed(token, {
                            embedId: 'snapContainer'
                        });
                    } catch (e) {
                        console.error('Gagal memuat Snap:', e);
                    }
                })();
            </script>
        @endif
        <script>
            (function() {
                const url = @json(route('pembayaran.status', $transaction));
                const payStatus = document.getElementById('payStatus');
                const statusAlert = document.getElementById('statusAlert');
                const statusText = document.getElementById('statusText');

                function setStatus(message, type) {
                    if (type === 'success') {
                        payStatus.innerHTML = '<span class="badge bg-success">BERHASIL</span>';
                        statusAlert.style.display = '';
                        statusAlert.classList.remove('alert-warning', 'alert-danger');
                        statusAlert.classList.add('alert-success');
                        statusText.textContent = message || 'Pembayaran berhasil.';
                    } else if (type === 'error') {
                        payStatus.innerHTML = '<span class="badge bg-danger">GAGAL</span>';
                        statusAlert.style.display = '';
                        statusAlert.classList.remove('alert-success', 'alert-warning');
                        statusAlert.classList.add('alert-danger');
                        statusText.textContent = message || 'Pembayaran tidak berhasil.';
                    } else {
                        payStatus.innerHTML = '<span class="badge bg-warning text-dark">MENUNGGU</span>';
                        statusAlert.style.display = '';
                        statusAlert.classList.remove('alert-success', 'alert-danger');
                        statusAlert.classList.add('alert-warning');
                        statusText.textContent = message || 'Menunggu pelanggan menyelesaikan pembayaran…';
                    }
                }

                let stopped = false;

                function poll() {
                    if (stopped) return;
                    fetch(url, {
                            cache: 'no-store'
                        })
                        .then(r => r.json())
                        .then(d => {
                            const s = (d.status || '').toLowerCase();
                            if (s === 'settlement') {
                                setStatus(
                                    'Pembayaran berhasil. Anda dapat mencetak struk atau kembali ke daftar pembayaran.',
                                    'success');
                                stopped = true; // Jangan redirect otomatis
                            } else if (['expire', 'cancel', 'deny', 'failure'].includes(s)) {
                                setStatus('Pembayaran tidak berhasil (' + s + ').', 'error');
                                stopped = true;
                            } else {
                                setStatus('Menunggu pelanggan menyelesaikan pembayaran…', 'pending');
                            }
                        })
                        .catch(() => {})
                        .finally(() => {
                            if (!stopped) setTimeout(poll, 3000);
                        });
                }

                const initial = @json(strtolower((string) ($payment->status->value ?? ($payment->status ?? 'pending'))));
                if (initial === 'settlement') {
                    setStatus('Pembayaran berhasil. Anda dapat mencetak struk atau kembali ke daftar pembayaran.',
                        'success');
                    stopped = true;
                } else if (['expire', 'cancel', 'deny', 'failure'].includes(initial)) {
                    setStatus('Pembayaran tidak berhasil (' + initial + ').', 'error');
                    stopped = true;
                } else {
                    setStatus('Menunggu pelanggan menyelesaikan pembayaran…', 'pending');
                    poll();
                }
            })();
        </script>
    @endpush
@endsection
