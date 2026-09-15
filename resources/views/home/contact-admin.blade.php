<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}">
    <title>Hubungi Admin - Summit Station</title>
    <link rel="stylesheet" href="{{ asset('css/summit-contact-admin.css') . '?v=' . filemtime(public_path('css/summit-contact-admin.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-footer.css') . '?v=' . filemtime(public_path('css/summit-footer.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-navbar.css') . '?v=' . filemtime(public_path('css/summit-navbar.css')) }}">
</head>
<body>
    @include('layouts.navbar')

    <main class="contact-page">
        <section class="contact-hero">
            <div>
                <p class="eyebrow"><span></span> KOMUNIKASI BASECAMP</p>
                <h1>Kirim Pesan ke Summit Station</h1>
                <p class="lead">Baik Anda sedang merencanakan pendakian atau membutuhkan dukungan alat segera, koordinator ekspedisi kami siap membantu.</p>
                <p style="margin-top: 14px; padding: 12px 16px; background: #185d31; color: #fff; border-radius: 8px; font-size: 14px; line-height: 1.6;">Lupa kata sandi? Hubungi admin kami melalui email <strong style="color: #fff;">{{ $siteSettings['email'] ?? 'support@summitstation.id' }}</strong> atau WhatsApp <strong style="color: #fff;">{{ $siteSettings['hotline'] ?? '+62 811-2345-6789' }}</strong> untuk meminta bantuan pemulihan akun Anda.</p>
            </div>
            <img class="contact-logo" src="{{ asset('images/logo-asli.png') . '?v=' . filemtime(public_path('images/logo-asli.png')) }}" alt="Summit Station emblem">
        </section>

        <section class="contact-grid">
            <div class="dispatch-card">
                <h3 class="dispatch-title">Cara Menghubungi Kami</h3>
                <p class="dispatch-lead">Gunakan salah satu saluran resmi berikut untuk bantuan penyewaan, panduan alat, atau pemulihan akun. Tim koordinator ekspedisi kami siap membantu.</p>

                <div class="contact-channel-list">
                    <a class="contact-channel" href="mailto:{{ e($siteSettings['email'] ?? 'support@summitstation.id') }}">
                        <span class="contact-icon mail">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"></path>
                            </svg>
                        </span>
                        <span>
                            <small>EMAIL RESMI</small>
                            <strong>{{ $siteSettings['email'] ?? 'support@summitstation.id' }}</strong>
                        </span>
                    </a>

                    <a class="contact-channel" href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $siteSettings['hotline'] ?? '+6281123456789') }}" target="_blank" rel="noopener">
                        <span class="contact-icon chat">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4 4h16v11H8l-4 4V4z"></path>
                            </svg>
                        </span>
                        <span>
                            <small>WHATSAPP</small>
                            <strong>{{ $siteSettings['hotline'] ?? '+62 811-2345-6789' }}</strong>
                        </span>
                    </a>
                </div>

                <div class="dispatch-note">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="5" y="11" width="14" height="10" rx="2"></rect>
                        <path d="M8 11V7a4 4 0 0 1 8 0v4"></path>
                    </svg>
                    Saluran dienkripsi end-to-end. Tim kami merespons rata-rata dalam 5 menit pada jam operasional.
                </div>
            </div>

            <aside class="contact-side">
                <div class="contact-cards">
                    <article>
                        <span class="contact-icon chat">
                            <svg width="23" height="23" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4 4h16v11H8l-4 4V4z"></path>
                            </svg>
                        </span>
                        <small>INSTAN</small>
                        <h2>WhatsApp</h2>
                        <p>Rata-rata balasan: 5 menit</p>
                    </article>
                    <article>
                        <span class="contact-icon mail">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"></path>
                            </svg>
                        </span>
                        <h2>Email Resmi</h2>
                        <p>{{ $siteSettings['email'] ?? 'hq@summitstation.co' }}</p>
                    </article>
                </div>

                <article class="basecamp-card">
                    <iframe
                        src="https://www.google.com/maps?q=SMK+Negeri+1+Gunung+Putri+Jl.+Barokah+No.06+Wanaherang+Kec.+Gn.+Putri+Kabupaten+Bogor+Jawa+Barat+16965+Indonesia&output=embed"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Google Maps Basecamp HQ"></iframe>
                    <div class="basecamp-body">
                        <h2>
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"></path>
                            </svg>
                            BASECAMP HQ
                        </h2>
                        @php
                            $fullAddress = $siteSettings['address'] ?? 'SMK Negeri 1 Gunung Putri, Jl. Barokah No.06, Wanaherang, Kec. Gn. Putri, Kabupaten Bogor, Jawa Barat 16965, Indonesia';
                            $addrParts = array_map('trim', explode(',', $fullAddress));
                        @endphp
                        <div class="address-text-block">
                            @if (count($addrParts) >= 5)
                                <strong class="address-venue">{{ $addrParts[0] }}</strong>
                                <p class="address-line">{{ implode(', ', array_slice($addrParts, 1, 3)) }}</p>
                                <p class="address-line">{{ implode(', ', array_slice($addrParts, 4)) }}</p>
                            @else
                                <p class="address-line">{{ $fullAddress }}</p>
                            @endif
                        </div>
                        <div class="basecamp-meta">
                            <div><span>JAM OPERASIONAL</span><strong>{{ $siteSettings['operating_hours'] ?? '07.00 - 21.00 WIB' }}</strong><small>Buka Setiap Hari</small></div>
                            <div><span>LOGISTIK</span><strong class="logistics-icons">↗ ◈</strong></div>
                        </div>
                    </div>
                </article>
            </aside>
        </section>
    </main>

    <!-- Footer -->
    @include('partials.footer', ['footerContext' => 'user'])
    <script src="{{ asset('js/summit-navbar.js') }}"></script>
</body>
</html>
