@extends('layouts.app')

@section('title', $title ?? 'Halaman')

@section('content')
<section class="container py-4">
    <header class="mb-4">
        <h1 class="h3 d-flex align-items-center gap-2">
            <i class="bi bi-folder"></i> {{ $title ?? 'Halaman' }}
        </h1>
        <p class="text-muted mb-0">Halaman ini masih dalam pengembangan.</p>
    </header>

    <div class="card shadow-sm">
        <div class="card-body">
            <p>Konten untuk <strong>{{ $title ?? 'Halaman' }}</strong> akan segera hadir.</p>
        </div>
    </div>
</section>
@endsection
