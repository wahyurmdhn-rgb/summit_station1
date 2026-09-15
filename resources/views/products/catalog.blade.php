<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}">
    <title>Katalog Peralatan Mendaki &amp; Paket Sewa - Summit Station</title>
    <link rel="stylesheet" href="{{ asset('css/summit-catalog.css') . '?v=' . filemtime(public_path('css/summit-catalog.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-footer.css') . '?v=' . filemtime(public_path('css/summit-footer.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-navbar.css') . '?v=' . filemtime(public_path('css/summit-navbar.css')) }}">
</head>
<body>

    <!-- Header / Navbar -->
    @include('layouts.navbar')

    <!-- Hero Header Section -->
    <header class="catalog-hero">
        <div class="catalog-hero-container">
            <div class="catalog-hero-content">
                <span class="catalog-eyebrow">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                        <polyline points="2 17 12 22 22 17"></polyline>
                        <polyline points="2 12 12 17 22 12"></polyline>
                    </svg>
                    PERALATAN EKSPEDISI GUNUNG
                </span>
                <h1 class="catalog-title">Katalog Peralatan &amp; Paket Sewa</h1>
                <p class="catalog-subtitle">
                    Pilihan perlengkapan outdoor kelas profesional dan paket sewa lengkap siap pakai yang higienis, terawat prima, dan teruji tangguh untuk setiap rute pendakian Anda.
                </p>
            </div>
            <div class="catalog-hero-stats">
                <div class="hero-stat-card">
                    <span class="stat-value">{{ sprintf('%02d', $paginatedProducts->total()) }}</span>
                    <span class="stat-label">Unit Alat</span>
                </div>
                <div class="hero-stat-card">
                    <span class="stat-value">{{ sprintf('%02d', $bundles->count()) }}</span>
                    <span class="stat-label">Paket Sewa</span>
                </div>
                <div class="hero-stat-card">
                    <span class="stat-value">{{ sprintf('%02d', $categories->count()) }}</span>
                    <span class="stat-label">Kategori</span>
                </div>
            </div>
        </div>
    </header>

    <form method="GET" action="{{ route('catalog') }}" id="catalog-filter-form">
        <!-- Search Bar Section -->
        <section class="search-bar-section">
            <div class="search-bar-container">
                <div class="search-input-wrapper">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" id="catalog-search-input" name="search" value="{{ $search }}" placeholder="Cari tenda ultralight, carrier, sleeping bag, kompor..." onkeydown="if(event.key==='Enter'){this.form.submit();}">
                    @if (!empty($search))
                        <button type="button" class="btn-clear-search" onclick="clearCatalogSearch()" title="Hapus pencarian">&times;</button>
                    @endif
                </div>

                <div class="sort-filter-actions">
                    <!-- Mobile filter toggle button -->
                    <button type="button" class="btn-mobile-filter" onclick="toggleMobileFilter()" aria-label="Buka Filter">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="4" y1="21" x2="4" y2="14"></line>
                            <line x1="4" y1="10" x2="4" y2="3"></line>
                            <line x1="12" y1="21" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12" y2="3"></line>
                            <line x1="20" y1="21" x2="20" y2="16"></line>
                            <line x1="20" y1="12" x2="20" y2="3"></line>
                            <line x1="1" y1="14" x2="7" y2="14"></line>
                            <line x1="9" y1="8" x2="15" y2="8"></line>
                            <line x1="17" y1="16" x2="23" y2="16"></line>
                        </svg>
                        <span>Filter</span>
                        @if (!empty($selectedCategories) || $availability !== 'all')
                            <span class="active-filter-badge">
                                {{ count($selectedCategories) + ($availability !== 'all' ? 1 : 0) }}
                            </span>
                        @endif
                    </button>

                    <div class="sort-selector-wrapper">
                        <span class="sort-label">URUTKAN:</span>
                        <div class="custom-select-wrap">
                            <select name="sort" onchange="this.form.submit()">
                                <option value="popular" {{ $sort === 'popular' ? 'selected' : '' }}>Terpopuler</option>
                                <option value="price_low" {{ $sort === 'price_low' ? 'selected' : '' }}>Harga: Rendah ke Tinggi</option>
                                <option value="price_high" {{ $sort === 'price_high' ? 'selected' : '' }}>Harga: Tinggi ke Rendah</option>
                                <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Terbaru</option>
                                <option value="rating" {{ $sort === 'rating' ? 'selected' : '' }}>Rating Tertinggi</option>
                            </select>
                            <svg class="select-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Catalog Container -->
        <main class="catalog-container">
            <!-- Sidebar Filters -->
            <aside class="sidebar-filter" id="catalog-sidebar-filter">
                <div class="sidebar-header-mobile">
                    <h3>Filter Peralatan</h3>
                    <button type="button" class="btn-close-filter" onclick="toggleMobileFilter()">&times;</button>
                </div>

                <!-- Category Filter -->
                <div class="filter-group">
                    <div class="filter-head">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 6h16M4 12h16M4 18h7"></path>
                        </svg>
                        <h3 class="filter-title">Kategori</h3>
                    </div>
                    <ul class="filter-list">
                        @foreach ($categories as $cat)
                            @php
                                $isChecked = in_array($cat->slug, $selectedCategories) || in_array((string)$cat->id, $selectedCategories);
                            @endphp
                            <li class="filter-item {{ $isChecked ? 'active' : '' }}">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="category[]" value="{{ $cat->slug }}" {{ $isChecked ? 'checked' : '' }} onchange="this.form.submit()">
                                    <span class="custom-check">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                    </span>
                                    <span class="cat-name">{{ $cat->name }}</span>
                                    <span class="cat-count">{{ $cat->products_count }}</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="filter-divider"></div>

                <!-- Availability Filter -->
                <div class="filter-group">
                    <div class="filter-head">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 14 14"></polyline>
                        </svg>
                        <h3 class="filter-title">Ketersediaan</h3>
                    </div>
                    <ul class="filter-list">
                        <li class="filter-item {{ $availability === 'all' ? 'active' : '' }}">
                            <label class="radio-label">
                                <input type="radio" name="availability" value="all" {{ $availability === 'all' ? 'checked' : '' }} onchange="this.form.submit()">
                                <span class="custom-radio"></span>
                                <span>Semua Peralatan</span>
                            </label>
                        </li>
                        <li class="filter-item {{ $availability === 'available' ? 'active' : '' }}">
                            <label class="radio-label">
                                <input type="radio" name="availability" value="available" {{ $availability === 'available' ? 'checked' : '' }} onchange="this.form.submit()">
                                <span class="custom-radio"></span>
                                <span>Hanya Tersedia Sekarang</span>
                            </label>
                        </li>
                    </ul>
                </div>

                <div class="filter-divider"></div>

                @if (!empty($search) || !empty($selectedCategories) || $availability !== 'all' || $sort !== 'popular')
                    <div class="reset-filter-box">
                        <a href="{{ route('catalog') }}" class="btn-reset-filters">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                                <polyline points="3 3 3 8 8 8"></polyline>
                            </svg>
                            Reset Filter &amp; Pencarian
                        </a>
                    </div>
                @endif

                <!-- Summit Protection Badge Card -->
                <div class="protection-badge-card">
                    <div class="protection-badge-header">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            <polyline points="9 12 11 14 15 10"></polyline>
                        </svg>
                        <span>Perlindungan Summit</span>
                    </div>
                    <p>Semua penyewaan sudah termasuk asuransi kerusakan wajar dan sanitasi standar ekspedisi.</p>
                </div>
            </aside>

            <!-- Main Catalog Content -->
            <section class="catalog-content">
                <!-- Paket Sewa Section (Exclusive Bundles) -->
                @if ($bundles->isNotEmpty())
                    <div class="bundles-section">
                        <div class="section-header-row">
                            <div class="section-title-wrap">
                                <h2 class="section-title-green">Paket Sewa</h2>
                                <span class="section-count-tag">{{ $bundles->count() }} Paket</span>
                            </div>
                            <span class="exclusive-bundles-tag">PAKET EKSKLUSIF</span>
                        </div>

                        <div class="bundles-grid">
                            @foreach ($bundles as $bundle)
                                <article class="bundle-card" style="--card-idx: {{ $loop->index }};">
                                    <div class="bundle-image-wrapper">
                                        <img src="{{ $bundle['image'] }}" alt="{{ $bundle['name'] }}" onerror="handleImgError(this)" loading="lazy">
                                        @if (isset($bundle['in_stock']))
                                            @if ($bundle['in_stock'])
                                                <span class="badge-stock in-stock"><span class="dot" aria-hidden="true"></span> Stok: {{ $bundle['stock_available'] }}</span>
                                            @else
                                                <span class="badge-stock fully-booked"><span class="dot" aria-hidden="true"></span> STOK HABIS</span>
                                            @endif
                                        @endif
                                    </div>
                                    <div class="bundle-info">
                                        <div class="bundle-content-top">
                                            <span class="bundle-category-tag">PAKET SEWA LENGKAP</span>
                                            <h3 class="bundle-title" title="{{ $bundle['name'] }}">{{ $bundle['name'] }}</h3>
                                            <p class="bundle-description">{{ $bundle['description'] }}</p>
                                        </div>
                                        <div class="bundle-footer">
                                            <div class="bundle-price-wrap">
                                                <div class="bundle-rate-label">Harga Paket</div>
                                                <div class="bundle-price-row">
                                                    Rp {{ number_format($bundle['price'], 0, ',', '.') }}<span>/hari</span>
                                                </div>
                                            </div>
                                            @if (!empty($bundle['in_stock']))
                                                <a href="{{ route('catalog.bundle', $bundle['id']) }}" class="btn-booking-bundle">
                                                    <span>Booking</span>
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                        <polyline points="9 18 15 12 9 6"></polyline>
                                                    </svg>
                                                </a>
                                            @else
                                                <button type="button" class="btn-waitlist" disabled>Stok Habis</button>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Individual Gear Products Grid -->
                <div class="products-section">
                    <div class="section-header-row">
                        <div class="section-title-wrap">
                            <h2 class="section-title-green">Peralatan Ekspedisi</h2>
                            <span class="section-count-tag">{{ $paginatedProducts->total() }} Unit</span>
                        </div>
                        <span class="gear-legend-text">
                            Menampilkan {{ $paginatedProducts->total() }} peralatan pendakian
                        </span>
                    </div>

                    <div class="products-grid">
                        @forelse ($items as $item)
                            @php
                                $catIconMap = [
                                    'tenda' => 'tent',
                                    'tents-shelters' => 'tent',
                                    'tas' => 'backpack',
                                    'backpacks' => 'backpack',
                                    'alat-tidur' => 'sleep',
                                    'sleeping-gear' => 'sleep',
                                    'alat-memasak' => 'cook',
                                    'cooking' => 'cook',
                                    'climbing' => 'gear',
                                    'hardware' => 'hardware',
                                    'pencahayaan' => 'lamp',
                                    'lighting' => 'lamp',
                                ];
                                $catIconName = $catIconMap[$item['category_slug'] ?? ''] ?? 'gear';
                                if ($catIconName === 'gear') {
                                    $catLabelLower = strtolower($item['category'] . ' ' . ($item['category_slug'] ?? ''));
                                    if (str_contains($catLabelLower, 'tent') || str_contains($catLabelLower, 'tenda')) { $catIconName = 'tent'; }
                                    elseif (str_contains($catLabelLower, 'backpack') || str_contains($catLabelLower, 'tali') || str_contains($catLabelLower, 'tas')) { $catIconName = 'backpack'; }
                                    elseif (str_contains($catLabelLower, 'sleep') || str_contains($catLabelLower, 'tidur')) { $catIconName = 'sleep'; }
                                    elseif (str_contains($catLabelLower, 'cook') || str_contains($catLabelLower, 'masak')) { $catIconName = 'cook'; }
                                    elseif (str_contains($catLabelLower, 'climb') || str_contains($catLabelLower, 'panjat')) { $catIconName = 'gear'; }
                                    elseif (str_contains($catLabelLower, 'lampu') || str_contains($catLabelLower, 'cahaya') || str_contains($catLabelLower, 'penerangan')) { $catIconName = 'lamp'; }
                                    elseif (str_contains($catLabelLower, 'hardware')) { $catIconName = 'hardware'; }
                                }
                                $catSvgPaths = [
                                    'tent' => '<path d="M5 21h14l-7-18L5 21z"/><path d="M5 21l7-7 7 7"/>',
                                    'backpack' => '<path d="M9 4h6v4H9z"/><path d="M6 8h12a2 2 0 0 1 2 2v10H4V10a2 2 0 0 1 2-2z"/><path d="M8 16h8"/><path d="M12 8v4"/>',
                                    'sleep' => '<rect x="4" y="9" width="16" height="7" rx="2"/><path d="M8 9V6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v3"/><path d="M8 20v-4M16 20v-4"/>',
                                    'cook' => '<path d="M4 11h16v1a6 6 0 0 1-6 6h-4a6 6 0 0 1-6-6v-1z"/><path d="M2 7h20"/><path d="M10 11V6M14 11V6"/>',
                                    'hardware' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
                                    'lamp' => '<path d="M9 18h6"/><path d="M10 22h4"/><path d="M8 3h8l3 6H5l3-6z"/><path d="M5 9h14"/><path d="M6 12l1 3M18 12l-1 3"/>',
                                    'gear' => '<path d="M3 20l6-10 4 6 2-3 6 7H3z"/>',
                                ];
                                $catIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $catSvgPaths[$catIconName] . '</svg>';
                            @endphp
                            <article class="product-card" style="--card-idx: {{ $loop->index }};">
                                <div class="product-image-box">
                                    <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" onerror="handleImgError(this)" loading="lazy">
                                    @if ($item['in_stock'])
                                        <span class="badge-stock in-stock"><span class="dot" aria-hidden="true"></span> Stok: {{ $item['stock_available'] }}</span>
                                    @else
                                        <span class="badge-stock fully-booked"><span class="dot" aria-hidden="true"></span> STOK HABIS</span>
                                    @endif

                                    @if (isset($item['rating']))
                                        <div class="badge-rating">
                                            <span class="star-gold">★</span>
                                            <span>{{ $item['rating'] }}</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="product-body">
                                    <div>
                                        <div class="product-category">
                                            <span class="cat-icon" aria-hidden="true">{!! $catIcon !!}</span>
                                            <span class="cat-label">{{ $item['category'] }}</span>
                                        </div>
                                        <h3 class="product-title" title="{{ $item['name'] }}">{{ $item['name'] }}</h3>
                                    </div>

                                    <div class="product-footer-wrap">
                                        <div class="product-price-block">
                                            <div class="product-rate-label">Tarif Harian</div>
                                            <div class="product-price">
                                                <span class="price-amount">Rp {{ number_format($item['price'], 0, ',', '.') }}</span><span class="price-per-day">/hari</span>
                                            </div>
                                        </div>

                                        @if ($item['in_stock'])
                                            <div class="product-actions">
                                                <a href="{{ route('catalog.show', $item['id']) }}" class="btn-details">
                                                    Detail
                                                    <svg class="btn-arrow" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <polyline points="9 18 15 12 9 6"></polyline>
                                                    </svg>
                                                </a>
                                                <a href="{{ route('catalog.show', $item['id']) }}" class="btn-rent-now">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                                    </svg>
                                                    Booking
                                                </a>
                                            </div>
                                        @else
                                            <button type="button" class="btn-waitlist" disabled>Stok Habis</button>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="empty-catalog-box">
                                <div class="empty-catalog-icon">
                                    <svg width="72" height="72" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="60" cy="60" r="56" fill="#f0f7f2" stroke="#d5e6da" stroke-width="2" />
                                        <path d="M22 84L46 48L64 74L78 56L98 84H22Z" fill="#cfe3d4" />
                                        <path d="M42 84L60 58L72 74L84 62L98 84H42Z" fill="#b1d1bc" />
                                        <polygon points="50,84 65,60 80,84" fill="#185d31" />
                                        <polygon points="65,60 65,84 78,84" fill="#2d7748" />
                                        <polygon points="61,84 65,72 69,84" fill="#e9ba62" />
                                        <circle cx="88" cy="34" r="7" fill="#facc15" />
                                    </svg>
                                </div>
                                <h3 class="empty-catalog-title">Alat Outdoor Tidak Ditemukan</h3>
                                <p class="empty-catalog-desc">
                                    Tidak ada peralatan atau paket yang cocok dengan kriteria filter atau kata kunci pencarian Anda. Silakan atur ulang filter pencarian Anda.
                                </p>
                                <a href="{{ route('catalog') }}" class="btn-empty-reset">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                                        <polyline points="3 3 3 8 8 8"></polyline>
                                    </svg>
                                    Reset Filter &amp; Tampilkan Semua Alat
                                </a>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Real Pagination -->
                @if ($paginatedProducts->hasPages())
                    <div class="pagination-container">
                        {{ $paginatedProducts->links('pagination::custom') }}
                    </div>
                @endif
            </section>
        </main>
    </form>

    <!-- Footer -->
    @include('partials.footer', ['footerContext' => 'user'])

    <script>
        const IMG_PLACEHOLDER = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300"><rect width="400" height="300" fill="#e8ece8"/><path d="M0 230 L110 130 L180 205 L250 150 L400 285 L400 400 L0 400 Z" fill="#a3baa3"/><path d="M150 300 L150 240 L115 300 Z" fill="#7d9a7d"/><circle cx="110" cy="130" r="13" fill="#fff"/><text x="200" y="285" font-family="Arial, sans-serif" font-size="15" fill="#6b7a6b" text-anchor="middle">Gambar tidak tersedia</text></svg>');
        function handleImgError(img) {
            if (img.dataset.ph) return;
            img.dataset.ph = '1';
            img.onerror = null;
            img.src = IMG_PLACEHOLDER;
        }

        function clearCatalogSearch() {
            const input = document.getElementById('catalog-search-input');
            if (input) {
                input.value = '';
                input.form.submit();
            }
        }

        function toggleMobileFilter() {
            const sidebar = document.getElementById('catalog-sidebar-filter');
            if (sidebar) {
                sidebar.classList.toggle('is-open-mobile');
                document.body.classList.toggle('filter-drawer-open');
            }
        }
    </script>
    <script src="{{ asset('js/summit-navbar.js') }}"></script>
</body>
</html>
