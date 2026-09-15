<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}">
    <title>Summit Station - Rental Peralatan Mendaki Premium</title>
    <link rel="stylesheet" href="{{ asset('css/summit-home.css') . '?v=' . filemtime(public_path('css/summit-home.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-footer.css') . '?v=' . filemtime(public_path('css/summit-footer.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-navbar.css') . '?v=' . filemtime(public_path('css/summit-navbar.css')) }}">
</head>
<body>

    <!-- Blue Top Banner Line -->
    <!-- Header / Navbar -->
    @include('layouts.navbar')

    <!-- Main Content -->
    <main>
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-bg" id="heroBg"></div>
            <div class="hero-overlay"></div>
            <div class="hero-container">
                <div class="hero-badge">EDISI EKSPEDISI 2026</div>
                <h1 class="hero-title">
                    @php
                        $heroTitle = $siteSettings['hero_title'] ?? 'Peralatan Mendaki Premium untuk Petualangan Anda';
                        // Bungkus kata terakhir dengan gaya aksen agar konsisten dengan desain hero.
                        $pos = strrpos($heroTitle, ' ');
                        if ($pos !== false) {
                            $left = substr($heroTitle, 0, $pos);
                            $right = substr($heroTitle, $pos + 1);
                            echo e($left) . ' <span class="accent-text">' . e($right) . '</span>';
                        } else {
                            echo e($heroTitle);
                        }
                    @endphp
                </h1>
                <p class="hero-description">
                    {{ $siteSettings['hero_subtitle'] ?? 'Sewa perlengkapan gunung kelas dunia dari merek terpercaya. Kami memastikan setiap alat siap menghadapi medan ekstrem demi keamanan Anda.' }}
                </p>

                <div class="hero-buttons">
                    <a href="{{ route('catalog') }}" class="btn-primary">
                        <span>Mulai Sewa</span>
                        <span>&rarr;</span>
                    </a>
                    <a href="{{ route('catalog') }}" class="btn-secondary">
                        Lihat Katalog
                    </a>
                </div>

                <div class="hero-explore">
                    <span>JELAJAHI</span>
                    <div class="line"></div>
                </div>
            </div>
        </section>

        <!-- Quick Action Section -->
        <section class="quick-action-section">
            <div class="quick-action-container">
                <a href="{{ route('catalog') }}" class="quick-card reveal" style="--stagger: 0;">
                    <span class="quick-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </span>
                    <span class="quick-card-title">Cari Peralatan</span>
                    <span class="quick-card-sub">Temukan alat sesuai kebutuhan</span>
                </a>
                <a href="#terms" class="quick-card reveal" style="--stagger: 1;" id="termsQuickCard">
                    <span class="quick-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    </span>
                    <span class="quick-card-title">Syarat & Ketentuan</span>
                    <span class="quick-card-sub">Baca sebelum menyewa</span>
                </a>
                <a href="{{ route('history') }}" class="quick-card reveal" style="--stagger: 2;">
                    <span class="quick-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 1 0 10 10 10 10 0 0 0-10-10z"></path><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </span>
                    <span class="quick-card-title">Cek Penyewaan</span>
                    <span class="quick-card-sub">Pantau status pemesananmu</span>
                </a>
                <a href="{{ route('contact.admin') }}" class="quick-card reveal" style="--stagger: 3;">
                    <span class="quick-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                    </span>
                    <span class="quick-card-title">Hubungi Admin</span>
                    <span class="quick-card-sub">Butuh bantuan? Kami siap</span>
                </a>
            </div>
        </section>

        <!-- Katalog Unggulan Section -->
        <section class="featured-section">
            <div class="featured-container">
                <p class="section-eyebrow reveal">PERALATAN PILIHAN</p>
                <h2 class="section-title reveal">Peralatan Pilihan Untuk Petualanganmu</h2>
                <p class="featured-subtitle reveal">Temukan perlengkapan yang sesuai dengan kebutuhan perjalananmu.</p>

                <form class="search-box reveal" action="{{ route('catalog') }}" method="GET" role="search">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" name="search" id="homeSearchInput" class="search-input" placeholder="Cari perlengkapan untuk petualanganmu..." autocomplete="off">
                    <button type="button" class="search-clear" id="homeSearchClear" aria-label="Bersihkan pencarian" hidden>&times;</button>
                    <button type="submit" class="search-submit">Cari</button>
                </form>

                <div class="featured-grid">
                    @forelse ($featuredProducts as $product)
                        @php
                            $inStock = $product->in_stock;
                            $img = $product->main_image;
                            $isImg = \Illuminate\Support\Str::startsWith((string) $img, 'http');
                        @endphp
                        <a href="{{ route('catalog.show', $product->id) }}" class="featured-card reveal" style="--stagger: {{ $loop->index % 4 }};">
                            <div class="featured-thumb">
                                @if ($isImg)
                                    <img src="{{ $img }}" alt="{{ $product->name }}" loading="lazy" onerror="this.parentElement.classList.add('no-img')">
                                @else
                                    <div class="featured-thumb-fallback">{{ mb_strtoupper(mb_substr($product->name, 0, 2)) }}</div>
                                @endif
                                <span class="featured-badge {{ $inStock ? 'in-stock' : 'out-stock' }}">{{ $inStock ? 'Tersedia' : 'Habis' }}</span>
                            </div>
                            <div class="featured-body">
                                <span class="featured-cat">{{ $product->category?->name ?? 'Peralatan' }}</span>
                                <h3 class="featured-name">{{ $product->name }}</h3>
                                <div class="featured-meta">
                                    <span class="featured-price">Rp {{ number_format($product->price_per_day, 0, ',', '.') }}</span>
                                    <span class="featured-unit">/ hari</span>
                                </div>
                                <span class="featured-cta">
                                    <span>Lihat Detail</span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                                </span>
                            </div>
                        </a>
                    @empty
                        <p class="featured-empty reveal">Belum ada peralatan yang tersedia.</p>
                    @endforelse
                </div>

                <div class="featured-more reveal">
                    <a href="{{ route('catalog') }}" class="btn-primary">
                        <span>Lihat Semua Katalog</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- Testimonial Section / Ulasan Pelanggan -->
        <section class="testimonial-section">
            <div class="testimonial-container">
                <p class="section-eyebrow reveal">ULASAN PELANGGAN</p>
                <h2 class="section-title reveal">Apa Kata Mereka?</h2>
                <p style="text-align: center; color: #6b7280; font-size: 14px; margin-top: -8px; margin-bottom: 28px;" class="reveal">
                    Pengalaman para penjelajah bersama Summit Station.
                </p>

                @php $initialReviews = 3; @endphp
                @if ($totalReviewsCount > 0)
                    <div class="rating-row reveal">
                        <div class="rating-score">{{ number_format($averageRating, 1) }}</div>
                        <div class="rating-detail">
                            <div class="stars" aria-label="Rating {{ number_format($averageRating, 1) }} dari 5 bintang">
                                @for ($s = 1; $s <= 5; $s++)
                                    @if ($s <= round($averageRating))
                                        <svg viewBox="0 0 24 24" style="fill: #eab308;"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                    @else
                                        <svg viewBox="0 0 24 24" style="fill: #e2e8f0;"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                    @endif
                                @endfor
                            </div>
                            <div class="rating-text">
                                Rating <strong>{{ number_format($averageRating, 1) }}/5.0</strong> berdasarkan <strong>{{ $totalReviewsCount }}</strong> ulasan pelanggan
                            </div>
                        </div>
                    </div>

                    <div class="testimonial-grid" data-initial-count="{{ $initialReviews }}">
                        @foreach ($reviews as $review)
                            <article class="testimonial-card reveal{{ $loop->index >= $initialReviews ? ' is-collapsed' : '' }}" style="--stagger: {{ $loop->index % 3 }};">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                    <div style="display: flex; gap: 2px; color: #eab308; font-size: 14px;">
                                        @for ($i = 1; $i <= 5; $i++)
                                            @if ($i <= $review->rating)
                                                <span>★</span>
                                            @else
                                                <span style="color: #cbd5e1;">★</span>
                                            @endif
                                        @endfor
                                    </div>
                                    <span style="font-size: 11px; color: #94a3b8; font-weight: 600;">
                                        {{ $review->created_at ? $review->created_at->format('d M Y') : '' }}
                                    </span>
                                </div>
                                <p class="testimonial-quote">
                                    "{{ $review->comment }}"
                                </p>
                                <div class="author-info">
                                    @if ($review->user && $review->user->avatar_path)
                                        <img class="author-avatar" src="{{ $review->user->avatar_path }}" alt="{{ $review->user->name }}" onerror="this.onerror=null;this.src=dataUriAvatar">
                                    @else
                                        <div class="author-avatar" style="width: 44px; height: 44px; border-radius: 50%; background: #e2e8f0; color: #475569; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; flex-shrink: 0;">
                                            {{ $review->user ? $review->user->initials : 'U' }}
                                        </div>
                                    @endif
                                    <div>
                                        <h3 class="author-name">{{ $review->user ? $review->user->name : 'Penjelajah' }}</h3>
                                        <span class="author-role">{{ $review->product ? $review->product->name : ($review->bundle ? $review->bundle->name : 'Penyewaan Peralatan') }}</span>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    @if ($reviews->count() > $initialReviews)
                        <div class="testimonials-toggle reveal">
                            <button type="button" id="testimonialsToggleBtn" class="testimonials-toggle-btn" aria-expanded="false">
                                <span class="testimonials-toggle-label">Lihat Semua Ulasan</span>
                                <svg class="testimonials-toggle-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>
                        </div>
                    @endif
                @else
                    <div class="reveal" style="text-align: center; padding: 48px 24px; background: #ffffff; border-radius: 16px; border: 1px dashed #cbd5e1; max-width: 600px; margin: 0 auto;">
                        <div style="width: 56px; height: 56px; border-radius: 50%; background: #f0fdf4; color: #185d31; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 24px;">
                            ★
                        </div>
                        <h3 style="font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 8px;">Belum Ada Ulasan Pelanggan</h3>
                        <p style="font-size: 14px; color: #64748b; margin-bottom: 20px; line-height: 1.5;">
                            Jadilah yang pertama menyewa alat dan membagikan pengalaman petualangan Anda bersama Summit Station.
                        </p>
                        <a href="{{ route('catalog') }}" class="btn-primary" style="display: inline-flex; text-decoration: none; padding: 10px 24px; font-size: 13px;">
                            <span>Sewa Alat Sekarang</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                @endif
            </div>
        </section>

        <!-- Visi & Misi Section -->
        <section class="vision-mission-section">
            <div class="vision-mission-container">
                <p class="section-eyebrow reveal">NILAI &amp; TUJUAN KAMI</p>
                <h2 class="section-title reveal">Visi &amp; Misi</h2>
                <p class="vision-mission-subtitle reveal">
                    Komitmen teguh Summit Station dalam mendukung setiap langkah petualangan dan ekspedisi Anda di alam bebas.
                </p>

                <div class="vision-mission-grid">
                    <!-- Card 1: VISI -->
                    <div class="vision-mission-card vision-card reveal">
                        <div class="vm-card-header">
                            <div class="vm-icon-wrapper" aria-hidden="true">
                                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <circle cx="12" cy="12" r="6"></circle>
                                    <circle cx="12" cy="12" r="2"></circle>
                                </svg>
                            </div>
                            <div>
                                <span class="vm-tag">PANDANGAN KE DEPAN</span>
                                <h3 class="vm-title">Visi</h3>
                            </div>
                        </div>
                        <div class="vm-quote-body">
                            <p class="vm-quote-text">
                                &ldquo;Menjadi penyedia layanan penyewaan peralatan ekspedisi yang terpercaya, berkualitas, dan mudah diakses untuk mendukung setiap perjalanan dan kegiatan petualangan.&rdquo;
                            </p>
                        </div>
                        <div class="vm-card-badge">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                            <span>Standar Mutu &amp; Keamanan Terpercaya</span>
                        </div>
                    </div>

                    <!-- Card 2: MISI -->
                    <div class="vision-mission-card mission-card reveal">
                        <div class="vm-card-header">
                            <div class="vm-icon-wrapper" aria-hidden="true">
                                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                                    <line x1="4" y1="22" x2="4" y2="15"></line>
                                </svg>
                            </div>
                            <div>
                                <span class="vm-tag">LANGKAH KAMI</span>
                                <h3 class="vm-title">Misi</h3>
                            </div>
                        </div>
                        <ol class="vm-mission-list">
                            <li class="vm-mission-item">
                                <span class="vm-item-num">1</span>
                                <p class="vm-item-text">Menyediakan peralatan ekspedisi yang berkualitas dan terawat.</p>
                            </li>
                            <li class="vm-mission-item">
                                <span class="vm-item-num">2</span>
                                <p class="vm-item-text">Memberikan pelayanan penyewaan yang mudah, cepat, dan terpercaya.</p>
                            </li>
                            <li class="vm-mission-item">
                                <span class="vm-item-num">3</span>
                                <p class="vm-item-text">Membantu pelanggan mendapatkan peralatan yang sesuai dengan kebutuhan perjalanan.</p>
                            </li>
                            <li class="vm-mission-item">
                                <span class="vm-item-num">4</span>
                                <p class="vm-item-text">Menjaga kepuasan dan kepercayaan pelanggan melalui pelayanan yang profesional.</p>
                            </li>
                            <li class="vm-mission-item">
                                <span class="vm-item-num">5</span>
                                <p class="vm-item-text">Mendukung kegiatan eksplorasi dan petualangan dengan menyediakan perlengkapan yang aman dan nyaman digunakan.</p>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- Kenapa Summit Station Section -->
        <section class="why-section">
            <div class="why-container">
                <p class="section-eyebrow reveal">KEUNGGULAN KAMI</p>
                <h2 class="section-title reveal">Kenapa Pilih Summit Station?</h2>
                <p class="why-subtitle reveal">Kami memastikan setiap perjalananmu nyaman, aman, dan terpercaya.</p>

                <div class="why-grid">
                    <article class="why-card reveal" style="--stagger: 0;">
                        <span class="why-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        </span>
                        <h3 class="why-title">Peralatan Terawat</h3>
                        <p class="why-desc">Setiap perlengkapan diperiksa sebelum disewakan.</p>
                    </article>
                    <article class="why-card reveal" style="--stagger: 1;">
                        <span class="why-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        </span>
                        <h3 class="why-title">Harga Terjangkau</h3>
                        <p class="why-desc">Dapatkan perlengkapan berkualitas tanpa harus membeli.</p>
                    </article>
                    <article class="why-card reveal" style="--stagger: 2;">
                        <span class="why-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                        </span>
                        <h3 class="why-title">Proses Mudah</h3>
                        <p class="why-desc">Pesan, bayar, dan kelola penyewaan dengan mudah.</p>
                    </article>
                    <article class="why-card reveal" style="--stagger: 3;">
                        <span class="why-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                        </span>
                        <h3 class="why-title">Dukungan Admin</h3>
                        <p class="why-desc">Hubungi admin jika membutuhkan bantuan.</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- Cara Menyewa Section -->
        <section class="steps-section">
            <div class="steps-container">
                <p class="section-eyebrow reveal">PANDUAN</p>
                <h2 class="section-title reveal">Cara Sewa di Summit Station</h2>
                <p class="steps-subtitle reveal">Empat langkah mudah menuju petualanganmu.</p>

                <div class="steps-grid">
                    <div class="step-card reveal" style="--stagger: 0;">
                        <span class="step-num">01</span>
                        <span class="step-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                        </span>
                        <h3 class="step-title">Pilih Peralatan</h3>
                        <p class="step-desc">Jelajahi katalog dan pilih perlengkapan yang kamu butuhkan.</p>
                    </div>
                    <div class="step-card reveal" style="--stagger: 1;">
                        <span class="step-num">02</span>
                        <span class="step-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        </span>
                        <h3 class="step-title">Tentukan Jadwal</h3>
                        <p class="step-desc">Atur tanggal sewa yang sesuai dengan rencana petualanganmu.</p>
                    </div>
                    <div class="step-card reveal" style="--stagger: 2;">
                        <span class="step-num">03</span>
                        <span class="step-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        </span>
                        <h3 class="step-title">Lakukan Pembayaran</h3>
                        <p class="step-desc">Selesaikan pembayaran untuk memproses penyewaanmu.</p>
                    </div>
                    <div class="step-card reveal" style="--stagger: 3;">
                        <span class="step-num">04</span>
                        <span class="step-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                        </span>
                        <h3 class="step-title">Ambil &amp; Nikmati</h3>
                        <p class="step-desc">Ambil peralatan dan mulai petualanganmu.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Petualangan Section -->
        <section class="cta-section">
            <div class="cta-bg"></div>
            <div class="cta-overlay"></div>
            <div class="cta-container">
                <h2 class="cta-title reveal">Petualangan Besarmu Dimulai dari Sini.</h2>
                <p class="cta-sub reveal">Temukan perlengkapan yang kamu butuhkan dan mulai perjalananmu bersama Summit Station.</p>
                <div class="cta-actions reveal">
                    <a href="{{ route('catalog') }}" class="btn-primary cta-btn">
                        <span>Mulai Sewa Sekarang</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="faq-section">
            <div class="faq-container">
                <p class="section-eyebrow reveal">PERTANYAAN UMUM</p>
                <h2 class="section-title reveal">Frequently Asked Questions</h2>
                <p class="faq-subtitle reveal">Temukan jawaban dari pertanyaan yang sering diajukan.</p>

                <div class="faq-list">
                    <div class="faq-item reveal" data-faq-item>
                        <button type="button" class="faq-toggle" data-faq-toggle aria-expanded="false">
                            <span class="faq-question">Bagaimana cara menyewa alat?</span>
                            <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="faq-answer">
                            <p>Pilih peralatan pada katalog, tentukan jadwal sewa, lalu lakukan pembayaran melalui halaman checkout. Setelah pembayaran diverifikasi, pesananmu akan diproses.</p>
                        </div>
                    </div>
                    <div class="faq-item reveal" data-faq-item>
                        <button type="button" class="faq-toggle" data-faq-toggle aria-expanded="false">
                            <span class="faq-question">Bagaimana cara melakukan pembayaran?</span>
                            <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="faq-answer">
                            <p>Pembayaran dapat dilakukan melalui transfer sesuai metode yang tersedia. Unggah bukti pembayaran pada halaman pembayaran agar pesanan dapat diverifikasi admin.</p>
                        </div>
                    </div>
                    <div class="faq-item reveal" data-faq-item>
                        <button type="button" class="faq-toggle" data-faq-toggle aria-expanded="false">
                            <span class="faq-question">Apakah bisa membatalkan penyewaan?</span>
                            <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="faq-answer">
                            <p>Ya, pembatalan mengikuti kebijakan pembatalan yang berlaku. Pengembalian dana yang memenuhi syarat akan diproses sesuai ketentuan yang berlaku.</p>
                        </div>
                    </div>
                    <div class="faq-item reveal" data-faq-item>
                        <button type="button" class="faq-toggle" data-faq-toggle aria-expanded="false">
                            <span class="faq-question">Bagaimana jika alat rusak?</span>
                            <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="faq-answer">
                            <p>Kerusakan akibat penggunaan yang tidak sesuai menjadi tanggung jawab penyewa. Biaya dibebankan berdasarkan hasil pemeriksaan admin.</p>
                        </div>
                    </div>
                    <div class="faq-item reveal" data-faq-item>
                        <button type="button" class="faq-toggle" data-faq-toggle aria-expanded="false">
                            <span class="faq-question">Bagaimana proses pengembalian alat?</span>
                            <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="faq-answer">
                            <p>Kembalikan peralatan sesuai tanggal yang ditentukan. Peralatan akan diperiksa admin, dan keterlambatan dapat dikenakan denda sesuai ketentuan.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Syarat & Ketentuan Section -->
    <section class="terms-section" id="terms">
        <div class="terms-container">
            <p class="section-eyebrow reveal">PERJANJIAN PENYEWAAN</p>
            <h2 class="section-title reveal">Syarat &amp; Ketentuan</h2>
            <p class="terms-subtitle reveal">Harap baca dan pahami ketentuan berikut sebelum melakukan penyewaan.</p>

            <div class="terms-grid">

                <article class="terms-card reveal" style="--stagger: 0;">
                    <div class="terms-card-header">
                        <span class="terms-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                            </svg>
                        </span>
                        <span class="terms-card-title">Ketentuan Penyewaan</span>
                    </div>
                    <ul class="terms-list">
                        <li>Penyewa wajib memiliki akun Summit Station yang aktif.</li>
                        <li>Data yang diberikan saat melakukan penyewaan harus benar dan dapat dipertanggungjawabkan.</li>
                        <li>Peralatan hanya boleh digunakan oleh penyewa sesuai dengan tujuan penggunaan yang wajar.</li>
                        <li>Penyewa bertanggung jawab menjaga peralatan selama masa penyewaan.</li>
                    </ul>
                </article>

                <article class="terms-card reveal" style="--stagger: 1;">
                    <div class="terms-card-header">
                        <span class="terms-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                        </span>
                        <span class="terms-card-title">Ketentuan Pembayaran</span>
                    </div>
                    <ul class="terms-list">
                        <li>Pembayaran harus dilakukan sesuai jumlah yang tertera pada sistem.</li>
                        <li>Penyewaan hanya dapat diproses setelah pembayaran berhasil diverifikasi.</li>
                        <li>Bukti pembayaran yang dikirim harus jelas dan merupakan bukti pembayaran yang sesuai dengan transaksi.</li>
                        <li>Jika pembayaran belum diverifikasi, pesanan belum dianggap selesai diproses.</li>
                    </ul>
                </article>

                <article class="terms-card reveal" style="--stagger: 0;">
                    <div class="terms-card-header">
                        <span class="terms-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="17 1 21 5 17 9"></polyline>
                                <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                                <polyline points="7 23 3 19 7 15"></polyline>
                                <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                            </svg>
                        </span>
                        <span class="terms-card-title">Pengambilan &amp; Pengembalian</span>
                    </div>
                    <ul class="terms-list">
                        <li>Peralatan harus dikembalikan sesuai tanggal dan waktu yang telah ditentukan.</li>
                        <li>Keterlambatan pengembalian dapat dikenakan denda sesuai ketentuan yang berlaku.</li>
                        <li>Penyewa wajib mengembalikan seluruh peralatan dalam kondisi yang sesuai saat diterima.</li>
                        <li>Setelah pengembalian, peralatan akan melalui proses pemeriksaan oleh admin.</li>
                    </ul>
                </article>

                <article class="terms-card reveal" style="--stagger: 1;">
                    <div class="terms-card-header">
                        <span class="terms-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                        </span>
                        <span class="terms-card-title">Kerusakan atau Kehilangan</span>
                    </div>
                    <ul class="terms-list">
                        <li>Penyewa bertanggung jawab apabila peralatan mengalami kerusakan akibat penggunaan yang tidak sesuai.</li>
                        <li>Kehilangan peralatan menjadi tanggung jawab penyewa.</li>
                        <li>Biaya kerusakan atau kehilangan dapat dibebankan kepada penyewa berdasarkan hasil pemeriksaan admin.</li>
                        <li>Kerusakan karena penggunaan normal akan ditentukan berdasarkan hasil pemeriksaan.</li>
                    </ul>
                </article>

                <article class="terms-card reveal" style="--stagger: 0;">
                    <div class="terms-card-header">
                        <span class="terms-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                        </span>
                        <span class="terms-card-title">Pembatalan Penyewaan</span>
                    </div>
                    <ul class="terms-list">
                        <li>Pembatalan penyewaan mengikuti kebijakan pembatalan yang berlaku pada Summit Station.</li>
                        <li>Pengembalian dana, apabila memenuhi syarat, akan diproses sesuai ketentuan yang berlaku.</li>
                        <li>Admin berhak melakukan verifikasi terhadap setiap permintaan pembatalan dan pengembalian dana.</li>
                    </ul>
                </article>

                <article class="terms-card reveal" style="--stagger: 1;">
                    <div class="terms-card-header">
                        <span class="terms-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                            </svg>
                        </span>
                        <span class="terms-card-title">Ketentuan Umum</span>
                    </div>
                    <ul class="terms-list">
                        <li>Dengan melakukan penyewaan, pengguna dianggap telah membaca dan memahami Syarat &amp; Ketentuan Summit Station.</li>
                        <li>Summit Station berhak memperbarui Syarat &amp; Ketentuan apabila diperlukan.</li>
                        <li>Setiap perubahan ketentuan akan ditampilkan pada halaman website.</li>
                    </ul>
                </article>

            </div>
        </div>
    </section>

    <!-- Footer -->
    @include('partials.footer', ['footerContext' => 'user'])

    <script>
        (function () {
            'use strict';
            var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var items = document.querySelectorAll('.reveal');

            // Scroll reveal with IntersectionObserver
            if (reduceMotion || !('IntersectionObserver' in window)) {
                items.forEach(function (el) { el.classList.add('revealed'); });
            } else {
                var io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('revealed');
                            io.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
                items.forEach(function (el) { io.observe(el); });
            }

            // Subtle GPU-accelerated Parallax on Hero Background
            var heroBg = document.getElementById('heroBg');
            if (heroBg && !reduceMotion) {
                var ticking = false;
                window.addEventListener('scroll', function () {
                    if (!ticking) {
                        window.requestAnimationFrame(function () {
                            var scrolled = window.scrollY;
                            if (scrolled < 850) {
                                heroBg.style.transform = 'translate3d(0, ' + (scrolled * 0.22).toFixed(1) + 'px, 0)';
                            }
                            ticking = false;
                        });
                        ticking = true;
                    }
                }, { passive: true });
            }

            // Countup animation if present
            var counter = document.querySelector('[data-countup]');
            if (counter && !reduceMotion && 'IntersectionObserver' in window) {
                var target = parseInt(counter.getAttribute('data-countup'), 10);
                var started = false;
                var cio = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (!entry.isIntersecting || started) return;
                        started = true;
                        cio.disconnect();
                        var t0 = performance.now();
                        var duration = 1400;
                        var tick = function (now) {
                            var progress = Math.min((now - t0) / duration, 1);
                            var eased = 1 - Math.pow(1 - progress, 3);
                            counter.textContent = Math.round(target * eased).toLocaleString('id-ID');
                            if (progress < 1) requestAnimationFrame(tick);
                        };
                        requestAnimationFrame(tick);
                    });
                }, { threshold: 0.4 });
                cio.observe(counter);
            }

            // Smooth scroll for #terms anchor
            var termsCard = document.getElementById('termsQuickCard');
            if (termsCard) {
                termsCard.addEventListener('click', function (e) {
                    var target = document.getElementById('terms');
                    if (target) {
                        e.preventDefault();
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            }

            // FAQ Accordion (independent open/close, one open at a time)
            var faqItems = document.querySelectorAll('[data-faq-item]');
            faqItems.forEach(function (item) {
                var toggle = item.querySelector('[data-faq-toggle]');
                if (!toggle) return;
                toggle.addEventListener('click', function () {
                    var isOpen = item.classList.contains('open');
                    faqItems.forEach(function (other) {
                        other.classList.remove('open');
                        var t = other.querySelector('[data-faq-toggle]');
                        if (t) t.setAttribute('aria-expanded', 'false');
                    });
                    if (!isOpen) {
                        item.classList.add('open');
                        toggle.setAttribute('aria-expanded', 'true');
                    }
                });
            });

            // Home search box clear button
            var searchInput = document.getElementById('homeSearchInput');
            var searchClear = document.getElementById('homeSearchClear');
            if (searchInput && searchClear) {
                searchInput.addEventListener('input', function () {
                    searchClear.hidden = searchInput.value.length === 0;
                });
                searchClear.addEventListener('click', function () {
                    searchInput.value = '';
                    searchClear.hidden = true;
                    searchInput.focus();
                });
            }

            // Testimonials expand / collapse
            var testimonialGrid = document.querySelector('.testimonial-grid');
            var testimonialToggle = document.getElementById('testimonialsToggleBtn');
            if (testimonialGrid && testimonialToggle) {
                var initialReviews = parseInt(testimonialGrid.getAttribute('data-initial-count'), 10) || 3;
                var testimonialCards = Array.prototype.slice.call(testimonialGrid.querySelectorAll(':scope > .testimonial-card'));
                var extraCards = testimonialCards.slice(initialReviews);
                var expanded = false;
                var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                if (extraCards.length === 0) {
                    testimonialToggle.hidden = true;
                } else {
                    testimonialToggle.addEventListener('click', function () {
                        expanded = !expanded;
                        testimonialToggle.classList.toggle('is-open', expanded);
                        testimonialToggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                        var labelEl = testimonialToggle.querySelector('.testimonials-toggle-label');
                        if (labelEl) {
                            labelEl.textContent = expanded ? 'Tampilkan Lebih Sedikit' : 'Lihat Semua Ulasan';
                        }

                        extraCards.forEach(function (card, i) {
                            if (expanded) {
                                card.classList.remove('is-collapsed');
                                card.classList.add('revealed', 'is-expanding');
                                if (!reduceMotion) {
                                    card.style.setProperty('--expand-delay', (i * 0.045) + 's');
                                } else {
                                    card.style.setProperty('--expand-delay', '0s');
                                }
                            } else {
                                card.classList.add('is-collapsed');
                                card.classList.remove('revealed', 'is-expanding');
                                card.style.removeProperty('--expand-delay');
                            }
                        });
                    });
                }
            }
        })();
    </script>

    <script>
        const dataUriAvatar = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><rect width="40" height="40" fill="#cbd5e1" rx="50%"/><circle cx="20" cy="15" r="7" fill="#64748b"/><path d="M4 36 c0 -7 7 -11 16 -11 s16 4 16 11 z" fill="#64748b"/></svg>');
    </script>
    <script src="{{ asset('js/summit-navbar.js') }}"></script>

</body>
</html>
