<footer class="border-top bg-white mt-auto site-footer" role="contentinfo">
    <div class="container py-3 small text-muted">
        <div class="row g-3 align-items-center justify-content-between text-center text-md-start">
            <div
                class="col-12 col-md-auto d-flex align-items-center justify-content-center justify-content-md-start gap-2">
                <img src="{{ $appStoreLogoPath ? asset($appStoreLogoPath) : asset('assets/images/logo.webp') }}"
                    alt="Logo" width="24" height="24" class="border rounded bg-white p-1" />
                <span class="text-truncate" style="max-width: 60vw">
                    &copy; {{ date('Y') }} {{ $appStoreName ?? config('app.name', 'POS') }}
                </span>
            </div>

            @if (!empty($appStoreAddress))
                <div class="col-12 col-sm-auto">
                    <span class="d-inline-flex align-items-center gap-1">
                        <i class="bi bi-geo-alt"></i>
                        <span class="text-wrap">{{ $appStoreAddress }}</span>
                    </span>
                </div>
            @endif

            @if (!empty($appStorePhone))
                <div class="col-12 col-sm-auto">
                    <a class="link-secondary text-decoration-none d-inline-flex align-items-center gap-1"
                        href="tel:{{ preg_replace('/\s+/', '', $appStorePhone) }}">
                        <i class="bi bi-telephone"></i>
                        <span>{{ $appStorePhone }}</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
    @push('css')
        <style>
            @media (max-width: 575.98px) {
                .site-footer .text-truncate {
                    max-width: 70vw;
                }
            }
        </style>
    @endpush
</footer>
