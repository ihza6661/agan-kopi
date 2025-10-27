@extends('layouts.app')

@section('title', 'Edit Kategori')

@section('content')
    <section class="container py-4">
        <header class="mb-3">
            <h1 class="h3 d-flex align-items-center gap-2">
                <i class="bi bi-pencil-square"></i> Edit Kategori
            </h1>
        </header>

        <section class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('kategori.update', $category) }}" method="POST" novalidate>
                    @method('PUT')
                    @include('categories._form', ['category' => $category])

                    <div class="d-flex gap-2">
                        <a href="{{ route('kategori.index') }}" class="btn btn-outline-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i> Perbarui
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </section>
@endsection
