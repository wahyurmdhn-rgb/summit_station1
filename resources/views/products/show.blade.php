<!doctype html>
@php
    $bookingToday = now()->format('Y-m-d');
    $bookingPrice = (int) ($product['price'] ?? 0);
@endphp
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}">
    <title>{{ $product['name'] }} - Summit Station</title>
    <link rel="stylesheet" href="{{ asset('css/summit-detail.css') . '?v=' . filemtime(public_path('css/summit-detail.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-image-viewer.css') . '?v=' . filemtime(public_path('css/summit-image-viewer.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-footer.css') . '?v=' . filemtime(public_path('css/summit-footer.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-navbar.css') . '?v=' . filemtime(public_path('css/summit-navbar.css')) }}">
    <style>
        .date-picker {
            border: 1px solid #d5d3cb;
            border-radius: 8px;
            padding: 9px 10px;
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            color: #1f2937;
            background: #fff;
            width: 100%;
            box-sizing: border-box;
        }
        .bundle-items-section {
            margin: 24px 0;
            padding: 18px;
            background: #f8faf9;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }
        .bundle-items-title {
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #185d31;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .bundle-item-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #e2e8f0;
            font-size: 13px;
            color: #374151;
        }
        .bundle-item-row:last-child {
            border-bottom: none;
        }
        .bundle-item-name {
            font-weight: 700;
        }
        .bundle-item-qty {
            font-size: 12px;
            color: #185d31;
            font-weight: 800;
            background: #e8f5e9;
            padding: 2px 8px;
            border-radius: 6px;
        }
        .review-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }
        @media (min-width: 1200px) {
            .review-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>

    <!-- Header / Navbar -->
    @include('layouts.navbar')

    <!-- Breadcrumb Bar -->
    <div class="breadcrumb-bar">
        <a href="{{ route('catalog') }}">KATALOG</a> &rsaquo;
        <a href="{{ route('catalog') }}">{{ $product['category'] }}</a> &rsaquo;
        <span class="active">{{ strtoupper($product['name']) }} {{ strtoupper($product['subtitle'] ?? '') }}</span>
    </div>

    @if (session('status'))
        <div style="max-width: 1200px; margin: 15px auto 0; padding: 12px 20px; background: #dcfce7; border: 1px solid #86efac; color: #15803d; border-radius: 10px; font-weight: 700; font-size: 13px;">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="max-width: 1200px; margin: 15px auto 0; padding: 12px 20px; background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; border-radius: 10px; font-weight: 700; font-size: 13px;">
            {{ $errors->first() }}
        </div>
    @endif

    <!-- Main Detail Container -->
    <main class="detail-container">
        <!-- Left Column: Image Gallery & Badges -->
        <div class="gallery-column">
            <div class="main-image-box">
                <div class="gallery-badges">
                    <span class="badge-grade">{{ $product['grade'] }}</span>
                    @if ($product['in_stock'])
                        <span class="badge-stock-pill">TERSEDIA (STOK: {{ $product['stock_available'] }})</span>
                    @else
                        <span class="badge-stock-pill" style="background: #ef4444;">STOK HABIS</span>
                    @endif
                </div>
                <img id="main-image" src="{{ $product['main_image'] }}" alt="{{ $product['name'] }}" onerror="handleDetailImgError(this)" data-summit-zoom>
                <span class="zoom-hint">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"></circle>
                        <line x1="21" y1="21" x2="16.5" y2="16.5"></line>
                        <line x1="11" y1="8" x2="11" y2="14"></line>
                        <line x1="8" y1="11" x2="14" y2="11"></line>
                    </svg>
                    <span>Perbesar</span>
                </span>
            </div>

            @if (!empty($product['thumbnails']) && count($product['thumbnails']) > 1)
                <div class="thumbnails-grid">
                    @foreach ($product['thumbnails'] as $index => $thumb)
                        <div class="thumb-item {{ $index === 0 ? 'active' : '' }}" onclick="switchImage(this, '{{ $thumb }}')">
                            <img src="{{ $thumb }}" alt="Thumbnail {{ $index + 1 }}" onerror="handleDetailImgError(this)">
                        </div>
                    @endforeach
                </div>
            @endif

            @if (!empty($productReviews) && count($productReviews) > 0)
                <div style="margin-top: 20px; padding-top: 24px; border-top: 1px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <div>
                            <span class="section-label">ULASAN PELANGGAN</span>
                            <h3 style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 4px 0 0 0;">Pengalaman Pelanggan ({{ count($productReviews) }})</h3>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 20px; font-weight: 800; color: #185d31;">{{ $product['rating'] }}</span>
                            <div style="color: #eab308; font-size: 16px;">★</div>
                        </div>
                    </div>

                    <div class="review-grid">
                        @foreach ($productReviews as $pRev)
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; display: flex; flex-direction: column; gap: 12px; text-align: left;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="color: #eab308; font-size: 14px;">
                                        @for ($si = 1; $si <= 5; $si++)
                                            @if ($si <= $pRev->rating)
                                                ★
                                            @else
                                                <span style="color: #cbd5e1;">★</span>
                                            @endif
                                        @endfor
                                    </div>
                                    <span style="font-size: 11px; color: #94a3b8;">{{ $pRev->created_at ? $pRev->created_at->format('d M Y') : '' }}</span>
                                </div>
                                <p style="font-size: 13px; color: #334155; line-height: 1.6; margin: 0;">
                                    "{{ $pRev->comment }}"
                                </p>
                                <div style="display: flex; align-items: center; gap: 10px; margin-top: auto; padding-top: 12px; border-top: 1px solid #e2e8f0;">
                                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #e2e8f0; color: #475569; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; flex-shrink: 0;">
                                        {{ $pRev->user ? $pRev->user->initials : 'U' }}
                                    </div>
                                    <div>
                                        <strong style="font-size: 13px; color: #0f172a; display: block;">{{ $pRev->user ? $pRev->user->name : 'Penjelajah' }}</strong>
                                        <span style="font-size: 11px; color: #94a3b8;">{{ $pRev->product ? $pRev->product->name : ($pRev->bundle ? $pRev->bundle->name : 'Penyewaan Peralatan') }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Column: Information & Booking Card -->
        <div class="info-column">
            <h1 class="product-main-title">{{ $product['name'] }}</h1>
            <div class="product-subtitle">{{ $product['subtitle'] }}</div>

            <div class="rating-row-detail">
                <div class="stars-gold" aria-label="Rating {{ $product['rating'] }} dari 5">
                    @for ($sr = 1; $sr <= 5; $sr++)
                        <span style="color: {{ $sr <= round((float) $product['rating']) ? '#eab308' : '#cbd5e1' }}; font-size: 18px;">&starf;</span>
                    @endfor
                </div>
                <span class="reviews-text">{{ $product['rating'] }} ({{ $product['reviews_count'] }} Ulasan Ekspedisi)</span>
            </div>

            <div class="section-label">DESKRIPSI</div>
            <p class="product-description-text">
                {{ $product['description'] }}
            </p>

            @if (!empty($product['bundle_items']))
                <div class="bundle-items-section">
                    <div class="bundle-items-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>
                        <span>PERLENGKAPAN DALAM PAKET SEWA</span>
                    </div>
                    @foreach ($product['bundle_items'] as $bItem)
                        <div class="bundle-item-row">
                            <span class="bundle-item-name">{{ $bItem->name }} ({{ $bItem->category?->name ?? 'Peralatan' }})</span>
                            <span class="bundle-item-qty">{{ $bItem->pivot->quantity ?? 1 }} Unit</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Specs 2x2 Grid -->
            <div class="specs-grid">
                @foreach ($product['specs'] as $key => $val)
                    <div class="spec-card">
                        <span class="spec-key">{{ $key }}</span>
                        <span class="spec-val">{{ $val }}</span>
                    </div>
                @endforeach
            </div>

            <!-- Rental Box Card -->
            <div class="rental-box-card">
                <div class="price-header-row">
                    <span class="section-label">{{ !empty($product['is_bundle']) ? 'HARGA PAKET' : 'HARGA SEWA' }}</span>
                    <div class="rental-price-display">
                        Rp {{ number_format($product['price'], 0, ',', '.') }} <span>/ hari</span>
                    </div>
                </div>

                @if (!empty($isSuspended))
                    <div style="padding: 16px; background: #fee2e2; border-radius: 10px; color: #991b1b; text-align: center; font-weight: 700; font-size: 14px; margin-top: 10px; line-height: 1.5;">
                        Akun Anda telah ditangguhkan (DIBLOKIR) dan tidak dapat melakukan penyewaan. Silakan hubungi Administrator.
                    </div>
                @elseif (!empty($isConsentPending))
                    <div style="padding: 16px; background: #fef3c7; border-radius: 10px; color: #92400e; text-align: center; font-weight: 700; font-size: 14px; margin-top: 10px; line-height: 1.5;">
                        Persetujuan orang tua Anda masih menunggu verifikasi admin. Anda belum dapat melakukan penyewaan alat sampai akun dikonfirmasi admin.
                        <div style="margin-top: 10px;">
                            <a href="{{ route('profile') }}" style="display: inline-block; background: #92400e; color: #fff; font-size: 13px; font-weight: 700; padding: 10px 24px; border-radius: 8px; text-decoration: none;">
                                Cek Status Verifikasi &rarr;
                            </a>
                        </div>
                    </div>
                @elseif (!session('account_id'))
                    <div style="padding: 16px; background: #fef3c7; border-radius: 10px; color: #92400e; text-align: center; font-weight: 700; font-size: 14px; margin-top: 10px; line-height: 1.5;">
                        Silakan login terlebih dahulu untuk melakukan booking.
                        <div style="margin-top: 10px;">
                            <a href="{{ route('login', ['redirect' => request()->getPathInfo()]) }}" style="display: inline-block; background: #185d31; color: #fff; font-size: 13px; font-weight: 700; padding: 10px 24px; border-radius: 8px; text-decoration: none;">
                                Login Sekarang &rarr;
                            </a>
                        </div>
                    </div>
                @elseif ($product['in_stock'])
                    <form method="POST" action="{{ !empty($product['is_bundle']) ? route('cart.add-bundle') : route('cart.add') }}" style="width: 100%;">
                        @csrf
                        @if (!empty($product['is_bundle']))
                            <input type="hidden" name="bundle_id" value="{{ $product['id'] }}">
                        @else
                            <input type="hidden" name="product_id" value="{{ $product['id'] }}">
                        @endif
                        <input type="hidden" name="days" id="input-duration" value="1">
                        <input type="hidden" name="quantity" id="input-qty" value="1">
                        @php
                            // Paket sewa dibatasi maksimal 5 paket; produk satuan mengikuti stok.
                            $qtyMax = !empty($product['is_bundle'])
                                ? min((int) $product['stock_available'], 5)
                                : (int) $product['stock_available'];
                        @endphp

                        <div class="steppers-row">
                            <!-- Tanggal Mulai & Pengembalian (durasi dihitung otomatis) -->
                            <div class="stepper-group" style="flex: 1 1 100%;">
                                <span class="stepper-label">TANGGAL MULAI</span>
                                <input type="date" name="rent_start" id="input-start" class="date-picker" min="{{ $bookingToday }}" value="{{ $bookingToday }}">
                            </div>
                            <div class="stepper-group" style="flex: 1 1 100%;">
                                <span class="stepper-label">TANGGAL PENGEMBALIAN</span>
                                <input type="date" name="rent_end" id="input-end" class="date-picker" min="{{ $bookingToday }}" value="{{ $bookingToday }}">
                            </div>

                            <!-- Quantity Stepper -->
                            <div class="stepper-group">
                                <span class="stepper-label">JUMLAH (MAKS: {{ $qtyMax }})</span>
                                <div class="stepper-box">
                                    <button type="button" class="stepper-btn" onclick="changeQty(-1, {{ $qtyMax }})">&minus;</button>
                                    <span class="stepper-value" id="qty-val">1</span>
                                    <button type="button" class="stepper-btn" onclick="changeQty(1, {{ $qtyMax }})">&plus;</button>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 14px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px; background: #f6f5f0; border-radius: 10px; padding: 10px 14px;">
                                <span style="font-size: 12px; font-weight: 700; color: #555; text-transform: uppercase; letter-spacing: 0.4px;">Durasi Penyewaan</span>
                                <strong style="font-size: 16px; color: #0f172a;" id="rent-duration-readout">1 Hari</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px; background: #eef7f0; border-radius: 10px; padding: 10px 14px;">
                                <span style="font-size: 12px; font-weight: 700; color: #185d31; text-transform: uppercase; letter-spacing: 0.4px;">Estimasi Biaya Sewa</span>
                                <strong style="font-size: 16px; color: #185d31;" id="rent-price-readout">Rp {{ number_format($bookingPrice, 0, ',', '.') }}</strong>
                            </div>
                        </div>

                        <div style="display: flex; gap: 10px; margin-top: 16px;">
                            <button type="submit" class="btn-tambah-cart" style="flex: 1;">
                                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path>
                                    <path d="M3 6h18"></path>
                                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                                </svg>
                                <span>{{ !empty($product['is_bundle']) ? 'Booking Paket Sewa' : 'Tambah ke Keranjang' }}</span>
                            </button>
                        </div>
                    </form>
                @else
                    <div style="padding: 16px; background: #fee2e2; border-radius: 10px; color: #991b1b; text-align: center; font-weight: 700; font-size: 14px; margin-top: 10px;">
                        @if (!empty($product['is_bundle']))
                            Paket sedang habis dan tidak dapat disewa. Satu atau lebih barang dalam paket ini stoknya kosong.
                        @else
                            Maaf, stok alat ini sedang habis. Silakan pilih alat alternatif di katalog.
                        @endif
                    </div>
                    <button type="button" disabled style="width: 100%; margin-top: 12px; padding: 14px; background: #d1d5db; color: #6b7280; border: none; border-radius: 10px; font-size: 15px; font-weight: 800; cursor: not-allowed;">
                        Stok Habis
                    </button>
                @endif

                <div class="guarantees-row">
                    <div class="guarantee-item">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>Jaminan Kualitas</span>
                    </div>
                    <div class="guarantee-item">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>Peralatan Tersanitasi</span>
                    </div>
                </div>
            </div>

        </div>
    </main>

    @if (!empty($product['features']))
        <!-- Features Highlight Section (3 Columns Bottom as in Mockup) -->
        <section class="features-section">
        <div class="features-container">
            @foreach ($product['features'] as $feat)
                <div class="feature-item-card">
                    <div class="feature-icon-circle">
                        @if ($feat['icon'] === 'package' || str_contains(strtolower($feat['title']), 'terintegrasi') || str_contains(strtolower($feat['title']), 'paket'))
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m7.5 4.27 9 5.15"></path>
                                <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path>
                                <path d="m3.3 7 8.7 5 8.7-5"></path>
                                <path d="M12 22V12"></path>
                            </svg>
                        @elseif ($feat['icon'] === 'droplet' || str_contains(strtolower($feat['title']), 'sanitasi') || str_contains(strtolower($feat['title']), 'steril') || str_contains(strtolower($feat['title']), 'weather'))
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>
                            </svg>
                        @elseif ($feat['icon'] === 'tag' || str_contains(strtolower($feat['title']), 'hemat') || str_contains(strtolower($feat['title']), 'harga'))
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"></path>
                                <circle cx="7" cy="7" r="1.5" fill="currentColor"></circle>
                            </svg>
                        @elseif ($feat['icon'] === 'tent' || str_contains(strtolower($feat['title']), 'structural'))
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 20H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2z"></path>
                                <polyline points="12 4 12 20"></polyline>
                            </svg>
                        @else
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17.7 7.7A2.5 2.5 0 1 1 19.5 12H2"></path>
                                <path d="M12.6 19.4A2 2 0 1 0 14 16H2"></path>
                                <path d="M9.6 4.6A2 2 0 1 1 11 8H2"></path>
                            </svg>
                        @endif
                    </div>
                    <h3 class="feature-title">{{ $feat['title'] }}</h3>
                    <p class="feature-description">{{ $feat['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    <!-- Footer -->
    @include('partials.footer', ['footerContext' => 'user'])

    <!-- Interactive Stepper & Image Switcher Script -->
    <script>
        function switchImage(el, src) {
            document.getElementById('main-image').src = src;
            document.querySelectorAll('.thumb-item').forEach(t => t.classList.remove('active'));
            el.classList.add('active');
        }

        let qty = 1;

        var PRICE_PER_DAY = {{ $bookingPrice }};

        function dayMs() { return 24 * 60 * 60 * 1000; }

        function rentalDays() {
            var start = document.getElementById('input-start');
            var end = document.getElementById('input-end');
            if (!start || !end || !start.value || !end.value) return 0;
            var s = Date.parse(start.value + 'T00:00:00Z');
            var e = Date.parse(end.value + 'T00:00:00Z');
            return Math.round((e - s) / dayMs()) + 1;
        }

        function formatRp(v) {
            return 'Rp ' + v.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function recomputeRental() {
            var start = document.getElementById('input-start');
            var end = document.getElementById('input-end');
            var durReadout = document.getElementById('rent-duration-readout');
            var priceReadout = document.getElementById('rent-price-readout');
            var hiddenDays = document.getElementById('input-duration');

            var days = rentalDays();

            if (days < 1) {
                if (durReadout) durReadout.textContent = 'Periksa kembali tanggal';
                if (priceReadout) priceReadout.textContent = '—';
                if (hiddenDays) hiddenDays.value = 0;
                return;
            }

            if (days > 30) {
                if (durReadout) durReadout.textContent = 'Maksimal 30 hari';
                if (priceReadout) priceReadout.textContent = '—';
                if (hiddenDays) hiddenDays.value = 30;
                return;
            }

            if (hiddenDays) hiddenDays.value = days;
            if (durReadout) durReadout.textContent = days + ' Hari';
            if (priceReadout) priceReadout.textContent = formatRp(PRICE_PER_DAY * days * qty);
        }

        function changeQty(delta, maxStock) {
            const limit = Math.max(1, maxStock);
            qty = Math.max(1, Math.min(limit, qty + delta));
            document.getElementById('qty-val').textContent = qty;
            document.getElementById('input-qty').value = qty;
            recomputeRental();
        }

        (function initRentalDates() {
            var start = document.getElementById('input-start');
            var end = document.getElementById('input-end');

            if (start) {
                start.addEventListener('change', function () {
                    if (end && end.value && end.value < start.value) end.value = start.value;
                    recomputeRental();
                });
            }
            if (end) {
                end.addEventListener('change', function () {
                    if (start && end.value < start.value) end.value = start.value;
                    recomputeRental();
                });
            }

            recomputeRental();
        })();
    </script>
    <script>
        const IMG_PLACEHOLDER = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300"><rect width="400" height="300" fill="#e8ece8"/><path d="M0 230 L110 130 L180 205 L250 150 L400 285 L400 400 L0 400 Z" fill="#a3baa3"/><path d="M150 300 L150 240 L115 300 Z" fill="#7d9a7d"/><circle cx="110" cy="130" r="13" fill="#fff"/><text x="200" y="285" font-family="Arial, sans-serif" font-size="15" fill="#6b7a6b" text-anchor="middle">Gambar tidak tersedia</text></svg>');
        function handleDetailImgError(img) {
            if (img.dataset.ph) return;
            img.dataset.ph = '1';
            var old = img.onerror;
            img.onerror = null;
            if (img.id === 'main-image') {
                var cur = img.src;
                img.src = IMG_PLACEHOLDER;
                img.setAttribute('data-fallback-origin', cur);
            } else {
                img.src = IMG_PLACEHOLDER;
            }
            img.onerror = old;
        }
    </script>
    <script src="{{ asset('js/summit-navbar.js') }}"></script>
    <script src="{{ asset('js/summit-image-viewer.js') . '?v=' . filemtime(public_path('js/summit-image-viewer.js')) }}"></script>
</body>
</html>
