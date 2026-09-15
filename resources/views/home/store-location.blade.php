<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}">
    <title>Lokasi Toko - Summit Station</title>
    <link rel="stylesheet" href="{{ asset('css/summit-store-location.css') . '?v=' . filemtime(public_path('css/summit-store-location.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-footer.css') . '?v=' . filemtime(public_path('css/summit-footer.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-navbar.css') . '?v=' . filemtime(public_path('css/summit-navbar.css')) }}">
</head>
<body>
    @include('layouts.navbar')

    <main>
        <section class="map-hero">
            <iframe
                class="google-map"
                src="https://www.google.com/maps?q=SMK+Negeri+1+Gunung+Putri+Jl.+Barokah+No.06+Wanaherang+Kec.+Gn.+Putri+Kabupaten+Bogor+Jawa+Barat+16965+Indonesia&output=embed"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="Google Maps lokasi Summit Station"></iframe>
            <div class="map-overlay-card">
                <h1>BASECAMP HUB</h1>
                <p>Tujuan utama Anda untuk perlengkapan ekspedisi ketinggian di Gunung Putri, Bogor. Keahlian, peralatan, dan persiapan dimulai di sini.</p>
            </div>
        </section>

        <section class="location-info">
            <div class="info-left">
                <article class="address-card">
                    <h2>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5z"/>
                        </svg>
                        Alamat Toko
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
                    <div class="address-actions">
                        <a class="maps-button" href="https://www.google.com/maps/search/?api=1&query=SMK+Negeri+1+Gunung+Putri+Jl.+Barokah+No.06+Wanaherang+Kec.+Gn.+Putri+Kabupaten+Bogor+Jawa+Barat+16965+Indonesia" target="_blank" rel="noreferrer">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 18l6-12M3 6l6-3 6 3 6-3v15l-6 3-6-3-6 3V6z"></path>
                            </svg>
                            BUKA DI GOOGLE MAPS
                        </a>
                        <button type="button" class="copy-button" data-copy-address>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                            SALIN ALAMAT
                        </button>
                    </div>
                </article>
            </div>

            <aside class="info-right">
                <article class="hours-card">
                    <h2>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path d="M12 7v5l3 2"></path>
                        </svg>
                        Jam Operasional
                    </h2>
                    <div><span>Senin - Jumat</span><strong>09:00 - 20:00</strong></div>
                    <div><span>Sabtu</span><strong>08:00 - 21:00</strong></div>
                    <div><span>Minggu</span><strong>08:00 - 18:00</strong></div>
                    <p>Jam operasional hari libur dapat berbeda. Pengembalian alat diterima hingga 30 menit sebelum tutup.</p>
                </article>
            </aside>

            <article class="pickup-card">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="3" width="15" height="13"></rect>
                    <polygon points="16 8 20 8 23 11 23 16 16 16 8"></polygon>
                    <circle cx="5.5" cy="18.5" r="2.5"></circle>
                    <circle cx="18.5" cy="18.5" r="2.5"></circle>
                </svg>
                <div class="pickup-text">
                    <h2>Butuh Bantuan Pengambilan?</h2>
                    <p>Sedang menyiapkan ekspedisi besar? Hubungi kami terlebih dahulu dan kami akan menyiapkan perlengkapan Anda di area pemuatan.</p>
                </div>
                <strong>+62 882-9337-1677</strong>
            </article>
        </section>

        <section class="journey-cta">
            <img class="journey-logo" src="{{ asset('images/logo.png') }}" alt="Summit Station Logo">
            <h2>SIAP MEMULAI PERJALANAN ANDA?</h2>
            <p>Baik Anda merencanakan pendakian akhir pekan atau ekspedisi alpine sebulan, para ahli kami siap memastikan Anda memiliki alat yang tepat untuk medan tersebut.</p>
            <div>
                <a href="{{ route('catalog') }}">JELAJAHI KATALOG ALAT</a>
                <a href="{{ route('contact.admin') }}">JADWALKAN KONSULTASI</a>
            </div>
        </section>
    </main>

    <!-- Footer -->
    @include('partials.footer', ['footerContext' => 'user'])

    <script>
        document.querySelector('[data-copy-address]').addEventListener('click', async (event) => {
            const text = @json($fullAddress);
            await navigator.clipboard.writeText(text);
            const btn = event.currentTarget;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg> ALAMAT DISALIN`;
            btn.style.backgroundColor = 'var(--green)';
            btn.style.color = '#ffffff';
            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.style.backgroundColor = '';
                btn.style.color = '';
            }, 1800);
        });
    </script>
    <script src="{{ asset('js/summit-navbar.js') }}"></script>
</body>
</html>
