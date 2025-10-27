<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $appStoreName ?? config('app.name', 'POS') }} - @yield('title', 'Dashboard')</title>
    <meta name="description" content="" />
    <meta name="author" content="Mariani Krismonika" />
    <link rel="icon" type="image/webp" href="{{ asset('assets/images/logo.webp') }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Google Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Lato:wght@300;400;700&display=swap"
        rel="stylesheet">

    {{-- CSS Libraries --}}
    <link href="{{ asset('assets/vendor/bootstrap.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/vendor/datatables.min.css') }}" rel="stylesheet" />

    {{-- Bootstrap Icons --}}
    <link href="{{ asset('assets/vendor/bootstrap-icons-1.13.1/bootstrap-icons.min.css') }}" rel="stylesheet" />

    {{-- Custom CSS --}}
    <link href="{{ asset('assets/css/custom-css.css') }}" rel="stylesheet" />

    @stack('css')
</head>

<body>
    @include('layouts.header')
    @include('layouts.sidebar')
    <main>
        @yield('content')
    </main>
    @include('layouts.footer')

    {{-- JS Libraries --}}
    <script src="{{ asset('assets/vendor/jquery-3.7.0.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/bootstrap.bundle.min.js') }}"></script>

    {{-- Custom JS --}}
    <script src="{{ asset('assets/js/custom-js.js') }}"></script>

    @stack('script')
</body>

</html>
