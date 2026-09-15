{{-- ============================================================
    HEADER ADMIN (global) — memuat lonceng notifikasi global.
    Dipakai bersama oleh semua halaman admin melalui @include('admin.partials.header')
    Data notifikasi ($adminNotifications, $adminUnreadCount) disuntikkan
    otomatis oleh AdminLayoutComposer pada semua halaman admin.
============================================================ --}}
<header class="admin-header">
    <div class="admin-header-left">
        @if (!empty($adminPageTitle ?? ''))
            <h1 class="admin-page-title">{{ $adminPageTitle }}</h1>
            @if (!empty($adminPageSubtitle ?? ''))
                <p class="admin-page-subtitle">{{ $adminPageSubtitle }}</p>
            @endif
        @endif
    </div>

    <div class="admin-header-actions">
        {{-- Lonceng Notifikasi GLOBAL --}}
        <div class="admin-notif-wrapper" data-admin-notif>
            <button type="button" class="header-icon-btn admin-notif-toggle" title="Notifikasi Aktivitas" aria-label="Notifikasi Aktivitas">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                @if (($adminUnreadCount ?? 0) > 0)
                    <span class="admin-notif-badge">{{ $adminUnreadCount > 99 ? '99+' : $adminUnreadCount }}</span>
                @endif
            </button>

            <div class="admin-notif-dropdown">
                <div class="admin-notif-dropdown-header">
                    <strong>Notifikasi Aktivitas</strong>
                    <span class="admin-notif-count">{{ $adminUnreadCount ?? 0 }} belum dibaca</span>
                </div>
                <div class="admin-notif-list">
                    @forelse (($adminNotifications ?? collect()) as $notif)
                        @php
                            $rawData = $notif->data;
                            $data = is_array($rawData)
                                ? $rawData
                                : (array) json_decode((string) $rawData, true);
                            $title = $data['title'] ?? 'Notifikasi';
                            $body = $data['body'] ?? '';
                            $icon = $data['icon'] ?? '🔔';
                            $link = route('admin.notifications.open', $notif->id);
                        @endphp
                        <a href="{{ $link }}" class="admin-notif-item {{ $notif->read_at ? '' : 'unread' }}">
                            <div class="admin-notif-item-icon">{{ $icon }}</div>
                            <div class="admin-notif-item-content">
                                <div class="admin-notif-item-title">
                                    @if (! $notif->read_at)<span class="admin-notif-dot"></span>@endif
                                    {{ $title }}
                                </div>
                                <div class="admin-notif-item-body">{{ $body }}</div>
                                <div class="admin-notif-item-time">{{ $notif->created_at ? $notif->created_at->diffForHumans() : '' }}</div>
                            </div>
                        </a>
                    @empty
                        <div class="admin-notif-empty">
                            <div class="admin-notif-empty-icon">🔔</div>
                            <p>Belum ada notifikasi aktivitas.</p>
                        </div>
                    @endforelse
                </div>
                <div class="admin-notif-dropdown-footer">
                    <a href="{{ route('admin.notifications.index') }}" class="admin-notif-view-all">Lihat semua notifikasi</a>
                    @if (($adminUnreadCount ?? 0) > 0)
                        <form method="POST" action="{{ route('admin.notifications.readAll') }}">
                            @csrf
                            <button type="submit" class="admin-notif-mark-all">Tandai semua dibaca</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <a href="{{ route('admin.profile') }}" class="admin-profile-pill" title="Profil Admin">
            <div class="admin-avatar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
            <span class="admin-profile-name">{{ session('account_name') ?? 'Admin' }}</span>
            <svg class="chevron-down" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </a>
    </div>
</header>

<script>
    (function () {
        var wrapper = document.querySelector('[data-admin-notif]');
        if (!wrapper) { return; }
        var toggle = wrapper.querySelector('.admin-notif-toggle');
        if (toggle) {
            toggle.addEventListener('click', function (e) {
                e.stopPropagation();
                wrapper.classList.toggle('open');
            });
        }
        document.addEventListener('click', function (e) {
            if (!wrapper.contains(e.target)) { wrapper.classList.remove('open'); }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { wrapper.classList.remove('open'); }
        });
    })();
</script>
