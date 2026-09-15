<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}">
    <title>Penyewaan - Manajemen Inventaris Sewa - Summit Station</title>
    <link rel="stylesheet" href="{{ asset('css/summit-admin.css') . '?v=' . time() }}">
    <link rel="stylesheet" href="{{ asset('css/summit-footer.css') . '?v=' . filemtime(public_path('css/summit-footer.css')) }}">
</head>
<body>

    <!-- Blue Top Accent Line -->
    <div class="top-banner-line"></div>

    <div class="admin-layout">
        <!-- ─── Sidebar ─── -->
        @include('admin.partials.sidebar', ['activeMenu' => 'penyewaan'])

        <!-- ─── Main Content ─── -->
        <div class="admin-main">
            <!-- Top Header -->
            @include('admin.partials.header', [
                'adminPageTitle' => 'Penyewaan',
                'adminPageSubtitle' => 'Verifikasi & kelola transaksi sewa',
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

                <!-- ─── 4 Statistic Cards Row ─── -->
                <div class="stats-grid-penyewaan">
                    <!-- Card 1: ACTIVE RENTALS -->
                    <div class="stat-card-penyewaan">
                        <div class="stat-card-penyewaan-top">
                            <div class="stat-icon-penyewaan green">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                            </div>
                            <span class="badge-penyewaan badge-this-week">MINGGU INI</span>
                        </div>
                        <div>
                            <div class="stat-penyewaan-label">SEWA AKTIF</div>
                            <div class="stat-penyewaan-val">{{ $activeRentals }}</div>
                        </div>
                        <div class="stat-subtext-note green">
                            <span>↗</span>
                            <span>Jumlah sewa yang sedang berjalan</span>
                        </div>
                    </div>

                    <!-- Card 2: PENDING REQUESTS -->
                    <div class="stat-card-penyewaan">
                        <div class="stat-card-penyewaan-top">
                            <div class="stat-icon-penyewaan orange">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="12" y1="18" x2="12" y2="12"></line>
                                    <line x1="12" y1="9" x2="12.01" y2="9"></line>
                                </svg>
                            </div>
                            <span class="badge-penyewaan badge-urgent">SEGERA</span>
                        </div>
                        <div>
                            <div class="stat-penyewaan-label">PERMINTAAN MENUNGGU</div>
                            <div class="stat-penyewaan-val">{{ $pendingRequests < 10 ? '0' . $pendingRequests : $pendingRequests }}</div>
                        </div>
                        <div class="stat-subtext-note red">
                            <span style="font-weight: 800;">!</span>
                            <span>Perlu tindakan segera</span>
                        </div>
                    </div>

                    <!-- Card 3: EXPECTED RETURNS -->
                    <div class="stat-card-penyewaan">
                        <div class="stat-card-penyewaan-top">
                            <div class="stat-icon-penyewaan green">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                </svg>
                            </div>
                            <span class="badge-penyewaan badge-today">HARI INI</span>
                        </div>
                        <div>
                            <div class="stat-penyewaan-label">PERKIRAAN PENGEMBALIAN</div>
                            <div class="stat-penyewaan-val">{{ $expectedReturns }}</div>
                        </div>
                        <div class="stat-subtext-note muted">
                            <span>🕒</span>
                            <span>Perkiraan pengembalian sewa hari ini</span>
                        </div>
                    </div>

                    <!-- Card 4: REVENUE FORECAST -->
                    <div class="stat-card-penyewaan">
                        <div class="stat-card-penyewaan-top">
                            <div class="stat-icon-penyewaan green">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                                    <line x1="2" y1="10" x2="22" y2="10"></line>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <div class="stat-penyewaan-label">PROYEKSI PENDAPATAN</div>
                            <div class="stat-penyewaan-val">{{ $revenueForecast }}</div>
                        </div>
                        <div class="stat-subtext-note green">
                            <span>✔</span>
                            <span>Pembayaran terverifikasi</span>
                        </div>
                    </div>
                </div>

                <!-- ─── Page Heading & Filter Actions Row ─── -->
                <div class="penyewaan-header-row">
                    <div class="penyewaan-title-block">
                        <h1 class="penyewaan-main-heading">Manajemen Inventaris Sewa</h1>
                        <p class="penyewaan-subtitle">
                            Pantau dan proses penyewaan peralatan aktif di seluruh wilayah.
                        </p>
                    </div>

                    <div class="penyewaan-actions-right">
                        <!-- Filter Tabs Pill Group -->
                        <div class="filter-tab-pill-group">
                            <a href="{{ route('admin.penyewaan', array_merge(request()->query(), ['filter' => 'all'])) }}"
                               class="filter-tab-btn {{ $currentFilter === 'all' ? 'active' : '' }}">
                                Semua
                            </a>
                            <a href="{{ route('admin.penyewaan', array_merge(request()->query(), ['filter' => 'active'])) }}"
                               class="filter-tab-btn {{ $currentFilter === 'active' ? 'active' : '' }}">
                                Aktif
                            </a>
                            <a href="{{ route('admin.penyewaan', array_merge(request()->query(), ['filter' => 'past'])) }}"
                               class="filter-tab-btn {{ $currentFilter === 'past' ? 'active' : '' }}">
                                Selesai
                            </a>
                        </div>

                        <!-- Export CSV Button -->
                        <a href="{{ route('admin.penyewaan.export', ['filter' => $currentFilter, 'search' => $searchTerm]) }}"
                           class="btn-export-csv"
                           title="Unduh Data Penyewaan CSV">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            <span>Ekspor CSV</span>
                        </a>
                    </div>
                </div>

                <!-- ─── Tabel Penyewaan ─── -->
                <div class="alat-table-container">
                    <div class="table-responsive">
                        <table class="alat-table">
                            <thead>
                                <tr>
                                    <th>NAMA PENYEWA</th>
                                    <th>PRODUK</th>
                                    <th>LAMA SEWA</th>
                                    <th>STATUS</th>
                                    <th>TANGGAL</th>
                                    <th style="text-align: right;">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse ($orders as $order)
                                @php
                                    // Hitung inisial
                                    $userName = $order->user?->name ?? 'Guest User';
                                    $nameParts = explode(' ', trim($userName));
                                    $initials = count($nameParts) >= 2
                                        ? strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1))
                                        : strtoupper(substr($userName, 0, 2));

                                    // Item produk pertama
                                    $firstItem = $order->items->first();
                                    $productName = $firstItem ? $firstItem->name : 'Paket Peralatan Outdoor';
                                    $productImg = $firstItem?->image ?? ($firstItem?->product?->main_image ?? 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=150&q=80');
                                    $productGrade = $firstItem?->product?->grade ?? 'PRO-GRADE';

                                    // Durasi hari (inklusif: selisih tanggal kalender + 1)
                                    $days = $order->rent_start && $order->rent_end ? ($order->rent_start->diffInDays($order->rent_end) + 1) : 1;
                                    if ($days < 1) $days = 1;

                                    // Avatar color variant
                                    $avatarVariant = $order->status === 'active' ? 'orange' : ($order->status === 'pending' ? 'green' : 'gray');
                                @endphp
                                <tr>
                                    <!-- 1. NAMA PENYEWA -->
                                    <td>
                                        <div class="customer-cell-block">
                                            <div class="user-avatar-initials {{ $avatarVariant }}">
                                                {{ $initials }}
                                            </div>
                                            <div class="customer-info-txt">
                                                <span class="customer-name-bold">{{ $userName }}</span>
                                                <span class="customer-email-muted">{{ $order->user?->email ?? 'no-email@example.com' }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- 2. PRODUK -->
                                    <td>
                                        <div class="product-cell">
                                            <img src="{{ $productImg }}"
                                                 alt="{{ $productName }}"
                                                 class="product-thumb-img">
                                            <div class="product-info-details">
                                                <span class="product-name-txt">{{ $productName }}</span>
                                                <span class="gear-meta-grade">{{ $productGrade }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- 3. LAMA SEWA -->
                                    <td>
                                        <div>
                                            <div class="duration-days-txt">{{ $days }} Hari</div>
                                            <div class="duration-sub-info">
                                                @if ($order->status === 'active')
                                                    Berakhir {{ $order->rent_end ? $order->rent_end->format('M d') : 'Segera' }}
                                                @elseif ($order->status === 'pending')
                                                    Menunggu pengambilan
                                                @elseif ($order->status === 'completed')
                                                    Selesai
                                                @else
                                                    Dibatalkan
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <!-- 4. STATUS -->
                                    <td>
                                        @if ($order->status === 'active' || $order->status === 'paid')
                                            <span class="badge-status active">
                                                <span class="status-dot"></span> Aktif
                                            </span>
                                        @elseif ($order->status === 'pending')
                                            <span class="badge-status lowstock">
                                                <span class="status-dot"></span> Menunggu
                                            </span>
                                        @elseif ($order->status === 'completed')
                                            <span class="badge-status inactive">
                                                <span class="status-dot"></span> Selesai
                                            </span>
                                        @else
                                            <span class="badge-status inactive">
                                                <span class="status-dot"></span> Batal
                                            </span>
                                        @endif
                                    </td>

                                    <!-- 5. TANGGAL -->
                                    <td>
                                        <div class="datetime-stack">
                                            <span>{{ $order->created_at->format('M d, Y') }}</span>
                                            <span class="time-txt">{{ $order->created_at->format('H:i A') }}</span>
                                        </div>
                                    </td>

                                    <!-- 6. ACTIONS -->
                                    <td style="text-align: right;">
                                        <div class="action-icons-group" style="justify-content: flex-end;">
                                            @if ($order->status === 'pending')
                                                <!-- Action Konfirmasi -->
                                                <button type="button"
                                                        class="btn-action-confirm"
                                                        onclick="openConfirmModal('{{ $order->id }}', '{{ $order->code }}')">
                                                    Konfirmasi
                                                </button>

                                                <!-- Action Tolak -->
                                                <button type="button"
                                                        class="btn-action-reject"
                                                        onclick="openRejectModal('{{ $order->id }}', '{{ $order->code }}')">
                                                    Tolak
                                                </button>
                                            @elseif ($order->status === 'active' || $order->status === 'paid')
                                                <!-- Action Selesai -->
                                                <button type="button"
                                                        class="btn-action-selesai"
                                                        onclick="openCompleteModal('{{ $order->id }}', '{{ $order->code }}')">
                                                    Selesai
                                                </button>
                                                <!-- 3 Dots Detail Trigger -->
                                                <button type="button"
                                                        class="btn-action-icon"
                                                        title="Detail Pesanan"
                                                        onclick='openDetailModal(@json($order))'>
                                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                                        <circle cx="12" cy="5" r="2"></circle>
                                                        <circle cx="12" cy="12" r="2"></circle>
                                                        <circle cx="12" cy="19" r="2"></circle>
                                                    </svg>
                                                </button>
                                            @else
                                                <!-- View Receipt -->
                                                <button type="button"
                                                        class="btn-view-receipt"
                                                        onclick='openDetailModal(@json($order))'>
                                                    Lihat Resi
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 48px 20px; color: #64748b;">
                                        Belum ada data penyewaan yang sesuai dengan filter ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>

                    <!-- ─── Pagination Footer ─── -->
                    <div class="pagination-container-row">
                        <div class="results-counter-text">
                            Menampilkan {{ $orders->firstItem() ?? 1 }} hingga {{ $orders->lastItem() ?? count($orders) }} dari {{ $orders->total() ?? count($orders) }} penyewaan
                        </div>

                        @if ($orders->hasPages())
                            <div class="pagination-pages-list">
                                {{-- Previous Page Link --}}
                                @if ($orders->onFirstPage())
                                    <span class="page-nav-link" style="opacity: 0.4; cursor: not-allowed;">&lsaquo;</span>
                                @else
                                    <a href="{{ $orders->previousPageUrl() }}" class="page-nav-link">&lsaquo;</a>
                                @endif

                                {{-- Pagination Elements --}}
                                @foreach ($orders->getUrlRange(1, $orders->lastPage()) as $page => $url)
                                    @if ($page == $orders->currentPage())
                                        <span class="page-nav-link active">{{ $page }}</span>
                                    @else
                                        <a href="{{ $url }}" class="page-nav-link">{{ $page }}</a>
                                    @endif
                                @endforeach

                                {{-- Next Page Link --}}
                                @if ($orders->hasMorePages())
                                    <a href="{{ $orders->nextPageUrl() }}" class="page-nav-link">&rsaquo;</a>
                                @else
                                    <span class="page-nav-link" style="opacity: 0.4; cursor: not-allowed;">&rsaquo;</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </main>

            <!-- Footer -->
            @include('partials.footer', ['footerContext' => 'admin'])
        </div>
    </div>

    <!-- ─── Modal Detail / Receipt ─── -->
    <div id="detailModal" class="modal-overlay">
        <div class="modal-card" style="width: 580px;">
            <div class="modal-header">
                <h3 class="modal-title" id="receipt_order_code">Detail Penyewaan</h3>
                <button type="button" class="btn-close-modal" onclick="closeDetailModal()">&times;</button>
            </div>
            <div class="modal-body" id="detailModalContent">
                <!-- Content will be rendered dynamically by JS -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-submit" onclick="closeDetailModal()">Tutup</button>
            </div>
        </div>
    </div>

    <!-- ─── Modal Konfirmasi Penyewaan ─── -->
    <div id="confirmModal" class="modal-overlay">
        <div class="modal-card" style="width: 440px;">
            <div class="modal-header">
                <h3 class="modal-title" style="color: #185d31;">Konfirmasi Penyewaan</h3>
                <button type="button" class="btn-close-modal" onclick="closeConfirmModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p style="font-size: 14px; color: #374151; line-height: 1.5;">
                    Apakah Anda yakin ingin menyetujui dan mengaktifkan permintaan sewa <strong id="confirm_order_code"></strong>? Stok alat akan dialokasikan untuk peminjam.
                </p>
            </div>
            <div class="modal-footer">
                <form id="confirmForm" method="POST" action="">
                    @csrf
                    <button type="button" class="btn-modal-cancel" onclick="closeConfirmModal()">Batal</button>
                    <button type="submit" class="btn-modal-submit">Ya, Konfirmasi</button>
                </form>
            </div>
        </div>
    </div>

    <!-- ─── Modal Tolak Penyewaan ─── -->
    <div id="rejectModal" class="modal-overlay">
        <div class="modal-card" style="width: 460px;">
            <div class="modal-header">
                <h3 class="modal-title" style="color: #dc2626;">Tolak Permintaan Sewa</h3>
                <button type="button" class="btn-close-modal" onclick="closeRejectModal()">&times;</button>
            </div>
            <form id="rejectForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <p style="font-size: 14px; color: #374151; line-height: 1.5; margin-bottom: 12px;">
                        Apakah Anda yakin ingin menolak permintaan sewa <strong id="reject_order_code"></strong>?
                    </p>
                    <div class="form-group-modal">
                        <label class="form-label-modal">Alasan Penolakan (Opsional)</label>
                        <input type="text" name="reason" class="form-input-modal" placeholder="Contoh: Stok alat sedang pemeliharaan">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" onclick="closeRejectModal()">Batal</button>
                    <button type="submit" class="btn-modal-submit" style="background-color: #dc2626;">Tolak Permintaan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── Modal Selesaikan Penyewaan ─── -->
    <div id="completeModal" class="modal-overlay">
        <div class="modal-card" style="width: 440px;">
            <div class="modal-header">
                <h3 class="modal-title" style="color: #185d31;">Selesaikan Penyewaan</h3>
                <button type="button" class="btn-close-modal" onclick="closeCompleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p style="font-size: 14px; color: #374151; line-height: 1.5;">
                    Apakah alat untuk penyewaan <strong id="complete_order_code"></strong> telah diterima kembali dalam kondisi baik? Stok alat akan otomatis ditambahkan kembali.
                </p>
            </div>
            <div class="modal-footer">
                <form id="completeForm" method="POST" action="">
                    @csrf
                    <button type="button" class="btn-modal-cancel" onclick="closeCompleteModal()">Batal</button>
                    <button type="submit" class="btn-modal-submit">Ya, Selesaikan</button>
                </form>
            </div>
        </div>
    </div>

    <!-- ─── Scripts ─── -->
    <script>
        function openDetailModal(order) {
            document.getElementById('receipt_order_code').textContent = 'Detail Pesanan #' + order.code;

            let itemsHtml = '';
            if (order.items && order.items.length > 0) {
                order.items.forEach(it => {
                    itemsHtml += `
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px dashed #e5e7eb;">
                            <div>
                                <div style="font-weight: 700; color: #1a1d1a;">${it.name}</div>
                                <div style="font-size: 11px; color: #64748b;">${it.quantity} Unit &times; ${it.days} Hari</div>
                            </div>
                            <div style="font-weight: 700; color: #185d31;">Rp ${parseInt(it.subtotal || 0).toLocaleString('id-ID')}</div>
                        </div>
                    `;
                });
            } else {
                itemsHtml = '<p style="color: #64748b; font-size: 12px;">Peralatan Outdoor Standard</p>';
            }

            const payment = (order.payments && order.payments.length > 0) ? order.payments[0] : null;
            const paymentMethod = payment ? payment.method.toUpperCase() : 'QRIS';
            const paymentStatus = payment ? payment.status.toUpperCase() : 'PAID';

            const html = `
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <div style="background: #f8faf8; padding: 14px; border-radius: 10px; border: 1px solid #e8e6e1;">
                        <div style="font-size: 11px; font-weight: 800; color: #185d31; text-transform: uppercase; margin-bottom: 4px;">Informasi Penyewa</div>
                        <div style="font-size: 14px; font-weight: 700; color: #1a1d1a;">${order.user ? order.user.name : 'Guest User'}</div>
                        <div style="font-size: 12px; color: #64748b;">Email: ${order.user ? order.user.email : '-'}</div>
                        <div style="font-size: 12px; color: #64748b;">Domisili: ${order.user && order.user.domicile ? order.user.domicile : '-'}</div>
                    </div>

                    <div>
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 6px;">Peralatan yang Disewa</div>
                        ${itemsHtml}
                    </div>

                    <div style="display: flex; justify-content: space-between; font-size: 13px; color: #4b5563; padding-top: 4px;">
                        <span>Periode Sewa:</span>
                        <span style="font-weight: 700; color: #1a1d1a;">${order.rent_start ? order.rent_start.substring(0, 10) : '-'} s/d ${order.rent_end ? order.rent_end.substring(0, 10) : '-'}</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; font-size: 13px; color: #4b5563;">
                        <span>Metode Pembayaran:</span>
                        <span style="font-weight: 700; color: #185d31;">${paymentMethod} (${paymentStatus})</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: baseline; border-top: 2px solid #e5e7eb; padding-top: 10px; margin-top: 4px;">
                        <span style="font-size: 14px; font-weight: 800; color: #1a1d1a;">TOTAL BIAYA</span>
                        <span style="font-size: 20px; font-weight: 800; color: #185d31;">Rp ${parseInt(order.total || 0).toLocaleString('id-ID')}</span>
                    </div>
                </div>
            `;

            document.getElementById('detailModalContent').innerHTML = html;
            document.getElementById('detailModal').classList.add('active');
        }

        function closeDetailModal() {
            document.getElementById('detailModal').classList.remove('active');
        }

        function openConfirmModal(id, code) {
            document.getElementById('confirmForm').action = '/admin/penyewaan/' + id + '/confirm';
            document.getElementById('confirm_order_code').textContent = '#' + code;
            document.getElementById('confirmModal').classList.add('active');
        }

        function closeConfirmModal() {
            document.getElementById('confirmModal').classList.remove('active');
        }

        function openRejectModal(id, code) {
            document.getElementById('rejectForm').action = '/admin/penyewaan/' + id + '/reject';
            document.getElementById('reject_order_code').textContent = '#' + code;
            document.getElementById('rejectModal').classList.add('active');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.remove('active');
        }

        function openCompleteModal(id, code) {
            document.getElementById('completeForm').action = '/admin/penyewaan/' + id + '/complete';
            document.getElementById('complete_order_code').textContent = '#' + code;
            document.getElementById('completeModal').classList.add('active');
        }

        function closeCompleteModal() {
            document.getElementById('completeModal').classList.remove('active');
        }

        // Close on Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDetailModal();
                closeConfirmModal();
                closeRejectModal();
                closeCompleteModal();
            }
        });
    </script>

</body>
</html>
