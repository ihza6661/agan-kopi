@extends('layouts.app')

@section('title', 'Transaksi')

@section('content')
    <section class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h3 mb-0"><i class="bi bi-receipt"></i> Transaksi</h1>
        </div>

        <form id="filterForm" class="card shadow-sm mb-3">
            <div class="card-body row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label">Cari</label>
                    <input type="text" class="form-control" name="q" value="{{ $q }}"
                        placeholder="No/ket.">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">Semua</option>
                        @foreach ($statuses as $s)
                            <option value="{{ $s->value }}" {{ $status === $s->value ? 'selected' : '' }}>
                                {{ strtoupper($s->value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Metode</label>
                    <select class="form-select" name="method">
                        <option value="">Semua</option>
                        @foreach ($methods as $m)
                            <option value="{{ $m->value }}" {{ $method === $m->value ? 'selected' : '' }}>
                                {{ strtoupper($m->value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Dari</label>
                    <input type="date" class="form-control" name="from" value="{{ $from }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Sampai</label>
                    <input type="date" class="form-control" name="to" value="{{ $to }}">
                </div>
                <div class="col-6 col-md-1">
                    <label class="form-label">Per halaman</label>
                    <select class="form-select" name="per_page">
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="30">30</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div class="col-12 col-md-12 d-flex gap-2 justify-content-end">
                    <button type="button" id="btnReset" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i>
                        Reset</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Terapkan</button>
                </div>
            </div>
        </form>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table id="transactionsTable" class="table align-middle mb-0" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width:60px;">#</th>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Kasir</th>
                            <th>Metode</th>
                            <th>Status</th>
                            <th class="text-end">Total</th>
                            <th class="text-end" style="width:120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
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
            const $form = $('#filterForm');
            const $perPage = $form.find('select[name="per_page"]');
            const table = $('#transactionsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('transaksi.data') }}',
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
                        data: 'invoice',
                        name: 'invoice_number'
                    },
                    {
                        data: 'date',
                        name: 'created_at'
                    },
                    {
                        data: 'cashier',
                        name: 'user.name',
                        defaultContent: '',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'method',
                        name: 'payment_method'
                    },
                    {
                        data: 'status_badge',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'total',
                        name: 'total',
                        className: 'text-end'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-end'
                    },
                ],
                order: [
                    [2, 'desc']
                ],
                pageLength: 10,
            });

            const initialLen = parseInt($perPage.val() || '10', 10);
            if (!Number.isNaN(initialLen)) {
                table.page.len(initialLen).draw();
            }

            $form.on('submit', function(e) {
                e.preventDefault();
                table.ajax.reload();
            });

            $perPage.on('change', function() {
                const len = parseInt(this.value || '10', 10);
                table.page.len(len).draw();
            });

            $('#btnReset').on('click', function() {
                $form[0].reset();
                $perPage.val('10');
                table.page.len(10).draw();
                table.ajax.reload();
            });
        })();
    </script>
@endpush
