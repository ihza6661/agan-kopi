@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <section class="container py-4">
        <header class="mb-4">
            <h1 class="h3 d-flex align-items-center gap-2">
                <i class="bi bi-speedometer2"></i> Dashboard
            </h1>
            <p class="text-muted mb-0">Ringkasan aktivitas kasir dan penjualan.</p>
        </header>

        <section class="row row-cols-1 row-cols-md-2 row-cols-xxl-6 g-3">
            <article class="col">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <i class="bi bi-cash-coin text-success fs-2 me-3"></i>
                        <div>
                            <p class="text-muted mb-0">Penjualan Hari Ini</p>
                            <h2 class="h4 mb-0">@money($salesToday)</h2>
                        </div>
                    </div>
                </div>
            </article>
            <article class="col">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <i class="bi bi-receipt text-primary fs-2 me-3"></i>
                        <div>
                            <p class="text-muted mb-0">Transaksi</p>
                            <h2 class="h4 mb-0">{{ number_format($trxToday, 0, ',', '.') }}</h2>
                        </div>
                    </div>
                </div>
            </article>
            <article class="col">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <i class="bi bi-box-seam text-warning fs-2 me-3"></i>
                        <div>
                            <p class="text-muted mb-0">Produk Habis</p>
                            <h2 class="h4 mb-0">{{ number_format($outOfStock, 0, ',', '.') }}</h2>
                        </div>
                    </div>
                </div>
            </article>
            <article class="col">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <i class="bi bi-people text-info fs-2 me-3"></i>
                        <div>
                            <p class="text-muted mb-0">Produk Stok Rendah (&le; stok minimum)</p>
                            <h2 class="h4 mb-0">{{ number_format($lowStock, 0, ',', '.') }}</h2>
                        </div>
                    </div>
                </div>
            </article>
            <article class="col">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <i class="bi bi-graph-up-arrow text-danger fs-2 me-3"></i>
                        <div>
                            <p class="text-muted mb-0">Total 7 Hari</p>
                            <h2 class="h4 mb-0">@money($sales7days)</h2>
                        </div>
                    </div>
                </div>
            </article>
            <article class="col">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <i class="bi bi-basket text-secondary fs-2 me-3"></i>
                        <div>
                            <p class="text-muted mb-0">Rata-rata Order (7 hari)</p>
                            <h2 class="h4 mb-0">@money($aov7days)</h2>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <section class="row g-3 mt-1 mt-md-3">
            <div class="col-12 col-xxl-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="bi bi-graph-up"></i>
                        <strong>Penjualan 7 Hari Terakhir</strong>
                    </div>
                    <div class="card-body">
                        <div class="ratio ratio-21x9">
                            <canvas id="sales7Chart" aria-label="Grafik Penjualan 7 Hari" role="img"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xxl-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="bi bi-star"></i>
                        <strong>Top Produk Hari Ini</strong>
                    </div>
                    <div class="card-body">
                        @if ($topToday->isEmpty())
                            <div class="text-muted">Belum ada penjualan hari ini.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Produk</th>
                                            <th class="text-end">Qty</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($topToday as $p)
                                            <tr>
                                                <td>{{ $p->name }}</td>
                                                <td class="text-end">{{ number_format($p->qty, 0, ',', '.') }}</td>
                                                <td class="text-end">@money($p->total)</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section class="row g-3 mt-1 mt-md-3">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body d-flex flex-wrap gap-3 justify-content-between">
                        <div>
                            <div class="text-muted">Item Terjual (7 hari)</div>
                            <div class="fw-semibold">{{ number_format($items7days, 0, ',', '.') }}</div>
                        </div>
                        <div>
                            <div class="text-muted">Transaksi (7 hari)</div>
                            <div class="fw-semibold">{{ number_format($trx7days, 0, ',', '.') }}</div>
                        </div>
                        <div>
                            <div class="text-muted">Hari Terbaik</div>
                            <div class="fw-semibold">{{ $bestDay['label'] ?? '-' }} (@money($bestDay['value'] ?? 0))</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </section>
