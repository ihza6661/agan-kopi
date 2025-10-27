@extends('layouts.app')

@section('title', 'Manajemen Kategori')

@section('content')
    <section class="container py-4">
        <header class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-tags"></i> Kategori
                </h1>
                <p class="text-muted mb-0">Kelola kategori produk.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('kategori.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
                </a>
            </div>
        </header>

        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        <section class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="categoriesTable" class="table align-middle" style="width:100%">
                        <caption>Daftar kategori produk</caption>
                        <thead>
                            <tr>
                                <th scope="col" style="width:60px;">#</th>
                                <th scope="col">Nama</th>
                                <th scope="col">Deskripsi</th>
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
            const table = $('#categoriesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('kategori.data') }}',
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
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'description',
                        name: 'description',
                        defaultContent: '',
                        render: (data) => data || ''
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
                    [1, 'asc']
                ],
                pageLength: 10,
            });
        })();
    </script>
@endpush
