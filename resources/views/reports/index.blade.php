@extends('layouts.app')

@section('title', 'Laporan')

@section('content')
    <section class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h3 mb-0"><i class="bi bi-graph-up-arrow"></i> Laporan Penjualan</h1>
            <div class="d-flex gap-2">
                <a id="downloadCsv" class="btn btn-outline-success" href="{{ route('laporan.unduh', request()->query()) }}"
                    data-url="{{ route('laporan.unduh') }}">
                    <i class="bi bi-download"></i> Unduh CSV
                </a>
            </div>
        </div>

        <form id="reportFilter" class="card shadow-sm mb-3">
            <div class="card-body row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label">Dari</label>
                    <input type="date" class="form-control" name="from" value="{{ $filters['from'] }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Sampai</label>
                    <input type="date" class="form-control" name="to" value="{{ $filters['to'] }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">Semua</option>
                        @foreach ($statuses as $s)
                            <option value="{{ $s->value }}"
                                {{ ($filters['status'] ?? '') === $s->value ? 'selected' : '' }}>
                                {{ strtoupper($s->value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Metode</label>
                    <select class="form-select" name="method">
                        <option value="">Semua</option>
                        @foreach ($methods as $m)
                            <option value="{{ $m->value }}"
                                {{ ($filters['method'] ?? '') === $m->value ? 'selected' : '' }}>
                                {{ strtoupper($m->value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Periode</label>
                    <select class="form-select" name="period">
                        <option value="daily" {{ ($filters['period'] ?? 'daily') === 'daily' ? 'selected' : '' }}>Harian
                        </option>
                        <option value="monthly" {{ ($filters['period'] ?? 'daily') === 'monthly' ? 'selected' : '' }}>
                            Bulanan</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
                        <a href="{{ route('laporan') }}" class="btn btn-outline-secondary w-100 w-md-auto"><i
                                class="bi bi-x-circle"></i>
                            Reset</a>
                        <button type="submit" class="btn btn-primary w-100 w-md-auto"><i class="bi bi-search"></i>
                            Terapkan</button>
                    </div>
                </div>
            </div>
        </form>

        <section class="row g-3">
            <div class="col-12 col-xl-4">
                <div class="row row-cols-1 row-cols-md-2 g-3">
                    <div class="col">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex align-items-center">
                                <i class="bi bi-cash-coin text-success fs-2 me-3"></i>
                                <div>
                                    <p class="text-muted mb-0">Total Penjualan</p>
                                    <h2 class="h4 mb-0">@money($summary['total_sales'])</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex align-items-center">
                                <i class="bi bi-receipt text-primary fs-2 me-3"></i>
                                <div>
                                    <p class="text-muted mb-0">Total Transaksi</p>
                                    <h2 class="h4 mb-0">{{ number_format($summary['total_transactions'], 0, ',', '.') }}
                                    </h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex align-items-center">
                                <i class="bi bi-clipboard-data text-warning fs-2 me-3"></i>
                                <div>
                                    <p class="text-muted mb-0">Rata-rata/Transaksi</p>
                                    <h2 class="h4 mb-0">@money($summary['average_order_value'])</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex align-items-center">
                                <i class="bi bi-box-seam text-info fs-2 me-3"></i>
                                <div>
                                    <p class="text-muted mb-0">Total Item Terjual</p>
                                    <h2 class="h4 mb-0">{{ number_format($summary['total_items_sold'], 0, ',', '.') }}</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <section class="card shadow-sm mt-3">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="bi bi-star"></i> <strong>Top Produk</strong>
                    </div>
                    <div class="card-body">
                        @if ($topProducts->isEmpty())
                            <div class="text-muted">Tidak ada data.</div>
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
                                        @foreach ($topProducts as $p)
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
                </section>

                <section class="card shadow-sm mt-3">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="bi bi-hourglass-split"></i> <strong>Produk Perputaran Lambat</strong>
                    </div>
                    <div class="card-body">
                        @if (($slowProducts ?? collect())->isEmpty())
                            <div class="text-muted">Tidak ada data.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Produk</th>
                                            <th class="text-end">Qty</th>
                                            <th class="text-end">Total</th>
                                            <th class="text-end">Stok Saat Ini</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($slowProducts as $p)
                                            <tr>
                                                <td>{{ $p->name }}</td>
                                                <td class="text-end">{{ number_format($p->qty, 0, ',', '.') }}</td>
                                                <td class="text-end">@money($p->total)</td>
                                                <td class="text-end">{{ number_format($p->stock, 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </section>
            </div>

            <div class="col-12 col-xl-8">
                <div class="card shadow-sm">
                    <div class="table-responsive">
                        <table id="reportsTable" class="table align-middle mb-0" style="width:100%">
                            <thead>
                                <tr>
                                    <th style="width:60px;">#</th>
                                    <th>Tanggal</th>
                                    <th>Transaksi</th>
                                    <th>Item Terjual</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </section>
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables.min.css') }}">
@endpush

@push('script')
    <script src="{{ asset('assets/vendor/jquery-3.7.0.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables.min.js') }}"></script>
    <script>
        (function() {
            const $form = $('#reportFilter');
            const table = $('#reportsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('laporan.data') }}',
                    type: 'GET',
                    data: function(d) {
                        const fd = Object.fromEntries(new FormData($form[0]).entries());
                        return Object.assign(d, fd);
                    }
                },
                language: {
                    url: '{{ asset('assets/vendor/id.json') }}'
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'date',
                        name: 'date'
                    },
                    {
                        data: 'trx_count',
                        name: 'trx_count'
                    },
                    {
                        data: 'items_qty',
                        name: 'items_qty'
                    },
                    {
                        data: 'total',
                        name: 'total',
                        className: 'text-end'
                    },
                ],
                order: [
                    [1, 'asc']
                ],
                pageLength: 10,
            });

            $form.on('submit', function(e) {
                e.preventDefault();
                table.ajax.reload();
            });

            $('#downloadCsv').on('click', function(e) {
                const base = $(this).data('url');
                const fd = new FormData($form[0]);
                const params = new URLSearchParams(fd).toString();
                $(this).attr('href', base + '?' + params);
            });
        })();
    </script>
@endpush
