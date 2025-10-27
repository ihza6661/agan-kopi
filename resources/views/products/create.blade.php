@extends('layouts.app')

@section('title', 'Tambah Produk')

@section('content')
    <section class="container py-4">
        <header class="mb-3">
            <h1 class="h3 d-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i> Tambah Produk
            </h1>
        </header>

        <section class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('produk.store') }}" method="POST" novalidate>
                    @include('products._form', ['categories' => $categories])

                    <div class="d-flex gap-2 mt-3">
                        <a href="{{ route('produk.index') }}" class="btn btn-outline-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </section>
@endsection
