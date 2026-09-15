<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}">
    <title>Laporan Operasional & Keuangan - Summit Station Admin</title>
    <link rel="stylesheet" href="{{ asset('css/summit-admin.css') . '?v=' . time() }}">
    <link rel="stylesheet" href="{{ asset('css/summit-footer.css') . '?v=' . filemtime(public_path('css/summit-footer.css')) }}">
</head>
<body>

    <!-- Blue Top Accent Line -->
    <div class="top-banner-line"></div>

    <div class="admin-layout">
        <!-- ─── 1. Sidebar Admin ─── -->
        @include('admin.partials.sidebar', ['activeMenu' => 'laporan'])

        <!-- ─── 2. Main Content ─── -->
        <div class="admin-main">
            <!-- Header -->
            @include('admin.partials.header', [
                'adminPageTitle' => 'Laporan',
                'adminPageSubtitle' => 'Operasional & keuangan',
            ])

            <!-- Main Body -->
            <main class="admin-content">
                <!-- Page Header -->
                <div class="user-page-header-row">
                    <div class="user-page-header-left">
                        <div class="user-operations-badge">ANALITIK &amp; AUDIT</div>
                        <h1 class="user-main-heading">Laporan Operasional & Keuangan</h1>
                        <p class="user-main-subtitle">
                            Ringkasan performa finansial, volume penyewaan, utilisasi peralatan, dan catatan audit logistik Summit Station.
                        </p>
                    </div>

                    <!-- Actions & Filter -->
                    <div class="user-page-actions">
                        <form method="GET" action="{{ route('admin.laporan') }}" class="filter-dropdown-form">
                            <div class="filter-pill-dropdown">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <select name="period" class="filter-select-pill" onchange="this.form.submit()">
                                    <option value="all" {{ $period === 'all' ? 'selected' : '' }}>Semua Periode</option>
                                    <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Hari Ini</option>
                                    <option value="this_week" {{ $period === 'this_week' ? 'selected' : '' }}>Minggu Ini</option>
                                    <option value="this_month" {{ $period === 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                                </select>
                            </div>
                        </form>

                        <a href="{{ route('admin.laporan.export') }}" class="btn-export-csv">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            <span>Ekspor Laporan CSV</span>
                        </a>
                    </div>
                </div>

                <!-- 4 KPI Summary Cards -->
                <div class="user-stats-grid">
                    <!-- Total Revenue -->
                    <div class="user-stat-card">
                        <div class="user-stat-label">TOTAL PENDAPATAN</div>
                        <div class="user-stat-val-row">
                            <span class="user-stat-number" style="font-size: 22px; color: #185d31;">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</span>
                        </div>
                        <div class="user-stat-subtext">Akumulasi transaksi sukses</div>
                    </div>

                    <!-- Total Orders -->
                    <div class="user-stat-card">
                        <div class="user-stat-label">TOTAL TRANSAKSI</div>
                        <div class="user-stat-val-row">
                            <span class="user-stat-number">{{ number_format($totalOrdersCount) }}</span>
                            <span class="user-stat-growth">{{ $completedOrdersCount }} Selesai</span>
                        </div>
                        <div class="user-stat-subtext">{{ $activeOrdersCount }} Aktif • {{ $pendingOrdersCount }} Menunggu</div>
                    </div>

                    <!-- Total Items Rented -->
                    <div class="user-stat-card">
                        <div class="user-stat-label">UNIT ALAT DISEWA</div>
                        <div class="user-stat-val-row">
                            <span class="user-stat-number">{{ number_format((int) $totalItemsRented) }}</span>
                            <span class="user-stat-dot active"></span>
                        </div>
                        <div class="user-stat-subtext">Peralatan berstatus deployed</div>
                    </div>

                    <!-- Damaged & Fines -->
                    <div class="user-stat-card">
                        <div class="user-stat-label">AUDIT KERUSAKAN</div>
                        <div class="user-stat-val-row">
                            <span class="user-stat-number red">{{ number_format($damagedReturnsCount) }}</span>
                            <span style="font-size: 12px; color: #dc2626; font-weight: 700;">Kasus</span>
                        </div>
                        <div class="user-stat-subtext">Total Denda: Rp {{ number_format($damageFinesTotal, 0, ',', '.') }}</div>
                    </div>
                </div>

                <!-- Grid Layout: Top Rented Gear + Category Distribution -->
                <div class="report-grid-charts">
                    <!-- Top Rented Equipment -->
                    <div class="user-table-card">
                        <div style="padding: 16px 20px; border-bottom: 1px solid #f1f3f1; font-weight: 800; font-size: 14px; color: #111827; display: flex; align-items: center; justify-content: space-between;">
                            <span>PERALATAN PALING SERING DISEWA</span>
                            <span style="font-size: 11px; color: #6b7280; text-transform: uppercase;">TERBAIK</span>
                        </div>
                        <div class="user-table-scroll-container">
                            <table class="user-custom-table">
                                <thead>
                                    <tr>
                                        <th>ALAT</th>
                                        <th style="text-align: center;">TOTAL DISEWA</th>
                                        <th style="text-align: right;">TOTAL ESTIMASI OMZET</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($topProducts as $item)
                                        <tr class="user-table-row">
                                            <td>
                                                <div class="user-profile-cell">
                                                    <img src="{{ $item->image }}" alt="{{ $item->name }}" class="user-avatar-img" style="border-radius: 8px;">
                                                    <span style="font-weight: 700; color: #111827;">{{ $item->name }}</span>
                                                </div>
                                            </td>
                                            <td style="text-align: center; font-weight: 800; color: #185d31;">
                                                {{ $item->total_rented }} Unit
                                            </td>
                                            <td style="text-align: right; font-weight: 700; color: #374151;">
                                                Rp {{ number_format($item->total_sales, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" style="text-align: center; padding: 24px; color: #9ca3af;">Belum ada data penyewaan.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Category Utilization -->
                    <div class="user-table-card">
                        <div style="padding: 16px 20px; border-bottom: 1px solid #f1f3f1; font-weight: 800; font-size: 14px; color: #111827;">
                            DISTRIBUSI KATEGORI
                        </div>
                        <div style="padding: 20px;">
                            @foreach ($categories as $cat)
                                <div style="margin-bottom: 16px;">
                                    <div style="display: flex; justify-content: space-between; font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 4px;">
                                        <span>{{ $cat['name'] }}</span>
                                        <span>{{ $cat['products_count'] }} Produk</span>
                                    </div>
                                    <div class="user-stat-progress-track">
                                        <div class="user-stat-progress-fill" style="width: {{ min(100, max(20, $cat['products_count'] * 15)) }}%;"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions Audit Log -->
                <div class="user-table-card">
                    <div style="padding: 16px 20px; border-bottom: 1px solid #f1f3f1; font-weight: 800; font-size: 14px; color: #111827; display: flex; align-items: center; justify-content: space-between;">
                        <span>LOG TRANSAKSI TERAKHIR</span>
                        <a href="{{ route('admin.pembayaran') }}" style="font-size: 12px; color: #185d31; font-weight: 700; text-decoration: none;">Kelola Pembayaran &rarr;</a>
                    </div>
                    <div class="user-table-scroll-container">
                        <table class="user-custom-table">
                            <thead>
                                <tr>
                                    <th>ORDER ID</th>
                                    <th>CUSTOMER</th>
                                    <th>TANGGAL SEWA</th>
                                    <th>STATUS PESANAN</th>
                                    <th>TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentOrders as $order)
                                    <tr class="user-table-row">
                                        <td style="font-family: monospace; font-weight: 800; color: #185d31;">#{{ $order->code }}</td>
                                        <td>
                                            <div style="font-weight: 700; color: #111827;">{{ $order->user?->name ?? 'Guest' }}</div>
                                            <div style="font-size: 11px; color: #6b7280;">{{ $order->user?->email }}</div>
                                        </td>
                                        <td style="color: #4b5563; font-size: 12px;">
                                            {{ $order->rent_start ? $order->rent_start->format('M d') : '-' }} - {{ $order->rent_end ? $order->rent_end->format('M d, Y') : '-' }}
                                        </td>
                                        <td>
                                            @if ($order->status === 'active')
                                                <span class="user-status-badge user-badge-active"><span class="badge-dot"></span> AKTIF</span>
                                            @elseif ($order->status === 'completed')
                                                <span class="user-status-badge user-badge-active"><span class="badge-dot"></span> SELESAI</span>
                                            @elseif ($order->status === 'pending')
                                                <span class="user-status-badge user-badge-pending"><span class="badge-dot"></span> MENUNGGU</span>
                                            @else
                                                <span class="user-status-badge user-badge-suspended"><span class="badge-dot"></span> {{ strtoupper($order->status) }}</span>
                                            @endif
                                        </td>
                                        <td style="font-weight: 800; color: #111827;">Rp {{ number_format($order->total, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
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
