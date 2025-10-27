@extends('layouts.app')

@section('title', 'Pembayaran')

@section('content')
    <section class="container py-4">
        <header class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-credit-card-2-front"></i> Pembayaran
                </h1>
                <p class="text-muted mb-0">Daftar pembayaran QRIS dari transaksi.</p>
            </div>
        </header>

        <form id="payFilter" class="card shadow-sm mb-3">
            <div class="card-body row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label">Cari</label>
                    <input type="text" class="form-control" name="q" value="{{ $q }}"
                        placeholder="Invoice / Order ID">
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
                <div class="col-6 col-md-1">
                    <label class="form-label">Provider</label>
                    <input type="text" class="form-control" name="provider" value="{{ $provider }}"
                        placeholder="midtrans">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Dari</label>
                    <input type="date" class="form-control" name="from" value="{{ $from }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Sampai</label>
                    <input type="date" class="form-control" name="to" value="{{ $to }}">
                </div>
                <div class="col-12 col-md-12 d-flex gap-2 justify-content-end">
                    <button type="button" id="btnReset" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i>
                        Reset</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Terapkan</button>
                </div>
            </div>
        </form>

        <section class="card shadow-sm">
            <div class="table-responsive">
                <table id="paymentsTable" class="table align-middle" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width:60px;">#</th>
                            <th>Invoice</th>
                            <th>Kasir</th>
                            <th>Metode</th>
                            <th>Provider</th>
                            <th>Status</th>
                            <th class="text-end">Jumlah</th>
                            <th>Dibuat</th>
                            <th>Dibayar</th>
                            <th class="text-end" style="width:140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
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
            const $form = $('#payFilter');
            const table = $('#paymentsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('pembayaran.data') }}',
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
                        name: 'transaction.invoice_number'
                    },
                    {
                        data: 'cashier',
                        name: 'transaction.user.name',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'method_text',
                        name: 'method'
                    },
                    {
                        data: 'provider',
                        name: 'provider'
                    },
                    {
                        data: 'status_badge',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'amount',
                        name: 'amount',
                        className: 'text-end'
                    },
                    {
                        data: 'created',
                        name: 'created_at'
                    },
                    {
                        data: 'paid',
                        name: 'paid_at',
                        orderable: false,
                        searchable: false
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
                    [7, 'desc']
                ],
                pageLength: 10,
            });

            $form.on('submit', function(e) {
                e.preventDefault();
                table.ajax.reload();
            });

            $('#btnReset').on('click', function() {
                $form[0].reset();
                table.ajax.reload();
            });
        })();
    </script>
@endpush
