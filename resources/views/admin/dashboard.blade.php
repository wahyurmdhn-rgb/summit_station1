<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}">
    <title>Dashboard Admin - Summit Station</title>
    <link rel="stylesheet" href="{{ asset('css/summit-admin.css') . '?v=' . time() }}">
    <link rel="stylesheet" href="{{ asset('css/summit-footer.css') . '?v=' . filemtime(public_path('css/summit-footer.css')) }}">
</head>
<body>

    <!-- Top Accent Line -->
    <div class="top-banner-line"></div>

    <div class="admin-layout">
        <!-- â”€â”€â”€ Sidebar â”€â”€â”€ -->
        @include('admin.partials.sidebar', ['activeMenu' => 'dashboard'])

        <!-- â”€â”€â”€ Main Content Wrapper â”€â”€â”€ -->
        <div class="admin-main">
            <!-- Top Header -->
            @include('admin.partials.header', [
                'adminPageTitle' => 'Dashboard',
                'adminPageSubtitle' => 'Ringkasan operasional Summit Station',
            ])

            <!-- Dashboard Content -->
            <main class="admin-content">
                <!-- â•â•â• 1. STATISTIK UTAMA â•â•â• -->
                <section class="dash-stats">
                    <!-- Total Pendapatan (Hero) -->
                    <div class="stat-hero">
                        <div class="stat-hero-body">
                            <span class="stat-hero-label">Total Pendapatan</span>
                            <div class="stat-hero-value">Rp {{ number_format($stats['total_pendapatan'], 0, ',', '.') }}</div>
                            <span class="stat-hero-caption">Akumulasi dari seluruh pembayaran yang telah disetujui.</span>
                        </div>
                        <div class="stat-hero-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                                <line x1="2" y1="10" x2="22" y2="10"></line>
                            </svg>
                        </div>
                    </div>

                    <!-- Kartu Metrik Utama -->
                    <div class="stats-grid">
                        <!-- Total Produk -->
                        <div class="stat-card">
                            <div class="stat-top">
                                <div class="stat-icon ic-green">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                    </svg>
                                </div>
                                <span class="stat-caption">{{ number_format($stats['total_produk'], 0, ',', '.') }} produk · {{ number_format($stats['total_paket'], 0, ',', '.') }} paket</span>
                            </div>
                            <div class="stat-card-body">
                                <div class="stat-card-label">Total Produk</div>
                                <div class="stat-card-value">{{ number_format($stats['total_produk'], 0, ',', '.') }}</div>
                            </div>
                        </div>

                        <!-- Total Pengguna -->
                        <div class="stat-card">
                            <div class="stat-top">
                                <div class="stat-icon ic-blue">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                </div>
                                <span class="stat-caption">Akun terdaftar</span>
                            </div>
                            <div class="stat-card-body">
                                <div class="stat-card-label">Total Pengguna</div>
                                <div class="stat-card-value">{{ number_format($stats['total_pengguna'], 0, ',', '.') }}</div>
                            </div>
                        </div>

                        <!-- Total Pemesanan -->
                        <div class="stat-card">
                            <div class="stat-top">
                                <div class="stat-icon ic-violet">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                    </svg>
                                </div>
                                <span class="stat-caption">Semua transaksi</span>
                            </div>
                            <div class="stat-card-body">
                                <div class="stat-card-label">Total Pemesanan</div>
                                <div class="stat-card-value">{{ number_format($stats['total_pemesanan'], 0, ',', '.') }}</div>
                            </div>
                        </div>

                        <!-- Produk Tersedia -->
                        <div class="stat-card">
                            <div class="stat-top">
                                <div class="stat-icon ic-green">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                </div>
                                <span class="stat-caption">Kapasitas {{ $stats['kapasitas'] }}%</span>
                            </div>
                            <div class="stat-card-body">
                                <div class="stat-card-label">Produk Tersedia</div>
                                <div class="stat-card-value">{{ number_format($stats['produk_tersedia'], 0, ',', '.') }}</div>
                                <div class="capacity-line">
                                    <div class="capacity-fill" style="width: {{ min(100, max(0, $stats['kapasitas'])) }}%;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Booking Aktif -->
                        <div class="stat-card">
                            <div class="stat-top">
                                <div class="stat-icon ic-amber">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="9" cy="21" r="1"></circle>
                                        <circle cx="20" cy="21" r="1"></circle>
                                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                    </svg>
                                </div>
                                <span class="stat-caption">Sedang berjalan</span>
                            </div>
                            <div class="stat-card-body">
                                <div class="stat-card-label">Booking Aktif</div>
                                <div class="stat-card-value">{{ number_format($stats['sedang_disewa'], 0, ',', '.') }}</div>
                            </div>
                        </div>

                        <!-- Menunggu Proses -->
                        <div class="stat-card">
                            <div class="stat-top">
                                <div class="stat-icon ic-slate">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="9"></circle>
                                        <polyline points="12 7 12 12 15.5 14.5"></polyline>
                                    </svg>
                                </div>
                                <span class="stat-caption">Menunggu verifikasi</span>
                            </div>
                            <div class="stat-card-body">
                                <div class="stat-card-label">Pesanan Diproses</div>
                                <div class="stat-card-value">{{ number_format($stats['menunggu_proses'], 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- â•â•â• 2. AKTIVITAS TERBARU â•â•â• -->
                <section class="dash-recent">
                    <!-- Booking Terbaru -->
                    <div class="recent-panel">
                        <div class="recent-panel-header">
                            <h3>Booking Terbaru</h3>
                            <a href="{{ route('admin.penyewaan') }}">Lihat semua</a>
                        </div>
                        <ul class="recent-list">
                            @forelse ($recentOrders as $order)
                                @php
                                    $orderBadge = match ($order['status']) {
                                        'active', 'paid' => ['active', 'Aktif'],
                                        'pending' => ['lowstock', 'Menunggu'],
                                        'completed' => ['success', 'Selesai'],
                                        'cancelled', 'rejected', 'declined' => ['danger', 'Dibatalkan'],
                                        default => ['inactive', ucfirst((string) $order['status'])],
                                    };
                                @endphp
                                <li class="recent-row">
                                    <div class="recent-avatar recent-avatar-order" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14 2 14 8 20 8"></polyline>
                                        </svg>
                                    </div>
                                    <div class="recent-info">
                                        <span class="recent-title">{{ $order['code'] }}</span>
                                        <span class="recent-sub">{{ $order['customer'] }} · Rp {{ number_format($order['total'], 0, ',', '.') }}</span>
                                    </div>
                                    <div class="recent-right">
                                        <span class="badge-status {{ $orderBadge[0] }}"><span class="status-dot"></span>{{ $orderBadge[1] }}</span>
                                        <span class="recent-time">{{ $order['created_at'] ? $order['created_at']->diffForHumans() : '' }}</span>
                                    </div>
                                </li>
                            @empty
                                <li class="recent-empty">Belum ada booking.</li>
                            @endforelse
                        </ul>
                    </div>

                    <!-- Pengguna Terbaru -->
                    <div class="recent-panel">
                        <div class="recent-panel-header">
                            <h3>Pengguna Terbaru</h3>
                            <a href="{{ route('admin.users') }}">Lihat semua</a>
                        </div>
                        <ul class="recent-list">
                            @forelse ($recentUsers as $user)
                                <li class="recent-row">
                                    <div class="recent-avatar recent-avatar-user">{{ $user['initial'] }}</div>
                                    <div class="recent-info">
                                        <span class="recent-title">{{ $user['name'] }}</span>
                                        <span class="recent-sub">{{ $user['email'] }}</span>
                                    </div>
                                    <div class="recent-right">
                                        <span class="recent-time">{{ $user['created_at'] ? $user['created_at']->diffForHumans() : '' }}</span>
                                    </div>
                                </li>
                            @empty
                                <li class="recent-empty">Belum ada pengguna terdaftar.</li>
                            @endforelse
                        </ul>
                    </div>
                </section>

                <!-- â•â•â• 3. MONITORING PERALATAN POPULER â•â•â• -->
                <section class="section-popular">
                    <div class="section-header">
                        <div>
                            <h2 class="section-title">Monitoring Peralatan Populer</h2>
                            <p class="section-subtitle">Status ketersediaan stok peralatan yang paling sering disewa.</p>
                        </div>
                        <div class="section-actions">
                            <a href="{{ route('admin.laporan.export') }}" class="btn-csv" style="text-decoration: none;">Unduh CSV</a>
                            <a href="{{ route('admin.alat') }}" class="btn-kelola" style="text-decoration: none; display: inline-flex; align-items: center;">Kelola Produk</a>
                        </div>
                    </div>

                    <div class="gear-grid">
                        @foreach ($popularGear as $gear)
                            <div class="gear-card">
                                <div class="gear-image-box">
                                    <img src="{{ $gear['image'] }}" alt="{{ $gear['name'] }}">
                                    <span class="gear-badge badge-{{ $gear['category_color'] }}">{{ $gear['category'] }}</span>
                                </div>
                                <div class="gear-body">
                                    <h3 class="gear-title">{{ $gear['name'] }}</h3>
                                    <div class="gear-meta-row">
                                        <span>{{ $gear['stock'] }} unit</span>
                                        <div class="gear-rating">
                                            <span>{{ $gear['rating'] }}</span>
                                            <span>&starf;</span>
                                        </div>
                                    </div>
                                    <div class="progress-track">
                                        <div class="progress-fill {{ $gear['progress_color'] }}" style="width: {{ $gear['progress'] }}%;"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </main>

            <!-- Footer -->
            @include('partials.footer', ['footerContext' => 'admin'])
        </div>
    </div>

</body>
</html>