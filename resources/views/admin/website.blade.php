<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}">
    <title>Manajemen Website - Summit Station Admin</title>
    <link rel="stylesheet" href="{{ asset('css/summit-admin.css') . '?v=' . time() }}">
    <link rel="stylesheet" href="{{ asset('css/summit-footer.css') . '?v=' . filemtime(public_path('css/summit-footer.css')) }}">
</head>
<body>

    <!-- Blue Top Accent Line -->
    <div class="top-banner-line"></div>

    <div class="admin-layout">
        <!-- ─── 1. Sidebar Admin ─── -->
        @include('admin.partials.sidebar', ['activeMenu' => 'website'])

        <!-- ─── 2. Main Content ─── -->
        <div class="admin-main">
            <!-- Header -->
            @include('admin.partials.header', [
                'adminPageTitle' => 'Website',
                'adminPageSubtitle' => 'Konten & ulasan situs',
            ])

            <!-- Main Body -->
            <main class="admin-content">
                @if (session('success'))
                    <div style="background: #dcfce7; border: 1px solid #86efac; color: #15803d; padding: 14px 20px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; font-size: 13px;">
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Page Header -->
                <div class="user-page-header-row">
                    <div class="user-page-header-left">
                        <div class="user-operations-badge">CMS &amp; TAMPILAN TOKO</div>
                        <h1 class="user-main-heading">Manajemen Website</h1>
                        <p class="user-main-subtitle">
                            Kelola informasi publik, spotlight toko, jam operasional, dan preview etalase website Summit Station.
                        </p>
                    </div>

                    <div class="user-page-actions">
                        <a href="{{ url('/') }}" target="_blank" class="btn-add-user">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                <polyline points="15 3 21 3 21 9"></polyline>
                                <line x1="10" y1="14" x2="21" y2="3"></line>
                            </svg>
                            <span>Buka Live Website</span>
                        </a>
                    </div>
                </div>

                <!-- 3 Storefront Status Cards -->
                <div class="user-stats-grid">
                    <div class="user-stat-card">
                        <div class="user-stat-label">PRODUK AKTIF DI WEBSITE</div>
                        <div class="user-stat-val-row">
                            <span class="user-stat-number">{{ $settings['featured_products_count'] }}</span>
                            <span class="user-stat-growth">Tayang</span>
                        </div>
                        <div class="user-stat-subtext">Tampil di halaman katalog publik</div>
                    </div>

                    <div class="user-stat-card">
                        <div class="user-stat-label">KATEGORI PERALATAN</div>
                        <div class="user-stat-val-row">
                            <span class="user-stat-number">{{ $settings['total_categories_count'] }}</span>
                        </div>
                        <div class="user-stat-subtext">Kategori navigasi aktif</div>
                    </div>

                    <div class="user-stat-card">
                        <div class="user-stat-label">PAKET SEWA (BUNDLE)</div>
                        <div class="user-stat-val-row">
                            <span class="user-stat-number">{{ $settings['active_bundles_count'] }}</span>
                            <span class="user-stat-dot active"></span>
                        </div>
                        <div class="user-stat-subtext">Penawaran paket pendakian</div>
                    </div>

                    <div class="user-stat-card">
                        <div class="user-stat-label">STATUS SERVER STOREFRONT</div>
                        <div class="user-stat-val-row">
                            <span class="user-stat-number" style="font-size: 20px; color: #16a34a;">ONLINE</span>
                        </div>
                        <div class="user-stat-subtext">SSL 256-bit Enkripsi Aktif</div>
                    </div>
                </div>

                <!-- Form Configuration Settings -->
                <div class="user-table-card" style="padding: 24px 28px; margin-bottom: 24px;">
                    <div style="font-size: 16px; font-weight: 800; color: #111827; margin-bottom: 6px;">
                        Informasi Kontak &amp; Lokasi Toko
                    </div>
                    <p style="font-size: 13px; color: #6b7280; margin-bottom: 20px;">
                        Data ini ditampilkan pada halaman Store Location, Contact Admin, dan footer publik.
                    </p>

                    <form method="POST" action="{{ route('admin.website.update') }}">
                        @csrf
                        <div class="user-form-grid" style="margin-bottom: 20px;">
                            <div class="user-form-group">
                                <label class="user-form-label">Nama Brand Website</label>
                                <input type="text" name="site_name" class="user-form-input" value="{{ $settings['site_name'] }}">
                            </div>

                            <div class="user-form-group">
                                <label class="user-form-label">Hotline WhatsApp / Telepon</label>
                                <input type="text" name="hotline" class="user-form-input" value="{{ $settings['hotline'] }}">
                            </div>

                            <div class="user-form-group">
                                <label class="user-form-label">Email Support</label>
                                <input type="email" name="email" class="user-form-input" value="{{ $settings['email'] }}">
                            </div>

                            <div class="user-form-group">
                                <label class="user-form-label">Jam Operasional</label>
                                <input type="text" name="operating_hours" class="user-form-input" value="{{ $settings['operating_hours'] }}">
                            </div>

                            <div class="user-form-group full-width">
                                <label class="user-form-label">Alamat Basecamp &amp; Store Location</label>
                                <input type="text" name="address" class="user-form-input" value="{{ $settings['address'] }}">
                            </div>

                            <div class="user-form-group full-width">
                                <label class="user-form-label">Hero Title Landing Page</label>
                                <input type="text" name="hero_title" class="user-form-input" value="{{ $settings['hero_title'] }}">
                            </div>

                            <div class="user-form-group full-width">
                                <label class="user-form-label">Hero Subtitle</label>
                                <textarea name="hero_subtitle" class="user-form-input" rows="3">{{ $settings['hero_subtitle'] }}</textarea>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: flex-end;">
                            <button type="submit" class="btn-submit-modal">Simpan Pengaturan Website</button>
                        </div>
                    </form>
                </div>

                <!-- ─── 2. Customer Reviews Management & Moderation ─── -->
                <div class="user-table-card" style="margin-top: 28px;">
                    <div class="user-table-toolbar">
                        <div>
                            <div class="user-table-title">Ulasan &amp; Rating Pelanggan</div>
                            <div class="user-table-subtitle">
                                Moderasi testimoni yang masuk dari penyewaan selesai untuk ditampilkan pada halaman Beranda (Home).
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="padding: 8px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 13px; font-weight: 700; color: #1e293b;">
                                ★ {{ number_format($averageRating ?? 0, 1) }} / 5.0 ({{ $totalReviewsCount ?? 0 }} Total Ulasan)
                            </div>
                        </div>
                    </div>

                    <div class="user-table-wrapper">
                        <table class="user-custom-table">
                            <thead>
                                <tr>
                                    <th>Pelanggan</th>
                                    <th>Alat / Order</th>
                                    <th>Rating</th>
                                    <th>Ulasan / Komentar</th>
                                    <th>Tanggal</th>
                                    <th>Status Beranda</th>
                                    <th style="text-align: right;">Aksi Moderasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (isset($reviews) && count($reviews) > 0)
                                    @foreach ($reviews as $rev)
                                        <tr>
                                            <td>
                                                <div class="user-info-cell">
                                                    <div class="user-avatar-circle">
                                                        {{ $rev->user ? $rev->user->initials : 'U' }}
                                                    </div>
                                                    <div>
                                                        <div class="user-name-text">{{ $rev->user ? $rev->user->name : 'Explorer' }}</div>
                                                        <div class="user-email-text">{{ $rev->user ? $rev->user->email : '-' }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <strong style="color: #0f172a; font-size: 13px;">{{ $rev->product ? $rev->product->name : ($rev->bundle ? $rev->bundle->name : 'Equipment') }}</strong>
                                                <div style="font-size: 11px; color: #64748b;">#{{ $rev->order ? $rev->order->code : '-' }}</div>
                                            </td>
                                            <td>
                                                <div style="color: #eab308; font-size: 14px; font-weight: 700;">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        @if ($i <= $rev->rating)
                                                            ★
                                                        @else
                                                            <span style="color: #cbd5e1;">★</span>
                                                        @endif
                                                    @endfor
                                                    <span style="color: #475569; font-size: 12px; margin-left: 4px;">({{ $rev->rating }}/5)</span>
                                                </div>
                                            </td>
                                            <td style="max-width: 320px;">
                                                <p style="font-size: 13px; color: #334155; line-height: 1.4; margin: 0;">
                                                    "{{ $rev->comment }}"
                                                </p>
                                            </td>
                                            <td style="white-space: nowrap; font-size: 12px; color: #64748b;">
                                                {{ $rev->created_at ? $rev->created_at->format('d M Y H:i') : '-' }}
                                            </td>
                                            <td>
                                                @if ($rev->is_visible)
                                                    <span style="display: inline-block; padding: 4px 10px; background: #dcfce7; color: #15803d; border-radius: 6px; font-size: 11px; font-weight: 700;">
                                                        Ditampilkan
                                                    </span>
                                                @else
                                                    <span style="display: inline-block; padding: 4px 10px; background: #fee2e2; color: #991b1b; border-radius: 6px; font-size: 11px; font-weight: 700;">
                                                        Disembunyikan
                                                    </span>
                                                @endif
                                            </td>
                                            <td style="text-align: right; white-space: nowrap;">
                                                <div style="display: inline-flex; gap: 6px;">
                                                    <form method="POST" action="{{ route('admin.reviews.toggle', $rev->id) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn-action-edit" style="font-size: 12px; padding: 6px 10px; cursor: pointer;" title="{{ $rev->is_visible ? 'Sembunyikan dari Home' : 'Tampilkan di Home' }}">
                                                            {{ $rev->is_visible ? 'Sembunyikan' : 'Tampilkan' }}
                                                        </button>
                                                    </form>

                                                    <form method="POST" action="{{ route('admin.reviews.destroy', $rev->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus ulasan ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn-action-delete" style="font-size: 12px; padding: 6px 10px; cursor: pointer;" title="Hapus Ulasan">
                                                            Hapus
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 36px; color: #64748b;">
                                            Belum ada ulasan dari pelanggan.
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>

            <!-- ─── Footer ─── -->
            @include('partials.footer', ['footerContext' => 'admin'])
        </div>
    </div>

</body>
</html>
