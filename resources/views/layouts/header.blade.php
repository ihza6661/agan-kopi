<header class="navbar navbar-expand-lg bg-white border-bottom sticky-top" role="banner">
    <div class="container-fluid">
        <button class="btn btn-outline-secondary d-lg-none me-2" type="button" data-bs-toggle="offcanvas"
            data-bs-target="#appSidebar" aria-controls="appSidebar" aria-label="Toggling sidebar">
            <i class="bi bi-list"></i>
        </button>

        <button id="desktopSidebarToggle" class="btn btn-outline-secondary d-none d-lg-inline-flex me-2" type="button"
            aria-pressed="false" aria-label="Sembunyikan/Tampilkan sidebar">
            <i class="bi bi-layout-sidebar-inset"></i>
        </button>

        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ url('/') }}">
            <img src="{{ $appStoreLogoPath ? asset($appStoreLogoPath) : asset('assets/images/logo.webp') }}"
                alt="Logo" width="32" height="32" />
            <span class="fw-semibold">{{ $appStoreName ?? config('app.name', 'POS') }}</span>
        </a>

        <div class="ms-auto d-flex align-items-center gap-2">
            @auth
                <div class="dropdown me-2">
                    <button class="btn btn-light position-relative" type="button" id="notifMenu" data-bs-toggle="dropdown"
                        data-bs-display="static" aria-expanded="false" aria-label="Notifikasi">
                        <i class="bi bi-bell"></i>
                        @php($unread = auth()->user()->unreadNotifications()->limit(10)->get())
                        @if ($unread->count() > 0)
                            <span
                                class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                                <span class="visually-hidden">Ada notifikasi</span>
                            </span>
                        @endif
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end notif-dropdown p-2" aria-labelledby="notifMenu">
                        <li>
                            <h6 class="dropdown-header">Notifikasi</h6>
                        </li>
                        <li>
                            <hr class="dropdown-divider" />
                        </li>
                        @forelse($unread as $n)
                            @php($data = $n->data ?? [])
                            <li>
                                <div class="dropdown-item small">
                                    <div class="notif-card border rounded-3 p-2 bg-light">
                                        <div class="d-flex align-items-start gap-2">
                                            <div class="text-primary flex-shrink-0 mt-1">
                                                @php($type = $data['type'] ?? null)
                                                @if ($type === 'low_stock')
                                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                                @elseif($type === 'near_expiry')
                                                    <i class="bi bi-hourglass-split"></i>
                                                @else
                                                    <i class="bi bi-bell-fill"></i>
                                                @endif
                                            </div>
                                            <div class="flex-grow-1 min-w-0 text-break">
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <div class="fw-semibold">{{ $data['message'] ?? 'Notifikasi' }}</div>
                                                    @if (isset($type))
                                                        <span
                                                            class="badge bg-secondary text-wrap">{{ $type === 'low_stock' ? 'Stok Minimum' : ($type === 'near_expiry' ? 'Mendekati Kadaluarsa' : 'Info') }}</span>
                                                    @endif
                                                </div>
                                                @if (isset($data['name']))
                                                    <div class="text-muted">{{ $data['name'] }} (SKU:
                                                        {{ $data['sku'] ?? '-' }})</div>
                                                @endif
                                                <div
                                                    class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2">
                                                    @if (isset($data['edit_url']))
                                                        <a href="{{ $data['edit_url'] }}"
                                                            class="btn btn-link btn-sm p-0 link-primary text-decoration-none d-inline-flex align-items-center gap-1 notif-action">
                                                            <i class="bi bi-box-arrow-up-right"></i>
                                                            <span>Lihat produk</span>
                                                        </a>
                                                    @else
                                                        <span></span>
                                                    @endif
                                                    <div class="d-flex align-items-center gap-2 ms-auto">
                                                        <span
                                                            class="text-muted small">{{ optional($n->created_at)->diffForHumans() }}</span>
                                                        <form method="POST"
                                                            action="{{ route('notifications.read', $n->id) }}"
                                                            class="flex-shrink-0">
                                                            @csrf
                                                            @method('PUT')
                                                            <button type="submit"
                                                                class="btn btn-sm btn-outline-secondary">Tandai
                                                                dibaca</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li><span class="dropdown-item text-muted small">Tidak ada notifikasi baru.</span></li>
                        @endforelse
                        <li>
                            <hr class="dropdown-divider" />
                        </li>
                        <li class="px-2 pb-2">
                            <form method="POST" action="{{ route('notifications.read_all') }}">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-outline-secondary btn-sm notif-read-all-btn">
                                    <i class="bi bi-check2-all"></i>
                                    <span>Tandai semua dibaca</span>
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2" type="button"
                        id="userMenu" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu pengguna">
                        <i class="bi bi-person-circle fs-5"></i>
                        <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                        <li>
                            <h6 class="dropdown-header">Sistem</h6>
                        </li>
                        <li>
                            <hr class="dropdown-divider" />
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i>Keluar
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            @endauth

            @guest
                <a href="{{ route('login') }}" class="btn btn-primary">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                </a>
            @endguest
        </div>
    </div>
</header>
