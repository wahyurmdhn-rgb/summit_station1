<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}">
    <title>Alat - Kelola Produk - Summit Station</title>
    <link rel="stylesheet" href="{{ asset('css/summit-admin.css') . '?v=' . time() }}">
    <link rel="stylesheet" href="{{ asset('css/summit-footer.css') . '?v=' . filemtime(public_path('css/summit-footer.css')) }}">
</head>
<body>

    <!-- Blue Top Accent Line -->
    <div class="top-banner-line"></div>

    <div class="admin-layout">
        <!-- ─── Sidebar ─── -->
        @include('admin.partials.sidebar', ['activeMenu' => 'alat'])

        <!-- ─── Main Content ─── -->
        <div class="admin-main">
            <!-- Top Header -->
            @include('admin.partials.header', [
                'adminPageTitle' => 'Kelola Produk',
                'adminPageSubtitle' => 'Atur inventaris produk & paket sewa',
            ])

            <!-- Main Content Body -->
            <main class="admin-content">
                @if (session('status'))
                    <div class="admin-flash-status">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div style="background-color: #fee2e2; border-left: 4px solid #ef4444; padding: 14px 18px; border-radius: 8px; color: #991b1b; font-size: 13px; font-weight: 700; margin-bottom: 20px;">
                        {{ $errors->first() }}
                    </div>
                @endif

                <!-- ─── Page Heading & Top Value Row ─── -->
                <div class="alat-header-row">
                    <div class="alat-title-block">
                        <div class="inventory-tag">KONTROL INVENTARIS</div>
                        <h1 class="page-title-main">Kelola Produk</h1>
                        <p class="page-desc-text">
                            Pantau dan kelola katalog peralatan outdoor premium Anda. Pastikan tingkat stok dan harga mencerminkan permintaan musiman saat ini.
                        </p>
                    </div>

                    <div class="alat-header-actions">
                        <div class="total-value-card">
                            <div class="total-value-label">TOTAL NILAI</div>
                            <div class="total-value-amount">
                                Rp {{ number_format($totalValue, 0, ',', '.') }}
                            </div>
                        </div>

                        <button type="button" class="btn-add-product" onclick="openAddModal()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="16"></line>
                                <line x1="8" y1="12" x2="16" y2="12"></line>
                            </svg>
                            <span>TAMBAH PRODUK</span>
                        </button>
                        <button type="button" class="btn-add-product" onclick="openBundleAddModal()" style="margin-left: 10px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                <line x1="12" y1="22.08" x2="12" y2="12"></line>
                            </svg>
                            <span>TAMBAH PAKET</span>
                        </button>
                        <button type="button" class="btn-add-product" onclick="openCategoryModal()" style="margin-left: 10px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="8" y1="6" x2="21" y2="6"></line>
                                <line x1="8" y1="12" x2="21" y2="12"></line>
                                <line x1="8" y1="18" x2="21" y2="18"></line>
                                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                <line x1="3" y1="18" x2="3.01" y2="18"></line>
                            </svg>
                            <span>KELOLA KATEGORI</span>
                        </button>
                    </div>
                </div>

                <!-- ─── 3 Stats Cards Row ─── -->
                <div class="stats-grid-alat">
                    <!-- Stat 1: Total SKUs -->
                    <div class="stat-card-alat">
                        <div class="stat-card-alat-top">
                            <div class="icon-circle-alat green">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                </svg>
                            </div>
                            <span class="badge-positive">Total</span>
                        </div>
                        <div>
                            <div class="stat-card-alat-value">{{ $totalSkus }}</div>
                            <div class="stat-card-alat-label">Total SKU</div>
                        </div>
                    </div>

                    <!-- Stat 2: Low Stock Alerts -->
                    <div class="stat-card-alat">
                        <div class="stat-card-alat-top">
                            <div class="icon-circle-alat orange">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                    <line x1="12" y1="9" x2="12" y2="13"></line>
                                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                </svg>
                            </div>
                            <span class="badge-critical">Kritis</span>
                        </div>
                        <div>
                            <div class="stat-card-alat-value">{{ $lowStockCount }}</div>
                            <div class="stat-card-alat-label">Peringatan Stok Menipis</div>
                        </div>
                    </div>

                    <!-- Stat 3: Product Categories -->
                    <div class="stat-card-alat">
                        <div class="stat-card-alat-top">
                            <div class="icon-circle-alat green">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="14" width="7" height="7"></rect>
                                    <rect x="3" y="14" width="7" height="7"></rect>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <div class="stat-card-alat-value">{{ $categoriesCount }}</div>
                            <div class="stat-card-alat-label">Kategori Produk</div>
                        </div>
                    </div>
                </div>

                <!-- ─── Filter & Counter Row ─── -->
                <form method="GET" action="{{ route('admin.alat') }}" class="filter-bar-row">
                    @if ($searchTerm)
                        <input type="hidden" name="search" value="{{ $searchTerm }}">
                    @endif

                    <div class="filter-left-actions">
                        <!-- Category Filter Dropdown -->
                        <div style="position: relative; display: inline-block;">
                            <select name="category" class="filter-select" onchange="this.form.submit()">
                                <option value="all" {{ $selectedCategory === 'all' ? 'selected' : '' }}>≡ Semua Kategori</option>
                                <option value="paket-sewa" {{ $selectedCategory === 'paket-sewa' ? 'selected' : '' }}>Paket Sewa</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->slug }}" {{ $selectedCategory === $cat->slug ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Sort Dropdown -->
                        <div style="position: relative; display: inline-block;">
                            <select name="sort" class="filter-select" onchange="this.form.submit()">
                                <option value="terbaru" {{ $selectedSort === 'terbaru' ? 'selected' : '' }}>≡ Terbaru</option>
                                <option value="stok_asc" {{ $selectedSort === 'stok_asc' ? 'selected' : '' }}>Stok Terendah</option>
                                <option value="stok_desc" {{ $selectedSort === 'stok_desc' ? 'selected' : '' }}>Stok Tertinggi</option>
                                <option value="harga_asc" {{ $selectedSort === 'harga_asc' ? 'selected' : '' }}>Harga Terendah</option>
                                <option value="harga_desc" {{ $selectedSort === 'harga_desc' ? 'selected' : '' }}>Harga Tertinggi</option>
                                <option value="nama_asc" {{ $selectedSort === 'nama_asc' ? 'selected' : '' }}>Nama A - Z</option>
                            </select>
                        </div>
                    </div>

                    <div class="results-counter-text">
                        Menampilkan {{ $products->firstItem() ?? 1 }} - {{ $products->lastItem() ?? count($products) }} dari {{ $products->total() ?? $totalSkus }} Item
                    </div>
                </form>

                <!-- ─── Table Produk / Alat ─── -->
                <div class="alat-table-container">
                    <div class="table-responsive">
                        <table class="alat-table">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Kategori</th>
                                    <th>Harga Sewa / Hari</th>
                                    <th>Stok</th>
                                    <th>Status</th>
                                    <th style="text-align: right;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse ($products as $item)
                                @php
                                    $isBundle = ($item['is_bundle'] ?? false) === true;
                                    $isLow = $item['stock'] <= 5;
                                    $stockPercent = $item['stock_total'] > 0 ? round(($item['stock'] / $item['stock_total']) * 100) : 0;
                                @endphp
                                <tr>
                                    <!-- Produk Details -->
                                    <td>
                                        <div class="product-cell">
                                            <img src="{{ $item['image'] }}"
                                                 alt="{{ $item['name'] }}"
                                                 class="product-thumb-img">
                                            <div class="product-info-details">
                                                <span class="product-name-txt">{{ $item['name'] }}</span>
                                                <span class="product-sku-txt">{{ $isBundle ? 'Kode: ' : 'SKU: ' }}{{ $item['kode'] }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Kategori / Tipe Badge -->
                                    <td>
                                        <span class="category-badge-pill">
                                            {{ $item['category'] }}
                                        </span>
                                    </td>

                                    <!-- Harga Sewa / Hari (Paket Sewa = harga paket) -->
                                    <td>
                                        <span class="price-text-bold">
                                            Rp {{ number_format($item['price'], 0, ',', '.') }}
                                        </span>
                                        @if ($isBundle)
                                            <div style="font-size: 10px; color: #94a3b8; font-weight: 600; margin-top: 2px;">per paket</div>
                                        @endif
                                    </td>

                                    <!-- Stok Progress -->
                                    <td>
                                        <div class="stock-progress-block">
                                            <span class="stock-count-label {{ $isLow ? 'low' : '' }}">
                                                {{ $item['stock'] }} {{ $isBundle ? 'Paket' : 'Unit' }}
                                            </span>
                                            <div class="stock-mini-bar">
                                                <div class="stock-mini-fill {{ $isLow ? 'orange' : 'green' }}"
                                                     style="width: {{ $stockPercent }}%;"></div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td>
                                        @if (! $item['is_active'])
                                            <span class="badge-status inactive">
                                                <span class="status-dot"></span> NONAKTIF
                                            </span>
                                        @elseif ($item['stock'] <= 0)
                                            <span class="badge-status inactive">
                                                <span class="status-dot"></span> HABIS
                                            </span>
                                        @elseif ($isLow)
                                            <span class="badge-status lowstock">
                                                <span class="status-dot"></span> STOK MENIPIS
                                            </span>
                                        @else
                                            <span class="badge-status active">
                                                <span class="status-dot"></span> AKTIF
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Aksi -->
                                    <td style="text-align: right;">
                                        <div class="action-icons-group" style="justify-content: flex-end;">
                                            @if ($isBundle)
                                                <!-- Edit Paket Sewa -->
                                                <button type="button"
                                                        class="btn-action-icon"
                                                        title="Edit Paket Sewa"
                                                        onclick='openBundleEditModal(@json($item))'>
                                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                    </svg>
                                                </button>

                                                <!-- Toggle Status Paket Sewa -->
                                                <form method="POST" action="{{ route('admin.alat.bundle.toggle', $item['id']) }}" style="display: inline;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                            class="btn-action-icon"
                                                            title="{{ $item['is_active'] ? 'Nonaktifkan Paket' : 'Aktifkan Paket' }}">
                                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <circle cx="12" cy="12" r="10"></circle>
                                                            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                                                        </svg>
                                                    </button>
                                                </form>

                                                <!-- Delete Paket Sewa -->
                                                <button type="button"
                                                        class="btn-action-icon delete"
                                                        title="Hapus Paket Sewa"
                                                        onclick="confirmDeleteBundle('{{ $item['id'] }}', '{{ addslashes($item['name']) }}')">
                                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                    </svg>
                                                </button>
                                            @else
                                                <!-- Edit Button (Peralatan) -->
                                                <button type="button"
                                                        class="btn-action-icon"
                                                        title="Edit Alat"
                                                        onclick='openEditModal(@json($item["model"]))'>
                                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                    </svg>
                                                </button>

                                                <!-- Toggle Status Button (Peralatan) -->
                                                <form method="POST" action="{{ route('admin.alat.toggle', $item['id']) }}" style="display: inline;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                            class="btn-action-icon"
                                                            title="{{ $item['is_active'] ? 'Nonaktifkan Alat' : 'Aktifkan Alat' }}">
                                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <circle cx="12" cy="12" r="10"></circle>
                                                            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                                                        </svg>
                                                    </button>
                                                </form>

                                                <!-- Delete Button (Peralatan) -->
                                                <button type="button"
                                                        class="btn-action-icon delete"
                                                        title="Hapus Alat"
                                                        onclick="confirmDelete('{{ $item['id'] }}', '{{ addslashes($item['name']) }}')">
                                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 48px 20px; color: #64748b;">
                                        Tidak ada data alat yang ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>

                <!-- Pagination if needed -->
                @if ($products->hasPages())
                    <div style="margin-top: 20px; display: flex; justify-content: center;">
                        <div class="pagination-pages-list">
                            {{-- Previous Page Link --}}
                            @if ($products->onFirstPage())
                                <span class="page-nav-link" style="opacity: 0.4; cursor: not-allowed;">&lsaquo;</span>
                            @else
                                <a href="{{ $products->previousPageUrl() }}" class="page-nav-link">&lsaquo;</a>
                            @endif

                            {{-- Pagination Elements --}}
                            @foreach ($products->getUrlRange(1, $products->lastPage()) as $page => $url)
                                @if ($page == $products->currentPage())
                                    <span class="page-nav-link active">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="page-nav-link">{{ $page }}</a>
                                @endif
                            @endforeach

                            {{-- Next Page Link --}}
                            @if ($products->hasMorePages())
                                <a href="{{ $products->nextPageUrl() }}" class="page-nav-link">&rsaquo;</a>
                            @else
                                <span class="page-nav-link" style="opacity: 0.4; cursor: not-allowed;">&rsaquo;</span>
                            @endif
                        </div>
                    </div>
                @endif
            </main>

            <!-- Footer -->
            @include('partials.footer', ['footerContext' => 'admin'])
        </div>
    </div>

    <!-- ─── Modal Tambah Alat ─── -->
    <div id="addModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modal-title">Tambah Produk / Alat Baru</h3>
                <button type="button" class="btn-close-modal" onclick="closeAddModal()">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.alat.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group-modal">
                        <label class="form-label-modal">Nama Alat / Produk *</label>
                        <input type="text" name="name" class="form-input-modal" placeholder="Contoh: Apex Ultralight V2" required>
                    </div>

                    <div class="form-row-grid-2">
                        <div class="form-group-modal">
                            <label class="form-label-modal">SKU Alat *</label>
                            <input type="text" name="sku" class="form-input-modal" placeholder="Contoh: TENT-001-OR" required>
                        </div>
                        <div class="form-group-modal">
                            <label class="form-label-modal">Kategori *</label>
                            <select name="category_id" class="form-select-modal" required>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-row-grid-2">
                        <div class="form-group-modal">
                            <label class="form-label-modal">Harga Sewa / Hari (Rp) *</label>
                            <input type="number" name="price_per_day" class="form-input-modal" placeholder="250000" min="0" required>
                        </div>
                        <div class="form-group-modal">
                            <label class="form-label-modal">Subjudul / Tipe</label>
                                    <input type="text" name="subtitle" class="form-input-modal" placeholder="Contoh: Ransel Ekspedisi 4 Musim">
                        </div>
                    </div>

                    <div class="form-row-grid-2">
                        <div class="form-group-modal">
                            <label class="form-label-modal">Stok Total *</label>
                            <input type="number" name="stock_total" class="form-input-modal" placeholder="15" min="1" required>
                        </div>
                        <div class="form-group-modal">
                            <label class="form-label-modal">Stok Tersedia *</label>
                            <input type="number" name="stock_available" class="form-input-modal" placeholder="12" min="0" required>
                        </div>
                    </div>

                    <div class="form-row-grid-2">
                        <div class="form-group-modal">
                            <label class="form-label-modal">Berat</label>
                            <input type="text" name="weight" class="form-input-modal" placeholder="Contoh: 3.2 kg">
                        </div>
                        <div class="form-group-modal">
                            <label class="form-label-modal">Kapasitas</label>
                            <input type="text" name="capacity" class="form-input-modal" placeholder="Contoh: 2-4 Orang">
                        </div>
                    </div>

                    <div class="form-row-grid-2">
                        <div class="form-group-modal">
                            <label class="form-label-modal">Kondisi *</label>
                            <select name="condition" class="form-select-modal" required>
                                <option value="Excellent" selected>Excellent (Sangat Baik)</option>
                                <option value="Good">Good (Baik)</option>
                                <option value="Fair">Fair (Cukup)</option>
                                <option value="Needs Repair">Needs Repair (Perlu Perbaikan)</option>
                            </select>
                        </div>
                        <div class="form-group-modal">
                            <label class="form-label-modal">Grade *</label>
                            <select name="grade" class="form-select-modal" required>
                                <option value="PRO-GRADE" selected>PRO-GRADE</option>
                                <option value="PREMIUM">PREMIUM</option>
                                <option value="STANDARD">STANDARD</option>
                                <option value="BASIC">BASIC</option>
                                <option value="ULTRALIGHT">ULTRALIGHT</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group-modal">
                        <label class="form-label-modal">URL Gambar Utama</label>
                        <input type="text" name="main_image" id="add_main_image" class="form-input-modal" placeholder="https://contoh.com/gambar.jpg  atau  /images/nama-gambar.png" oninput="previewAddImage(this.value)">
                        <img id="add_main_preview" alt="Pratinjau Gambar" style="display:none; margin-top:10px; max-width:160px; max-height:120px; border:1px solid #e2e8f0; border-radius:8px; object-fit:cover;">
                        <p id="add_main_hint" style="display:none; margin-top:8px; font-size:12px;"></p>
                        <p style="margin-top:6px; font-size:12px; color:#94a3b8;">
                            Masukkan <strong>tautan gambar langsung</strong> (URL), bukan path lokal seperti <code>C:\...</code>.
                            Untuk gambar di folder <code>public/images</code>, isi <code>/images/nama-gambar.png</code> (spasi diganti <code>%20</code>).
                        </p>
                    </div>

                    <div class="form-group-modal">
                        <label class="form-label-modal">Deskripsi Alat</label>
                        <textarea name="description" rows="3" class="form-textarea-modal" placeholder="Deskripsi spesifikasi alat..."></textarea>
                    </div>

                    <div class="form-group-modal" style="flex-direction: row; align-items: center; gap: 8px;">
                        <input type="checkbox" name="is_active" id="add_is_active" value="1" checked>
                        <label for="add_is_active" class="form-label-modal" style="margin-bottom: 0; cursor: pointer;">Status Aktif untuk Disewa</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" onclick="closeAddModal()">Batal</button>
                    <button type="submit" class="btn-modal-submit">Simpan Alat</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── Modal Edit Alat ─── -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modal-title">Edit Data Alat</h3>
                <button type="button" class="btn-close-modal" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group-modal">
                        <label class="form-label-modal">Nama Alat / Produk *</label>
                        <input type="text" name="name" id="edit_name" class="form-input-modal" required>
                    </div>

                    <div class="form-row-grid-2">
                        <div class="form-group-modal">
                            <label class="form-label-modal">SKU Alat *</label>
                            <input type="text" name="sku" id="edit_sku" class="form-input-modal" required>
                        </div>
                        <div class="form-group-modal">
                            <label class="form-label-modal">Kategori *</label>
                            <select name="category_id" id="edit_category_id" class="form-select-modal" required>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-row-grid-2">
                        <div class="form-group-modal">
                            <label class="form-label-modal">Harga Sewa / Hari (Rp) *</label>
                            <input type="number" name="price_per_day" id="edit_price_per_day" class="form-input-modal" min="0" required>
                        </div>
                        <div class="form-group-modal">
                            <label class="form-label-modal">Subjudul / Tipe</label>
                            <input type="text" name="subtitle" id="edit_subtitle" class="form-input-modal">
                        </div>
                    </div>

                    <div class="form-row-grid-2">
                        <div class="form-group-modal">
                            <label class="form-label-modal">Stok Total *</label>
                            <input type="number" name="stock_total" id="edit_stock_total" class="form-input-modal" min="1" required>
                        </div>
                        <div class="form-group-modal">
                            <label class="form-label-modal">Stok Tersedia *</label>
                            <input type="number" name="stock_available" id="edit_stock_available" class="form-input-modal" min="0" required>
                        </div>
                    </div>

                    <div class="form-row-grid-2">
                        <div class="form-group-modal">
                            <label class="form-label-modal">Berat</label>
                            <input type="text" name="weight" id="edit_weight" class="form-input-modal" placeholder="Contoh: 3.2 kg">
                        </div>
                        <div class="form-group-modal">
                            <label class="form-label-modal">Kapasitas</label>
                            <input type="text" name="capacity" id="edit_capacity" class="form-input-modal" placeholder="Contoh: 2-4 Orang">
                        </div>
                    </div>

                    <div class="form-row-grid-2">
                        <div class="form-group-modal">
                            <label class="form-label-modal">Kondisi *</label>
                            <select name="condition" id="edit_condition" class="form-select-modal" required>
                                <option value="Excellent">Excellent (Sangat Baik)</option>
                                <option value="Good">Good (Baik)</option>
                                <option value="Fair">Fair (Cukup)</option>
                                <option value="Needs Repair">Needs Repair (Perlu Perbaikan)</option>
                            </select>
                        </div>
                        <div class="form-group-modal">
                            <label class="form-label-modal">Grade *</label>
                            <select name="grade" id="edit_grade" class="form-select-modal" required>
                                <option value="PRO-GRADE">PRO-GRADE</option>
                                <option value="PREMIUM">PREMIUM</option>
                                <option value="STANDARD">STANDARD</option>
                                <option value="BASIC">BASIC</option>
                                <option value="ULTRALIGHT">ULTRALIGHT</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group-modal">
                        <label class="form-label-modal">URL Gambar Utama</label>
                        <input type="text" name="main_image" id="edit_main_image" class="form-input-modal" oninput="previewEditImage(this.value)">
                        <img id="edit_main_preview" alt="Pratinjau Gambar" style="display:none; margin-top:10px; max-width:160px; max-height:120px; border:1px solid #e2e8f0; border-radius:8px; object-fit:cover;">
                        <p id="edit_main_hint" style="display:none; margin-top:8px; font-size:12px;"></p>
                        <p style="margin-top:6px; font-size:12px; color:#94a3b8;">
                            Gunakan tautan gambar langsung, mis. <code>https://...</code> atau <code>/images/nama-gambar.png</code>. Path <code>C:\...</code> tidak akan tampil.
                        </p>
                    </div>

                    <div class="form-group-modal">
                        <label class="form-label-modal">Deskripsi Alat</label>
                        <textarea name="description" id="edit_description" rows="3" class="form-textarea-modal"></textarea>
                    </div>

                    <div class="form-group-modal" style="flex-direction: row; align-items: center; gap: 8px;">
                        <input type="checkbox" name="is_active" id="edit_is_active" value="1">
                        <label for="edit_is_active" class="form-label-modal" style="margin-bottom: 0; cursor: pointer;">Status Aktif untuk Disewa</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" onclick="closeEditModal()">Batal</button>
                    <button type="submit" class="btn-modal-submit">Perbarui Alat</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── Modal Edit Paket Sewa ─── -->
    <div id="bundleEditModal" class="modal-overlay">
        <div class="modal-card" style="width: 640px; max-width: 95vw;">
            <div class="modal-header">
                <h3 class="modal-title">Edit Paket Sewa</h3>
                <button type="button" class="btn-close-modal" onclick="closeBundleEditModal()">&times;</button>
            </div>
            <form id="bundleEditForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group-modal">
                        <label class="form-label-modal">Nama Paket *</label>
                        <input type="text" name="name" id="bundle_edit_name" class="form-input-modal" required>
                    </div>

                    <div class="form-row-grid-2">
                        <div class="form-group-modal">
                            <label class="form-label-modal">Kode Paket</label>
                            <input type="text" id="bundle_edit_code" class="form-input-modal" readonly style="background:#f1f5f9; color:#64748b;">
                        </div>
                        <div class="form-group-modal">
                            <label class="form-label-modal">Harga Sewa / Hari (Rp) *</label>
                            <input type="number" name="price" id="bundle_edit_price" class="form-input-modal" min="0" required>
                        </div>
                    </div>

                    <div class="form-group-modal">
                        <label class="form-label-modal">URL Gambar Paket</label>
                        <input type="text" name="image" id="bundle_edit_image" class="form-input-modal" placeholder="Kosongkan untuk mempertahankan gambar lama">
                    </div>

                    <div class="form-group-modal">
                        <label class="form-label-modal">Deskripsi Paket</label>
                        <textarea name="description" id="bundle_edit_description" rows="2" class="form-textarea-modal"></textarea>
                    </div>

                    <div class="form-group-modal" style="flex-direction: row; align-items: center; gap: 8px;">
                        <input type="checkbox" name="is_active" id="bundle_edit_is_active" value="1">
                        <label for="bundle_edit_is_active" class="form-label-modal" style="margin-bottom: 0; cursor: pointer;">Status Aktif</label>
                    </div>

                    <div class="form-group-modal">
                        <label class="form-label-modal">Barang-Barang dalam Paket (centang &amp; tentukan jumlah)</label>
                        <div style="max-height: 220px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px;">
                            @foreach ($productOptions as $opt)
                                <div class="bundle-member-row" style="display:flex; align-items:center; gap:10px; padding:6px 4px; border-bottom:1px solid #f1f5f9;">
                                    <input type="checkbox"
                                           class="bundle-member-check"
                                           name="member_ids[]"
                                           value="{{ $opt->id }}"
                                           data-bundle-product-id="{{ $opt->id }}"
                                           style="width:16px; height:16px; cursor:pointer;">
                                    <div style="flex:1; font-size:13px; color:#334155;">
                                        <strong>{{ $opt->name }}</strong>
                                        <span style="color:#94a3b8; font-size:11px;"> · {{ $opt->sku }}</span>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:6px;">
                                        <label style="font-size:11px; color:#64748b;">Qty</label>
                                        <input type="number"
                                               class="bundle-member-qty"
                                               name="quantities[{{ $opt->id }}]"
                                               min="1"
                                               value="1"
                                               disabled
                                               style="width:70px; padding:4px 6px; border:1px solid #d1d5db; border-radius:6px; font-size:13px;">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" onclick="closeBundleEditModal()">Batal</button>
                    <button type="submit" class="btn-modal-submit">Perbarui Paket Sewa</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── Modal Tambah Paket Sewa ─── -->
    <div id="bundleAddModal" class="modal-overlay">
        <div class="modal-card" style="width: 640px; max-width: 95vw;">
            <div class="modal-header">
                <h3 class="modal-title">Tambah Paket Sewa Baru</h3>
                <button type="button" class="btn-close-modal" onclick="closeBundleAddModal()">&times;</button>
            </div>
            <form id="bundleAddForm" method="POST" action="{{ route('admin.alat.bundle.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group-modal">
                        <label class="form-label-modal">Nama Paket *</label>
                        <input type="text" name="name" class="form-input-modal" placeholder="cth: Paket Camping Keluarga" required>
                    </div>

                    <div class="form-group-modal">
                        <label class="form-label-modal">Harga Sewa / Hari (Rp) *</label>
                        <input type="number" name="price" class="form-input-modal" min="0" required>
                    </div>

                    <div class="form-group-modal">
                        <label class="form-label-modal">URL Gambar Paket</label>
                        <input type="text" name="image" class="form-input-modal" placeholder="Kosongkan untuk menggunakan gambar default">
                    </div>

                    <div class="form-group-modal">
                        <label class="form-label-modal">Deskripsi Paket</label>
                        <textarea name="description" rows="2" class="form-textarea-modal"></textarea>
                    </div>

                    <div class="form-group-modal" style="flex-direction: row; align-items: center; gap: 8px;">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <label class="form-label-modal" style="margin-bottom: 0; cursor: pointer;">Status Aktif</label>
                    </div>

                    <div class="form-group-modal">
                        <label class="form-label-modal">Barang-Barang dalam Paket (centang &amp; tentukan jumlah)</label>
                        <div style="max-height: 220px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px;">
                            @foreach ($productOptions as $opt)
                                <div class="bundle-member-row" style="display:flex; align-items:center; gap:10px; padding:6px 4px; border-bottom:1px solid #f1f5f9;">
                                    <input type="checkbox"
                                           class="bundle-member-check"
                                           name="member_ids[]"
                                           value="{{ $opt->id }}"
                                           data-bundle-product-id="{{ $opt->id }}"
                                           style="width:16px; height:16px; cursor:pointer;">
                                    <div style="flex:1; font-size:13px; color:#334155;">
                                        <strong>{{ $opt->name }}</strong>
                                        <span style="color:#94a3b8; font-size:11px;"> · {{ $opt->sku }}</span>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:6px;">
                                        <label style="font-size:11px; color:#64748b;">Qty</label>
                                        <input type="number"
                                               class="bundle-member-qty"
                                               name="quantities[{{ $opt->id }}]"
                                               min="1"
                                               value="1"
                                               disabled
                                               style="width:70px; padding:4px 6px; border:1px solid #d1d5db; border-radius:6px; font-size:13px;">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" onclick="closeBundleAddModal()">Batal</button>
                    <button type="submit" class="btn-modal-submit">Simpan Paket Sewa</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── Modal Kelola Kategori ─── -->
    <div id="categoryModal" class="modal-overlay">
        <div class="modal-card" style="width: 560px;">
            <div class="modal-header">
                <h3 class="modal-title">Kelola Kategori Produk</h3>
                <button type="button" class="btn-close-modal" onclick="closeCategoryModal()">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.alat.category.store') }}" class="modal-body" style="display:flex; gap:10px; align-items:center;">
                @csrf
                <input type="text" name="name" class="form-input-modal" placeholder="Nama kategori baru, mis. Pakaian/Apparel" required style="flex:1; min-width:0;">
                <button type="submit" class="btn-modal-submit">Tambah</button>
            </form>
            <div class="modal-body" style="max-height: 360px; overflow-y:auto; display:flex; flex-direction:column; gap:8px;">
                @forelse ($categories as $cat)
                    @php $used = $cat->products_count ?? 0; @endphp
                    <div style="display:flex; align-items:center; gap:10px; padding:10px 12px; border:1px solid #e2e8f0; border-radius:8px;">
                        <span style="font-weight:700; color:#1e293b; flex:1;">{{ $cat->name }}</span>
                        <span class="category-badge-pill">{{ $used }} alat</span>
                        <form method="POST" action="{{ route('admin.alat.category.update', $cat->id) }}" style="display:flex; gap:6px; align-items:center;">
                            @csrf
                            @method('PUT')
                            <input type="text" name="name" value="{{ $cat->name }}" class="form-input-modal" required style="width:150px; padding:6px 8px;">
                            <button type="submit" class="btn-modal-submit" style="padding:6px 10px; background-color:#2563eb;">&check;</button>
                        </form>
                        <form method="POST" action="{{ route('admin.alat.category.destroy', $cat->id) }}" onsubmit="return confirm('Hapus kategori {{ $cat->name }}?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-modal-submit" style="padding:6px 10px; background-color:#dc2626;">&times;</button>
                        </form>
                    </div>
                @empty
                    <p style="color:#64748b; font-size:14px; text-align:center; padding:20px;">Belum ada kategori.</p>
                @endforelse
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closeCategoryModal()">Tutup</button>
            </div>
        </div>
    </div>

    <!-- ─── Modal Konfirmasi Hapus ─── -->
    <div id="deleteModal" class="modal-overlay">
        <div class="modal-card" style="width: 440px;">
            <div class="modal-header">
                <h3 class="modal-title" style="color: #dc2626;">Konfirmasi Hapus Alat</h3>
                <button type="button" class="btn-close-modal" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p style="font-size: 14px; color: #374151; line-height: 1.5;">
                    Apakah Anda yakin ingin menghapus <span id="delete_item_type" style="font-weight:700;">alat</span> <strong id="delete_item_name"></strong> dari katalog inventaris? Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>
            <div class="modal-footer">
                <form id="deleteForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn-modal-cancel" onclick="closeDeleteModal()">Batal</button>
                    <button type="submit" class="btn-modal-submit" style="background-color: #dc2626;">Ya, Hapus Alat</button>
                </form>
                <form id="deleteBundleForm" method="POST" action="" style="display: none;">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn-modal-cancel" onclick="closeDeleteModal()">Batal</button>
                    <button type="submit" class="btn-modal-submit" style="background-color: #dc2626;">Ya, Hapus Paket</button>
                </form>
            </div>
        </div>
    </div>

    <!-- ─── Scripts ─── -->
    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }

        function normImageUrl(v) {
            v = (v || '').trim();
            if (/^[A-Za-z]:[\\\/]/.test(v) || v.indexOf('\\') !== -1) {
                return { ok: false, msg: 'Ini path lokal (C:\\...), bukan URL. Isi URL gambar langsung atau gunakan /images/nama-gambar.png' };
            }
            if (!v) return { ok: false, msg: '' };
            return { ok: true, url: v };
        }

        function applyPreview(imgId, hintId, value) {
            var img = document.getElementById(imgId);
            var hint = document.getElementById(hintId);
            var r = normImageUrl(value);
            if (!r.ok) {
                if (img) img.style.display = 'none';
                if (hint) { hint.style.display = r.msg ? 'block' : 'none'; hint.style.color = '#dc2626'; hint.textContent = r.msg; }
                return;
            }
            if (!value) {
                if (img) img.style.display = 'none';
                if (hint) hint.style.display = 'none';
                return;
            }
            img.onerror = function() {
                this.style.display = 'none';
                if (hint) { hint.style.display = 'block'; hint.style.color = '#dc2626'; hint.textContent = 'Gambar tidak dapat dimuat dari URL ini. Pastikan link menunjuk langsung ke file gambar.'; }
            };
            img.onload = function() {
                this.style.display = 'block';
                if (hint) { hint.style.display = 'block'; hint.style.color = '#166534'; hint.textContent = 'Gambar siap digunakan.'; }
            };
            img.src = r.url;
        }

        function previewAddImage(v) { applyPreview('add_main_preview', 'add_main_hint', v); }
        function previewEditImage(v) { applyPreview('edit_main_preview', 'edit_main_hint', v); }

        function closeAddModal() {
            document.getElementById('addModal').classList.remove('active');
        }

        function openEditModal(item) {
            document.getElementById('editForm').action = '/admin/alat/' + item.id;
            document.getElementById('edit_name').value = item.name || '';
            document.getElementById('edit_sku').value = item.sku || '';
            document.getElementById('edit_category_id').value = item.category_id || '';
            document.getElementById('edit_price_per_day').value = item.price_per_day || '';
            document.getElementById('edit_subtitle').value = item.subtitle || '';
            document.getElementById('edit_stock_total').value = item.stock_total || 0;
            document.getElementById('edit_stock_available').value = item.stock_available || 0;

            // Berat, Kapasitas, Kondisi, Grade
            var itemSpecs = (typeof item.specs === 'object' && item.specs !== null) ? item.specs : {};
            document.getElementById('edit_weight').value = item.weight || itemSpecs.BERAT || itemSpecs.berat || '';
            document.getElementById('edit_capacity').value = item.capacity || itemSpecs.KAPASITAS || itemSpecs.kapasitas || '';

            var cond = item.condition || itemSpecs.KONDISI || itemSpecs.kondisi || 'Excellent';
            var condSelect = document.getElementById('edit_condition');
            condSelect.value = cond;
            if (!condSelect.value) {
                var optC = new Option(cond, cond, true, true);
                condSelect.add(optC);
            }

            var grd = item.grade || itemSpecs.GRADE || itemSpecs.grade || 'PRO-GRADE';
            var grdSelect = document.getElementById('edit_grade');
            grdSelect.value = grd;
            if (!grdSelect.value) {
                var optG = new Option(grd, grd, true, true);
                grdSelect.add(optG);
            }

            document.getElementById('edit_main_image').value = item.main_image || '';
            previewEditImage(document.getElementById('edit_main_image').value);
            document.getElementById('edit_description').value = item.description || '';
            document.getElementById('edit_is_active').checked = item.is_active ? true : false;

            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        function openBundleEditModal(item) {
            document.getElementById('bundleEditForm').action = '/admin/alat/bundle/' + item.id;
            document.getElementById('bundle_edit_name').value = item.name || '';
            document.getElementById('bundle_edit_code').value = item.kode || ('PKT-' + item.id);
            document.getElementById('bundle_edit_price').value = item.price || 0;
            document.getElementById('bundle_edit_image').value = item.image || '';
            document.getElementById('bundle_edit_description').value = item.description || '';
            document.getElementById('bundle_edit_is_active').checked = item.is_active ? true : false;

            // Reset semua checkbox & qty terlebih dahulu.
            var checks = document.querySelectorAll('.bundle-member-check');
            for (var i = 0; i < checks.length; i++) {
                checks[i].checked = false;
                var qty = checks[i].closest('.bundle-member-row').querySelector('.bundle-member-qty');
                qty.value = 1;
                qty.disabled = true;
            }

            // Centang & isi qty sesuai anggota paket saat ini.
            var members = (item.members && item.members.length) ? item.members : [];
            for (var m = 0; m < members.length; m++) {
                var pid = String(members[m].id);
                var row = document.querySelector('.bundle-member-check[value="' + pid + '"]');
                if (row) {
                    row.checked = true;
                    var qBox = row.closest('.bundle-member-row').querySelector('.bundle-member-qty');
                    qBox.value = members[m].quantity || 1;
                    qBox.disabled = false;
                }
            }

            document.getElementById('bundleEditModal').classList.add('active');
        }

        function closeBundleEditModal() {
            document.getElementById('bundleEditModal').classList.remove('active');
        }

        function openBundleAddModal() {
            // Reset form & semua checkbox/qty ke nilai awal.
            document.getElementById('bundleAddForm').reset();
            var checks = document.querySelectorAll('#bundleAddModal .bundle-member-check');
            for (var i = 0; i < checks.length; i++) {
                checks[i].checked = false;
                var qty = checks[i].closest('.bundle-member-row').querySelector('.bundle-member-qty');
                qty.value = 1;
                qty.disabled = true;
            }
            document.getElementById('bundleAddModal').classList.add('active');
        }

        function closeBundleAddModal() {
            document.getElementById('bundleAddModal').classList.remove('active');
        }

        function openCategoryModal() {
            document.getElementById('categoryModal').classList.add('active');
        }

        function closeCategoryModal() {
            document.getElementById('categoryModal').classList.remove('active');
        }

        // Aktifkan/nonaktifkan input qty mengikuti centang barang anggota.
        document.addEventListener('change', function (e) {
            if (e.target && e.target.classList && e.target.classList.contains('bundle-member-check')) {
                var qty = e.target.closest('.bundle-member-row').querySelector('.bundle-member-qty');
                if (qty) {
                    qty.disabled = !e.target.checked;
                    if (e.target.checked && (!qty.value || parseInt(qty.value, 10) < 1)) {
                        qty.value = 1;
                    }
                }
            }
        });

        function confirmDelete(id, name) {
            document.getElementById('deleteForm').action = '/admin/alat/' + id;
            document.getElementById('deleteBundleForm').style.display = 'none';
            document.getElementById('deleteForm').style.display = '';
            document.getElementById('delete_item_type').textContent = 'alat';
            document.getElementById('delete_item_name').textContent = name;
            document.getElementById('deleteModal').classList.add('active');
        }

        function confirmDeleteBundle(id, name) {
            document.getElementById('deleteBundleForm').action = '/admin/alat/bundle/' + id;
            document.getElementById('deleteForm').style.display = 'none';
            document.getElementById('deleteBundleForm').style.display = '';
            document.getElementById('delete_item_type').textContent = 'paket sewa';
            document.getElementById('delete_item_name').textContent = name;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }

        // Close modal on escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeAddModal();
                closeEditModal();
                closeBundleEditModal();
                closeBundleAddModal();
                closeCategoryModal();
                closeDeleteModal();
            }
        });
    </script>

</body>
</html>
