{{-- ============================================================
    HEADER ADMIN (global)  memuat lonceng notifikasi global.
    Dipakai bersama oleh semua halaman admin melalui
    @include('admin.partials.header')
    Data notifikasi ($adminNotifications, $adminUnreadCount,
    $adminNotifSignature) disuntikkan otomatis oleh AdminLayoutComposer
    pada semua halaman admin.

    Realtime: project ini TIDAK memakai WebSocket/Echo/Livewire, sehingga
    lonceng memakai polling ringan (lihat endpoint admin.notifications.poll).
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
        <div class="admin-notif-wrapper" data-admin-notif data-notif-poll-url="{{ route('admin.notifications.poll') }}" data-notif-signature="{{ $adminNotifSignature ?? '' }}" data-notif-initial-unread="{{ $adminUnreadCount ?? 0 }}">
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
                @include('admin.partials.notification-dropdown')
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
            <span class="admin-role-badge">ADMIN</span>
        </a>
    </div>
</header>

<script>
    (function () {
        var wrapper = document.querySelector('[data-admin-notif]');
        if (!wrapper) { return; }

        var toggle = wrapper.querySelector('.admin-notif-toggle');
        var dropdown = wrapper.querySelector('.admin-notif-dropdown');
        var pollUrl = wrapper.getAttribute('data-notif-poll-url');

        // Interval polling ringan. Notifikasi tidak pernah hilang: nilainya
        // selalu diambil ulang dari database, bukan disimpan di browser.
        var POLL_INTERVAL = 20000;

        var signature = wrapper.getAttribute('data-notif-signature') || '';
        var unreadCount = parseInt(wrapper.getAttribute('data-notif-initial-unread'), 10) || 0;
        var timer = null;

/* ---------- Badge merah pada ikon lonceng ----------
         * Badge TIDAK pernah ikut menentukan ukuran tombol: markup & CSS-nya
         * sama persis dengan desain awal, yaitu elemen span.admin-notif-badge
         * yang position:absolute di dalam .admin-notif-wrapper (position:relative).
         * Karena itu badge dibuat/diHAPUS (bukan disembunyikan) sesuai kondisi
         * unread, persis seperti hasil render server, sehingga DOM ikon lonceng
         * selalu identik dengan desain awal dan tidak pernah ada badge "0".
         */
        function renderBadge() {
            var badge = toggle ? toggle.querySelector('.admin-notif-badge') : null;

            if (unreadCount > 0) {
                var label = unreadCount > 99 ? '99+' : String(unreadCount);

                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'admin-notif-badge';
                    toggle.appendChild(badge);
                }

                if (badge.textContent !== label) {
                    badge.textContent = label;
                }
            } else if (badge) {
                badge.parentNode.removeChild(badge);
            }
        }

        /* ---------- Isi dropdown ---------- */
        function renderDropdown(html) {
            if (!dropdown) { return; }
            dropdown.innerHTML = html;
        }

        /* ---------- Ambil state notifikasi dari server ---------- */
        function refresh(withItems) {
            if (!pollUrl || typeof window.fetch !== 'function') { return; }
            if (document.hidden && !withItems) { return; }

            window.fetch(pollUrl + (withItems ? '?items=1' : ''), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            })
                .then(function (res) { return res.ok ? res.json() : null; })
                .then(function (data) {
                    if (!data) { return; }

                    var count = parseInt(data.unread_count, 10);
                    if (!isNaN(count)) {
                        unreadCount = count;
                        renderBadge();
                    }

                    // Daftar notifikasi hanya dimuat ulang bila statusnya berubah.
                    if (withItems || (data.signature && data.signature !== signature)) {
                        if (typeof data.html === 'string') {
                            renderDropdown(data.html);
                        }
                    }

                    if (data.signature) {
                        signature = data.signature;
                    }
                })
                .catch(function () {
                    // Polling gagal (offline / timeout) tidak boleh mengganggu UI.
                });
        }

        function startPolling() {
            if (timer || !pollUrl) { return; }
            timer = window.setInterval(function () { refresh(false); }, POLL_INTERVAL);
        }

        function stopPolling() {
            if (!timer) { return; }
            window.clearInterval(timer);
            timer = null;
        }

        /* ---------- Buka / tutup dropdown ---------- */
        if (toggle) {
            toggle.addEventListener('click', function (e) {
                e.stopPropagation();
                var willOpen = !wrapper.classList.contains('open');
                wrapper.classList.toggle('open');
                if (willOpen) { refresh(true); }
            });
        }

        document.addEventListener('click', function (e) {
            if (!wrapper.contains(e.target)) { wrapper.classList.remove('open'); }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { wrapper.classList.remove('open'); }
        });

        /* ---------- Klik notifikasi = tandai sudah dibaca ---------- */
        // Server tetap menandai notifikasi sebagai dibaca (route
        // admin.notifications.open). Perbaruan di bawah ini hanya agar badge
        // langsung terasa responsif tanpa menunggu halaman selesai dimuat.
        if (dropdown) {
            dropdown.addEventListener('click', function (e) {
                var item = e.target && e.target.closest ? e.target.closest('.admin-notif-item') : null;
                if (!item || item.getAttribute('data-notif-unread') !== '1') { return; }

                item.setAttribute('data-notif-unread', '0');
                item.classList.remove('unread');

                var dot = item.querySelector('.admin-notif-dot');
                if (dot && dot.parentNode) { dot.parentNode.removeChild(dot); }

                if (unreadCount > 0) {
                    unreadCount -= 1;
                    renderBadge();
                }
            });
        }

        /* ---------- Polling: dijeda saat tab tidak terlihat ---------- */
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                stopPolling();
            } else {
                refresh(false);
                startPolling();
            }
        });

        renderBadge();
        startPolling();
    })();
</script>