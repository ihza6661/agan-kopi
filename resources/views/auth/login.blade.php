<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Masuk - {{ $appStoreName ?? config('app.name', 'POS') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="{{ asset('assets/vendor/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap-icons-1.13.1/bootstrap-icons.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/custom-css.css') }}" rel="stylesheet" />
    <style>
        body {
            background: #f6f8fb;
        }

        .auth-wrapper {
            min-height: 100vh;
            display: grid;
            place-items: center;
        }

        .auth-card {
            max-width: 460px;
            width: 100%;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: .75rem;
            justify-content: center;
        }

        .brand-title {
            font-weight: 700;
            letter-spacing: .3px;
        }
    </style>
    <script>
        document.documentElement.classList.add('js');
    </script>
</head>

<body>
    <main class="auth-wrapper container py-4 py-md-5">
        <div class="auth-card card shadow-sm border-0">
            <div class="card-body p-4 p-md-4">
                <header class="mb-3 text-center">
                    <div class="brand">
                        <img src="{{ asset('assets/images/logo.webp') }}" alt="Logo" width="56" height="56"
                            class="rounded-circle">
                        <div class="text-start">
                            <div class="brand-title">{{ $appStoreName ?? config('app.name', 'POS') }}</div>
                            <div class="small text-muted">Sistem Point of Sale</div>
                        </div>
                    </div>
                    <h1 class="h5 mt-3 mb-0">Masuk ke Akun Anda</h1>
                    <p class="text-muted small mt-1 mb-0">Gunakan email dan kata sandi yang terdaftar.</p>
                </header>

                @if (session('status'))
                    <div class="alert alert-success" role="status">{{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
                @endif

                <form id="loginForm" action="{{ url('/login') }}" method="POST" novalidate>
                    @csrf
                    <fieldset class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input id="email" name="email" type="email"
                            class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}"
                            autocomplete="email" required placeholder="nama@contoh.com">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </fieldset>

                    <fieldset class="mb-2">
                        <label for="password" class="form-label mb-1">Kata Sandi</label>
                        <div class="input-group has-validation">
                            <input id="password" name="password" type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                autocomplete="current-password" required placeholder="••••••••">
                            <button type="button" class="btn btn-outline-secondary" id="btnTogglePwd"
                                title="Tampilkan kata sandi" aria-label="Tampilkan kata sandi">
                                <i class="bi bi-eye"></i>
                            </button>
                            <span class="input-group-text" id="capsLockHint" style="display:none;"
                                title="Caps Lock aktif">
                                <i class="bi bi-capslock"></i>
                            </span>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-text" id="pwdHelp" style="display:none;">Caps Lock aktif – periksa kembali
                            huruf besar/kecil.</div>
                    </fieldset>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember"
                                value="1" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">Ingat saya</label>
                        </div>
                        <!-- <a href="#" class="small text-muted">Lupa kata sandi?</a> -->
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary" id="btnLogin">
                            <span class="spinner-border spinner-border-sm me-2" id="btnSpinner" style="display:none;"
                                role="status" aria-hidden="true"></span>
                            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                        </button>
                    </div>
                </form>

                
            </div>
        </div>
    </main>

    <script>
        (function() {
            const form = document.getElementById('loginForm');
            const btn = document.getElementById('btnLogin');
            const spinner = document.getElementById('btnSpinner');
            const pwd = document.getElementById('password');
            const btnToggle = document.getElementById('btnTogglePwd');
            const capsHint = document.getElementById('capsLockHint');
            const pwdHelp = document.getElementById('pwdHelp');

            form.addEventListener('submit', function() {
                btn.setAttribute('disabled', 'disabled');
                spinner.style.display = '';
            });

            btnToggle.addEventListener('click', function() {
                const isPwd = pwd.type === 'password';
                pwd.type = isPwd ? 'text' : 'password';
                const icon = btnToggle.querySelector('i');
                if (icon) icon.className = isPwd ? 'bi bi-eye-slash' : 'bi bi-eye';
                const label = isPwd ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi';
                btnToggle.setAttribute('title', label);
                btnToggle.setAttribute('aria-label', label);
                pwd.focus();
            });

            function handleCaps(e) {
                const caps = e.getModifierState && e.getModifierState('CapsLock');
                capsHint.style.display = caps ? '' : 'none';
                pwdHelp.style.display = caps ? '' : 'none';
            }
            pwd.addEventListener('keyup', handleCaps);
            pwd.addEventListener('keydown', handleCaps);
        })();
    </script>
    <script src="{{ asset('assets/vendor/bootstrap.bundle.min.js') }}"></script>
</body>

</html>