@endsection

@push('script')
    <script>
        (function() {
            const canvas = document.getElementById('sales7Chart');
            if (!canvas) return;

            const labels = @json($chartLabels);
            const rawValues = @json($chartValues);
            const values = (rawValues || []).map(v => Number(v) || 0);
            const currencyCode = '{{ strtoupper($currency ?? ($appCurrency ?? 'IDR')) }}';

            // Resize-aware rendering
            const dpr = window.devicePixelRatio || 1;
            let width = 0,
                height = 0;
            const padding = {
                top: 16,
                right: 12,
                bottom: 36,
                left: 48
            };
            const state = {
                hoverIndex: null
            };

            function niceMax(maxVal) {
                if (!isFinite(maxVal) || maxVal <= 0) return 1;
                const pow = Math.pow(10, Math.floor(Math.log10(maxVal)));
                const n = maxVal / pow;
                let step;
                if (n <= 1) step = 1;
                else if (n <= 2) step = 2;
                else if (n <= 5) step = 5;
                else step = 10;
                return Math.ceil(maxVal / (step * pow)) * step * pow;
            }

            function fmtMoney(n) {
                const num = Number(n) || 0;
                const prefix = currencyCode === 'IDR' ? 'Rp ' : (currencyCode + ' ');
                return prefix + new Intl.NumberFormat('id-ID', {
                    maximumFractionDigits: 0
                }).format(num);
            }

            function setSize() {
                const rect = canvas.getBoundingClientRect();
                width = Math.max(300, Math.floor(rect.width));
                height = Math.max(180, Math.floor(rect.height));
                canvas.width = Math.floor(width * dpr);
                canvas.height = Math.floor(height * dpr);
                render();
            }

            function getScale() {
                const plotW = width - padding.left - padding.right;
                const plotH = height - padding.top - padding.bottom;
                const maxV = niceMax(Math.max(1, ...values));
                const n = Math.max(1, values.length - 1);
                const xAt = i => padding.left + (plotW * (i / Math.max(1, values.length - 1)));
                const yAt = v => padding.top + plotH - (plotH * (v / maxV));
                return {
                    plotW,
                    plotH,
                    maxV,
                    xAt,
                    yAt
                };
            }

            function clear(ctx) {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            }

            function drawGrid(ctx, scale) {
                const {
                    plotW,
                    plotH,
                    maxV
                } = scale;
                const x0 = padding.left,
                    y0 = padding.top,
                    x1 = padding.left + plotW,
                    y1 = padding.top + plotH;
                ctx.save();
                ctx.scale(dpr, dpr);
                ctx.strokeStyle = 'rgba(0,0,0,0.08)';
                ctx.fillStyle = '#6c757d';
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.rect(x0, y0, plotW, plotH);
                ctx.stroke();
                // horizontal lines + labels
                const steps = 5;
                for (let s = 0; s <= steps; s++) {
                    const yVal = (maxV / steps) * s;
                    const y = padding.top + plotH - (plotH * (yVal / maxV));
                    ctx.strokeStyle = 'rgba(0,0,0,0.06)';
                    ctx.beginPath();
                    ctx.moveTo(x0, y);
                    ctx.lineTo(x1, y);
                    ctx.stroke();
                    ctx.fillStyle = '#6c757d';
                    ctx.textAlign = 'right';
                    ctx.textBaseline = 'middle';
                    ctx.font = '12px system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial';
                    ctx.fillText(new Intl.NumberFormat('id-ID').format(Math.round(yVal)), x0 - 8, y);
                }
                // x labels
                ctx.textAlign = 'center';
                ctx.textBaseline = 'top';
                for (let i = 0; i < labels.length; i++) {
                    const x = scale.xAt(i);
                    ctx.fillText(labels[i], x, y1 + 6);
                }
                ctx.restore();
            }

            function drawSeries(ctx, scale) {
                const {
                    xAt,
                    yAt
                } = scale;
                ctx.save();
                ctx.scale(dpr, dpr);
                // area
                ctx.beginPath();
                for (let i = 0; i < values.length; i++) {
                    const x = xAt(i),
                        y = yAt(values[i]);
                    if (i === 0) ctx.moveTo(x, y);
                    else ctx.lineTo(x, y);
                }
                const lastX = xAt(values.length - 1),
                    baseY = yAt(0);
                ctx.lineTo(lastX, baseY);
                ctx.lineTo(xAt(0), baseY);
                ctx.closePath();
                ctx.fillStyle = 'rgba(13,110,253,.15)';
                ctx.fill();
                // line
                ctx.beginPath();
                for (let i = 0; i < values.length; i++) {
                    const x = xAt(i),
                        y = yAt(values[i]);
                    if (i === 0) ctx.moveTo(x, y);
                    else ctx.lineTo(x, y);
                }
                ctx.strokeStyle = '#0d6efd';
                ctx.lineWidth = 2;
                ctx.stroke();
                ctx.restore();
            }

            function drawHover(ctx, scale, i) {
                if (i == null || i < 0 || i >= values.length) return;
                const {
                    xAt,
                    yAt,
                    plotH
                } = scale;
                const x = xAt(i),
                    y = yAt(values[i]);
                ctx.save();
                ctx.scale(dpr, dpr);
                // guide line
                ctx.strokeStyle = 'rgba(13,110,253,.35)';
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.moveTo(x, padding.top);
                ctx.lineTo(x, padding.top + plotH);
                ctx.stroke();
                // point
                ctx.fillStyle = '#0d6efd';
                ctx.beginPath();
                ctx.arc(x, y, 4, 0, Math.PI * 2);
                ctx.fill();
                // tooltip
                const label = labels[i] + ' • ' + fmtMoney(values[i]);
                ctx.font = '12px system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial';
                const textW = ctx.measureText(label).width;
                const pad = 6;
                const boxW = textW + pad * 2;
                const boxH = 24;
                let boxX = x - boxW / 2;
                let boxY = Math.max(padding.top + 4, y - 32);
                boxX = Math.min(Math.max(boxX, padding.left), (width - padding.right) - boxW);
                // bg
                ctx.fillStyle = 'rgba(255,255,255,0.95)';
                ctx.strokeStyle = 'rgba(0,0,0,0.15)';
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.roundRect(boxX, boxY, boxW, boxH, 6);
                ctx.fill();
                ctx.stroke();
                // text
                ctx.fillStyle = '#0b2e66';
                ctx.textAlign = 'left';
                ctx.textBaseline = 'middle';
                ctx.fillText(label, boxX + pad, boxY + boxH / 2);
                ctx.restore();
            }

            function render() {
                const ctx = canvas.getContext('2d');
                clear(ctx);
                const scale = getScale();
                drawGrid(ctx, scale);
                drawSeries(ctx, scale);
                drawHover(ctx, scale, state.hoverIndex);
            }

            function nearestIndex(evt) {
                const rect = canvas.getBoundingClientRect();
                const xCss = (evt.clientX - rect.left);
                const plotW = width - padding.left - padding.right;
                const ratio = Math.max(1, values.length - 1);
                const rel = (xCss - padding.left) / Math.max(1, plotW);
                const idx = Math.round(rel * ratio);
                return Math.min(values.length - 1, Math.max(0, idx));
            }

            canvas.addEventListener('mousemove', (e) => {
                const i = nearestIndex(e);
                if (i !== state.hoverIndex) {
                    state.hoverIndex = i;
                    render();
                }
            });
            canvas.addEventListener('mouseleave', () => {
                state.hoverIndex = null;
                render();
            });

            // Handle resize
            let resizeTimer;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(setSize, 100);
            });

            // Initial paint
            setSize();
        })();
    </script>
@endpush
