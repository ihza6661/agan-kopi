@extends('layouts.app')

@section('title', 'Edit Pengguna')

@section('content')
    <section class="container py-4">
        <header class="mb-3">
            <h1 class="h3 d-flex align-items-center gap-2">
                <i class="bi bi-pencil-square"></i> Edit Pengguna
            </h1>
        </header>

        <section class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('pengguna.update', $user) }}" method="POST" novalidate>
                    @method('PUT')
                    @include('users._form', ['user' => $user])

                    <div class="d-flex gap-2 mt-3">
                        <a href="{{ route('pengguna.index') }}" class="btn btn-outline-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i> Perbarui
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </section>
@endsection
