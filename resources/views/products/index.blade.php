@extends('layouts.app')

@section('title', 'Manajemen Produk')

@section('content')
    <section class="container py-4">
        <header class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-box-seam"></i> Produk
                </h1>
                <p class="text-muted mb-0">Kelola produk dan stok.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('produk.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Produk
                </a>
            </div>
        </header>

        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        <section class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="productsTable" class="table align-middle" style="width:100%">
                        <caption>Daftar produk</caption>
                        <thead>
                            <tr>
                                <th scope="col" style="width:60px;">#</th>
                                <th scope="col">SKU</th>
                                <th scope="col">Nama</th>
                                <th scope="col">Kategori</th>
                                <th scope="col" class="text-end">Harga</th>
                                <th scope="col" class="text-end">Stok</th>
                                <th scope="col" class="text-end" style="width:180px;">Aksi</th>
                            </tr>
                        </thead>
                    </table>
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
            const table = $('#productsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('produk.data') }}',
                    type: 'GET'
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
                        data: 'sku',
                        name: 'sku'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'category',
                        name: 'category.name',
                        defaultContent: ''
                    },
                    {
                        data: 'price',
                        name: 'price',
                        className: 'text-end'
                    },
                    {
                        data: 'stock',
                        name: 'stock',
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
                    [2, 'asc']
                ],
                pageLength: 10,
            });
        })();
    </script>
@endpush
