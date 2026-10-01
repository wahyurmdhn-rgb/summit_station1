{{--
    Summit Station - Footer Standard (MASTER DESIGN)
    Sumber desain: Footer Beranda User. Satu component reusable untuk seluruh website.
    Override context dengan mengirim variabel: @include('partials.footer', ['footerContext' => 'user'|'admin'])
--}}
@php
    $footerContext = $footerContext ?? 'user';
    $footerSiteName = $siteSettings['site_name'] ?? 'Summit Station';
    $footerHotline = $siteSettings['hotline'] ?? '+6281123456789';
    $footerWaUrl = 'https://wa.me/' . preg_replace('/[^0-9]/', '', $footerHotline);
@endphp
<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-top">
            <div class="footer-col footer-col-brand">
                <div class="footer-brand">
                    <div class="logo-badge" style="width: 38px; height: 38px; overflow: hidden; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <img src="{{ asset('images/logo.png') }}" alt="{{ $footerSiteName }} Logo" style="width: 100%; height: 100%; object-fit: contain;">
                    </div>
                    <span>{{ $footerSiteName }}</span>
                </div>
                <p class="footer-subtitle">
                    Penyewaan perlengkapan outdoor kelas profesional untuk para penjelajah modern. Siap menemani setiap langkah petualanganmu.
                </p>
            </div>

            @if ($footerContext === 'admin')
                <div class="footer-col">
                    <h4 class="footer-heading">Navigasi Admin</h4>
                    <ul class="footer-links">
                        <li><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li><a href="{{ route('admin.alat') }}">Kelola Alat</a></li>
                        <li><a href="{{ route('admin.penyewaan') }}">Penyewaan</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4 class="footer-heading">Manajemen</h4>
                    <ul class="footer-links">
                        <li><a href="{{ route('admin.pembayaran') }}">Pembayaran</a></li>
                        <li><a href="{{ route('admin.pengembalian') }}">Pengembalian</a></li>
                        <li><a href="{{ route('admin.users') }}">Data Pengguna</a></li>
                    </ul>
                </div>
            @else
                <div class="footer-col">
                    <h4 class="footer-heading">Navigasi</h4>
                    <ul class="footer-links">
                        <li><a href="{{ url('/') }}">Beranda</a></li>
                        <li><a href="{{ route('catalog') }}">Katalog</a></li>
                        <li><a href="{{ route('store.location') }}">Lokasi Toko</a></li>
                        <li><a href="{{ route('history') }}">Riwayat Penyewaan</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4 class="footer-heading">Layanan</h4>
                    <ul class="footer-links">
                        <li><a href="{{ route('cart') }}">Keranjang Belanja</a></li>
                        <li><a href="{{ route('contact.admin') }}">Hubungi Admin</a></li>
                        <li><a href="{{ route('profile') }}">Profil Saya</a></li>
                        @if (!session('account_id'))
                            <li><a href="{{ route('login') }}">Masuk Akun</a></li>
                        @endif
                    </ul>
                </div>
            @endif

            <div class="footer-col footer-col-social">
                <h4 class="footer-heading">Ikuti &amp; Hubungi Kami</h4>
                <div class="footer-social-list">
                    <a href="https://www.instagram.com/summit_station/" target="_blank" rel="noopener" class="footer-social-link">
                        <span class="footer-social-icon footer-social-icon--instagram">
                            @include('partials.social-brand-icon', ['brand' => 'instagram'])
                        </span>
                        <span class="footer-social-text">
                            <span class="footer-social-label">Instagram</span>
                            <span class="footer-social-handle">@summit_station</span>
                        </span>
                    </a>
                    <a href="{{ $footerWaUrl }}" target="_blank" rel="noopener" class="footer-social-link">
                        <span class="footer-social-icon footer-social-icon--wa">
                            @include('partials.social-brand-icon', ['brand' => 'whatsapp'])
                        </span>
                        <span class="footer-social-text">
                            <span class="footer-social-label">WhatsApp</span>
                            <span class="footer-social-handle">Hubungi Kami</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>

        <div class="footer-divider"></div>

        <div class="footer-bottom">
            <p class="footer-copyright">
                &copy; 2026 {{ $footerSiteName }} Expedition Rentals. Seluruh hak cipta dilindungi.
            </p>
        </div>
    </div>
</footer>
