{{-- Summit Station - Shared Interactive Navbar --}}
@php
    $cartCount = count(session('cart_items', []));
    $navNotifications = collect();
    $navUnreadCount = 0;
    if (session('account_role') === 'customer' && session('account_id')) {
        $navUser = \App\Models\User::find(session('account_id'));
        if ($navUser) {
            $navUnreadCount = $navUser->unreadNotifications()->count();
            $navNotifications = $navUser->notifications()->latest()->limit(8)->get();
        }
    }
@endphp
<div class="top-banner-line"></div>
<header class="site-header" id="siteHeader">
    <div class="header-container">
        <a href="{{ url('/') }}" class="brand-logo" aria-label="{{ $siteSettings['site_name'] ?? 'Summit Station' }} - Beranda">
            <div class="logo-badge">
                <img src="{{ asset('images/logo.png') }}" alt="{{ $siteSettings['site_name'] ?? 'Summit Station' }} Logo">
            </div>
            <span>{{ $siteSettings['site_name'] ?? 'Summit Station' }}</span>
        </a>

        <nav class="main-nav" id="mainNav" aria-label="Navigasi utama">
            <ul class="nav-links">
                <li><a href="{{ url('/') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Beranda</a></li>
                <li><a href="{{ route('catalog', ['sort' => request('sort')]) }}" class="{{ request()->routeIs('catalog', 'catalog.show', 'catalog.bundle') ? 'active' : '' }}">Katalog</a></li>
                <li><a href="{{ route('store.location') }}" class="{{ request()->routeIs('store.location') ? 'active' : '' }}">Lokasi Toko</a></li>
                <li><a href="{{ route('contact.admin') }}" class="{{ request()->routeIs('contact.admin') ? 'active' : '' }}">Hubungi Admin</a></li>
                <li><a href="{{ route('history') }}" class="{{ request()->routeIs('history') ? 'active' : '' }}">Riwayat</a></li>
            </ul>
        </nav>

        <div class="header-actions">
            <a href="{{ route('cart') }}" class="icon-btn cart-btn" title="Keranjang Belanja" aria-label="Keranjang Belanja">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 6h-3c0-2.21-1.79-4-4-4S8 3.79 8 6H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-7-2c1.1 0 2 .9 2 2h-4c0-1.1.9-2 2-2zm7 16H5V8h3v2c0 .55.45 1 1 1s1-.45 1-1V8h4v2c0 .55.45 1 1 1s1-.45 1-1V8h3v12z"/>
                </svg>
                @if ($cartCount > 0)
                    <span class="cart-badge">{{ $cartCount > 9 ? '9+' : $cartCount }}</span>
                @endif
            </a>

            @if (session('account_role') === 'customer')
                <div class="notif-wrapper" data-notif-menu>
                    <button type="button" class="icon-btn notif-toggle" title="Notifikasi" aria-label="Notifikasi">
                        <svg class="icon-stroke" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        @if ($navUnreadCount > 0)
                            <span class="notif-badge">{{ $navUnreadCount > 9 ? '9+' : $navUnreadCount }}</span>
                        @endif
                    </button>
                    <div class="notif-dropdown">
                        <div class="notif-dropdown-header">
                            <strong>Notifikasi</strong>
                            @if ($navUnreadCount > 0)
                                <form method="POST" action="{{ route('notifications.readAll') }}">
                                    @csrf
                                    <button type="submit" class="notif-mark-all">Tandai semua dibaca</button>
                                </form>
                            @endif
                        </div>
                        <div class="notif-list">
                            @forelse ($navNotifications as $notif)
                                @php
                                    $rawData = $notif->data;
                                    $data = is_array($rawData)
                                        ? $rawData
                                        : (array) json_decode((string) $rawData, true);
                                    $title = $data['title'] ?? 'Notifikasi';
                                    $body = $data['body'] ?? '';
                                    $icon = $data['icon'] ?? '🔔';
                                @endphp
                                <a href="{{ route('notifications.open', $notif->id) }}" class="notif-item {{ $notif->read_at ? '' : 'unread' }}">
                                    <div class="notif-item-icon">{{ $icon }}</div>
                                    <div class="notif-item-content">
                                        <div class="notif-item-title">{{ $title }}</div>
                                        <div class="notif-item-body">{!! nl2br(e($body)) !!}</div>
                                        <div class="notif-item-time">{{ $notif->created_at ? $notif->created_at->diffForHumans() : '' }}</div>
                                    </div>
                                </a>
                            @empty
                                <div class="notif-empty">Belum ada notifikasi.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif

            <div class="user-menu-wrapper" data-user-menu>
                @if (session('account_id'))
                    @php
                        $rawUsername = session('account_username');
                        if (empty($rawUsername)) {
                            $rawUsername = session('account_name', '');
                        }
                        $cleanUsername = ltrim(trim((string) $rawUsername), '@');
                        $navbarInitial = $cleanUsername !== '' ? strtoupper(mb_substr($cleanUsername, 0, 1, 'UTF-8')) : 'U';
                    @endphp
                    <button type="button" class="icon-btn user-toggle user-toggle-avatar" aria-haspopup="true" aria-expanded="false" title="Profil {{ session('account_name') }}">
                        @if (session('account_avatar'))
                            <img class="user-avatar-img" src="{{ session('account_avatar') }}" alt="Avatar {{ session('account_name') }}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <span class="user-avatar-initial" style="display:none">{{ $navbarInitial }}</span>
                        @else
                            <span class="user-avatar-initial">{{ $navbarInitial }}</span>
                        @endif
                    </button>
                    <div class="user-dropdown">
                        <div class="user-name">{{ session('account_name') }} ({{ ucfirst(session('account_role', 'customer')) }})</div>
                        @if (session('account_role') === 'admin')
                            <a href="{{ route('admin.dashboard') }}" style="color: #185d31; font-weight: 700;">⚙️ Dashboard Admin</a>
                        @endif
                        <a href="{{ route('profile') }}">Profil Saya</a>
                        <form method="post" action="{{ route('logout') }}" id="logout-form">
                            @csrf
                            <button type="button" data-logout-open>Keluar</button>
                        </form>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="icon-btn" title="Masuk Akun" aria-label="Masuk Akun">
                        <svg class="icon-stroke" width="18" height="18" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </a>
                @endif
            </div>

            <button type="button" class="nav-hamburger" id="navToggle" aria-label="Buka menu navigasi" aria-expanded="false" aria-controls="mainNav">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>
</header>

@include('partials.logout-modal')
