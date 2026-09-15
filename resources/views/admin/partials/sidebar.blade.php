{{-- ============================================================
    SIDEBAR ADMIN (global)
    Dipakai bersama oleh semua halaman admin melalui @include('admin.partials.sidebar', ['activeMenu' => '...'])
    Data badge ($adminSidebar) disuntikkan otomatis oleh AdminLayoutComposer.
============================================================ --}}
<aside class="admin-sidebar">
    <a href="{{ route('admin.dashboard') }}" class="sidebar-brand">
        <div class="admin-shield-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
            </svg>
        </div>
        <span class="sidebar-brand-text">Summit Station</span>
        <span class="sidebar-brand-sub">Admin Panel</span>
    </a>

    <div class="sidebar-scroll">
        <span class="sidebar-section-label">Menu Utama</span>

        <ul class="sidebar-menu">
        {{-- Dashboard --}}
        <li>
            <a href="{{ route('admin.dashboard') }}" class="menu-link {{ ($activeMenu ?? '') === 'dashboard' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                <span>Dashboard</span>
            </a>
        </li>

        {{-- Alat --}}
        <li>
            <a href="{{ route('admin.alat') }}" class="menu-link {{ ($activeMenu ?? '') === 'alat' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg>
                <span>Alat</span>
            </a>
        </li>

        {{-- Pembayaran --}}
        <li>
            <a href="{{ route('admin.pembayaran') }}" class="menu-link {{ ($activeMenu ?? '') === 'pembayaran' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                <span>Pembayaran</span>
                @if (($adminSidebar['pembayaran'] ?? 0) > 0)
                    <span class="admin-sidebar-badge">{{ $adminSidebar['pembayaran'] > 99 ? '99+' : $adminSidebar['pembayaran'] }}</span>
                @endif
            </a>
        </li>

        {{-- Penyewaan --}}
        <li>
            <a href="{{ route('admin.penyewaan') }}" class="menu-link {{ ($activeMenu ?? '') === 'penyewaan' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span>Penyewaan</span>
                @if (($adminSidebar['penyewaan'] ?? 0) > 0)
                    <span class="admin-sidebar-badge">{{ $adminSidebar['penyewaan'] > 99 ? '99+' : $adminSidebar['penyewaan'] }}</span>
                @endif
            </a>
        </li>

        {{-- Pengembalian --}}
        <li>
            <a href="{{ route('admin.pengembalian') }}" class="menu-link {{ ($activeMenu ?? '') === 'pengembalian' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                    <polyline points="3 3 3 8 8 8"></polyline>
                </svg>
                <span>Pengembalian</span>
                @if (($adminSidebar['pengembalian'] ?? 0) > 0)
                    <span class="admin-sidebar-badge">{{ $adminSidebar['pengembalian'] > 99 ? '99+' : $adminSidebar['pengembalian'] }}</span>
                @endif
            </a>
        </li>

        {{-- Pengembalian Dana --}}
        <li>
            <a href="{{ route('admin.refund') }}" class="menu-link {{ ($activeMenu ?? '') === 'refund' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 10h4l3-6 4 12 3-6h4"></path>
                </svg>
                <span>Pengembalian Dana</span>
                @if (($adminSidebar['refund'] ?? 0) > 0)
                    <span class="admin-sidebar-badge {{ ($adminSidebar['refund'] ?? 0) > 0 ? 'badge-red' : '' }}">{{ $adminSidebar['refund'] > 99 ? '99+' : $adminSidebar['refund'] }}</span>
                @endif
            </a>
        </li>

        {{-- Data Pengguna --}}
        <li>
            <a href="{{ route('admin.users') }}" class="menu-link {{ ($activeMenu ?? '') === 'users' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>Data Pengguna</span>
            </a>
        </li>
        </ul>

        <div class="sidebar-group-sep"></div>
        <span class="sidebar-section-label">Lainnya</span>

        <ul class="sidebar-menu">
        {{-- Website --}}
        <li>
            <a href="{{ route('admin.website') }}" class="menu-link {{ ($activeMenu ?? '') === 'website' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="2" y1="12" x2="22" y2="12"></line>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                </svg>
                <span>Website</span>
            </a>
        </li>

        {{-- Laporan --}}
        <li>
            <a href="{{ route('admin.laporan') }}" class="menu-link {{ ($activeMenu ?? '') === 'laporan' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                <span>Laporan</span>
            </a>
        </li>

        {{-- Notifikasi --}}
        <li>
            <a href="{{ route('admin.notifications.index') }}" class="menu-link {{ ($activeMenu ?? '') === 'notifications' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <span>Notifikasi</span>
                @if (($adminUnreadCount ?? 0) > 0)
                    <span class="admin-sidebar-badge">{{ $adminUnreadCount > 99 ? '99+' : $adminUnreadCount }}</span>
                @endif
            </a>
        </li>

        {{-- Profil --}}
        <li class="menu-account-item">
            <a href="{{ route('admin.profile') }}" class="menu-link {{ ($activeMenu ?? '') === 'profile' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Profil</span>
            </a>
        </li>
        </ul>

        <div class="sidebar-footer">
            <div class="sidebar-footer-mount">
                <svg viewBox="0 0 32 12" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 11 L8 3 L12 8 L16 1 L22 10 L26 5 L31 11"></path>
                </svg>
                <span>Explore &bull; Rent &bull; Repeat</span>
            </div>
        </div>
    </div>

    <div class="sidebar-bottom">
        <div class="sidebar-logout-sep"></div>
        <form method="POST" action="{{ route('logout') }}" id="logout-form" style="margin: 0; width: 100%;">
            @csrf
            <button type="button" data-logout-open class="menu-link logout-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Keluar</span>
            </button>
        </form>
    </div>
</aside>

@include('partials.logout-modal')

<script>
    (function () {
        var logoutModal = document.getElementById('logout-modal');
        var logoutForm = document.getElementById('logout-form');
        if (!logoutModal) { return; }

        document.querySelectorAll('[data-logout-open]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                logoutModal.hidden = false;
                document.body.classList.add('modal-open');
            });
        });

        document.querySelectorAll('[data-close-logout]').forEach(function (el) {
            el.addEventListener('click', function () {
                logoutModal.hidden = true;
                document.body.classList.remove('modal-open');
            });
        });

        var confirmLogout = document.getElementById('confirm-logout');
        if (confirmLogout && logoutForm) {
            confirmLogout.addEventListener('click', function () {
                logoutForm.submit();
            });
        }
    })();
</script>
