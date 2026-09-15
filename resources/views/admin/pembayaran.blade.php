<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}">
    <title>Pembayaran - Log Audit Keuangan - Summit Station</title>
    <link rel="stylesheet" href="{{ asset('css/summit-admin.css') . '?v=' . time() }}">
    <link rel="stylesheet" href="{{ asset('css/summit-footer.css') . '?v=' . filemtime(public_path('css/summit-footer.css')) }}">
</head>
<body>

    <!-- Blue Top Accent Line -->
    <div class="top-banner-line"></div>

    <div class="admin-layout">
        <!-- ─── Sidebar ─── -->
        @include('admin.partials.sidebar', ['activeMenu' => 'pembayaran'])

        <!-- ─── Main Content Area ─── -->
        <div class="admin-main">
            <!-- Header with Breadcrumb & Admin Profile -->
            @include('admin.partials.header', [
                'adminPageTitle' => 'Pembayaran',
                'adminPageSubtitle' => 'Audit & verifikasi pembayaran',
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
                    <!-- Card 1: TOTAL REVENUE -->
                    <div class="stat-card-penyewaan">
                        <div class="stat-card-penyewaan-top">
                            <div class="stat-penyewaan-label">TOTAL PENDAPATAN</div>
                            <div class="stat-icon-penyewaan green" style="width: 28px; height: 28px; border-radius: 6px;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                                    <line x1="2" y1="10" x2="22" y2="10"></line>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <div class="stat-penyewaan-val" style="font-size: 32px; line-height: 1.1;">
                                Rp {{ number_format((int) $totalRevenue, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="stat-subtext-note green">
                            <span>↗</span>
                            <span>Total pendapatan dari seluruh pembayaran disetujui</span>
                        </div>
                    </div>

                    <!-- Card 2: PENDING VERIFICATIONS -->
                    <div class="stat-card-penyewaan">
                        <div class="stat-card-penyewaan-top">
                            <div class="stat-penyewaan-label">VERIFIKASI<br>MENUNGGU</div>
                            <div class="stat-icon-penyewaan orange" style="width: 28px; height: 28px; border-radius: 6px;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <div class="stat-penyewaan-val" style="font-size: 42px;">{{ $pendingCount < 10 ? '0' . $pendingCount : $pendingCount }}</div>
                        </div>
                        <div class="stat-subtext-note muted" style="font-size: 11px;">
                            <span>Perlu tindakan segera</span>
                        </div>
                    </div>

                    <!-- Card 3: FAILED TRANSACTIONS -->
                    <div class="stat-card-penyewaan">
                        <div class="stat-card-penyewaan-top">
                            <div class="stat-penyewaan-label">TRANSAKSI<br>GAGAL</div>
                            <div style="color: #dc2626;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <div class="stat-penyewaan-val" style="font-size: 42px;">{{ $failedCount < 10 ? '0' . $failedCount : $failedCount }}</div>
                        </div>
                        <div class="stat-subtext-note muted" style="font-size: 11px;">
                            <span>24 jam terakhir</span>
                        </div>
                    </div>

                    <!-- Card 4: SUCCESS RATE -->
                    <div class="stat-card-penyewaan">
                        <div class="stat-card-penyewaan-top">
                            <div class="stat-penyewaan-label">TINGKAT KEBERHASILAN</div>
                        </div>
                        <div>
                            <div class="stat-penyewaan-val" style="font-size: 42px;">{{ $successRate }}%</div>
                            <div class="success-rate-bar-container">
                                <div class="success-rate-bar-fill" style="width: {{ min(100, $successRate) }}%;"></div>
                            </div>
                        </div>
                        <div></div>
                    </div>
                </div>

                <!-- ─── Main Two-Column / Flexible Layout ─── -->
                <div class="pembayaran-main-container">
                    <!-- Left Section: Heading, Filter, and Financial Audit Log Table -->
                    <div class="pembayaran-table-section">
                        <!-- Heading & Action Bar -->
                        <div class="penyewaan-header-row" style="margin-top: 0;">
                            <div class="penyewaan-title-block">
                                <h1 class="penyewaan-main-heading">Log Audit Keuangan</h1>
                                <p class="penyewaan-subtitle">
                                    Kelola dan verifikasi semua pembayaran sewa yang masuk
                                </p>
                            </div>

                            <div class="penyewaan-actions-right">
                                <!-- Filter Dropdown Form -->
                                <form method="GET" action="{{ route('admin.pembayaran') }}" id="filterForm" style="display: flex; gap: 8px;">
                                    <select name="filter" class="filter-select-input" onchange="this.form.submit()" style="padding: 8px 14px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 13px; font-weight: 700; background: #ffffff; color: #374151; cursor: pointer;">
                                        <option value="all" {{ $currentFilter === 'all' ? 'selected' : '' }}>Filter: Semua Status</option>
                                        <option value="pending" {{ $currentFilter === 'pending' ? 'selected' : '' }}>Verifikasi Menunggu</option>
                                        <option value="confirmed" {{ $currentFilter === 'confirmed' ? 'selected' : '' }}>Dikonfirmasi</option>
                                        <option value="rejected" {{ $currentFilter === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                                    </select>
                                    @if ($searchTerm)
                                        <input type="hidden" name="search" value="{{ $searchTerm }}">
                                    @endif
                                </form>

                                <!-- Export CSV Button -->
                                <a href="{{ route('admin.pembayaran.export', ['filter' => $currentFilter, 'search' => $searchTerm]) }}"
                                   class="btn-export-csv"
                                   title="Unduh Audit Log CSV">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7 10 12 15 17 10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                    <span>Ekspor CSV</span>
                                </a>
                            </div>
                        </div>

                        <!-- ─── Financial Audit Log Table ─── -->
                        <div class="alat-table-container">
                            <div class="table-responsive">
                                <table class="alat-table">
                                    <thead>
                                        <tr>
                                            <th class="col-trx-id">KODE TRANSAKSI</th>
                                            <th class="col-customer">PELANGGAN</th>
                                            <th class="col-method">METODE</th>
                                            <th class="col-amount">JUMLAH</th>
                                            <th class="col-proof">BUKTI</th>
                                            <th class="col-status">STATUS</th>
                                            <th class="col-actions">AKSI</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($payments as $index => $payment)
                                            @php
                                                                    $user = $payment->order?->user;
                                                                    $userName = $user?->name ?? 'Pelanggan';
                                                                    $nameParts = explode(' ', trim($userName));
                                                                    $initials = count($nameParts) >= 2
                                                                        ? strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1))
                                                                        : strtoupper(substr($userName, 0, 2));

                                                                    $customerRole = 'Pelanggan';
                                                                    $customerLevel = $user?->status ?? 'aktif';
                                                                    $avatarColor = match ($customerLevel) {
                                                                        'suspended', 'inactive' => 'gray',
                                                                        default => 'green',
                                                                    };

                                                $methodName = $payment->formatted_method;
                                                $methodIcon = match (strtolower($payment->method)) {
                                                    'bca', 'bank_transfer', 'bca_transfer' => '🏛',
                                                    'mandiri', 'mandiri_va' => '💳',
                                                    'gopay' => '📱',
                                                    'qris' => '📷',
                                                    default => '💰',
                                                };
                                            @endphp
                                            <tr onclick="selectPaymentForVerification({{ json_encode([
                                                'id' => $payment->id,
                                                'trx_code' => $payment->trx_code,
                                                'customer_name' => $userName,
                                                'customer_email' => $user?->email ?? '-',
                                                'customer_role' => $customerRole,
                                                'method' => $methodName,
                                                'amount' => $payment->amount,
                                                'amount_formatted' => 'Rp ' . number_format($payment->amount, 0, ',', '.'),
                                                'status' => $payment->status,
                                                'reference' => $payment->reference ?? null,
                                                'timestamp' => $payment->created_at ? $payment->created_at->format('Y-m-d H:i') : null,
                                                'proof_url' => $payment->proof_url,
                                                'has_proof' => (bool) $payment->has_proof,
                                                'match_warning' => 'Periksa kecocokan nama pengirim, nominal, dan nomor referensi pada bukti transfer sebelum menyetujui pembayaran.',
                                            ]) }})" style="cursor: pointer;">
                                                <!-- 1. TRANSACTION ID -->
                                                <td class="col-trx-id">
                                                    <div style="display: flex; flex-direction: column; gap: 2px;">
                                                        <div style="display: flex; align-items: center; gap: 6px;">
                                                            <span style="font-size: 13px; font-weight: 800; color: #1a1d1a; font-family: monospace;">{{ $payment->trx_code }}</span>
                                                            @if ($payment->is_denda_payment)
                                                                <span style="font-size: 9.5px; font-weight: 800; background: #fff7ed; border: 1px solid #fdba74; color: #c2410c; padding: 1px 6px; border-radius: 999px; letter-spacing: .4px;">DENDA</span>
                                                            @endif
                                                        </div>
                                                        @if ($payment->order)
                                                            <span style="font-size: 11px; color: #185d31; font-weight: 700;">#{{ $payment->order->code }}</span>
                                                        @endif
                                                        <span style="font-size: 11px; color: #64748b;">
                                                            {{ $payment->created_at ? $payment->created_at->format('M d, Y • H:i') : '-' }}
                                                        </span>
                                                    </div>
                                                </td>

                                                <!-- 2. CUSTOMER -->
                                                <td class="col-customer">
                                                    <div class="customer-cell-block">
                                                        <div class="user-avatar-initials {{ $avatarColor }}">
                                                            {{ $initials }}
                                                        </div>
                                                        <div class="customer-info-txt">
                                                            <span class="customer-name-bold">{{ $userName }}</span>
                                                            <span class="customer-email-muted" style="font-size: 11px;">{{ $customerRole }}</span>
                                                        </div>
                                                    </div>
                                                </td>

                                                <!-- 3. METHOD -->
                                                <td class="col-method">
                                                    <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: #374151;">
                                                        <span>{{ $methodIcon }}</span>
                                                        <span>{{ $methodName }}</span>
                                                    </div>
                                                </td>

                                                <!-- 4. AMOUNT -->
                                                <td class="col-amount">
                                                    <div style="font-size: 14px; font-weight: 800; color: #1a1d1a;">
                                                        Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                                    </div>
                                                </td>

                                                <!-- 5. PROOF -->
                                                <td class="col-proof">
                                                    @if ($payment->has_proof)
                                                        <button type="button"
                                                                class="proof-thumbnail-btn"
                                                                title="Lihat Bukti Transfer"
                                                                onclick="event.stopPropagation(); openProofLightbox('{{ $payment->proof_url }}', '{{ $payment->trx_code }}')">
                                                            <img src="{{ $payment->proof_url }}"
                                                                 alt="Bukti Transfer"
                                                                 class="proof-thumbnail-img">
                                                        </button>
                                                    @else
                                                        <span class="proof-none-badge" title="Bukti belum dikirim">—</span>
                                                    @endif
                                                </td>

                                                <!-- 6. STATUS -->
                                                <td class="col-status">
                                                    @if ($payment->status === 'pending')
                                                        <span class="badge-status pending-verification">Verifikasi Menunggu</span>
                                                    @elseif ($payment->status === 'success')
                                                        <span class="badge-status confirmed">
                                                            <span class="status-dot"></span> Dikonfirmasi
                                                        </span>
                                                    @else
                                                        <span class="badge-status declined">Ditolak</span>
                                                    @endif
                                                </td>
                                                <!-- 7. ACTIONS -->
                                                <td class="col-actions" onclick="event.stopPropagation();">
                                                    <div class="action-icons-group" style="justify-content: flex-end; gap: 6px;">
                                                        @if ($payment->status === 'pending')
                                                            <!-- Quick Approve Button -->
                                                            <button type="button"
                                                                    class="action-icon-circle-btn approve"
                                                                    title="Setujui Pembayaran"
                                                                    onclick="openApproveModal('{{ $payment->id }}', '{{ $payment->trx_code }}')">
                                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                                </svg>
                                                            </button>

                                                            <!-- Quick Reject Button -->
                                                            <button type="button"
                                                                    class="action-icon-circle-btn reject"
                                                                    title="Tolak Pembayaran"
                                                                    onclick="openRejectModal('{{ $payment->id }}', '{{ $payment->trx_code }}')">
                                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                                                </svg>
                                                            </button>

                                                            <!-- Select for Verification Drawer -->
                                                            <button type="button"
                                                                    class="action-icon-circle-btn detail"
                                                                    title="Buka Panel Verifikasi"
                                                                    onclick="selectPaymentForVerification({{ json_encode([
                                                                        'id' => $payment->id,
                                                                        'trx_code' => $payment->trx_code,
                                                                        'customer_name' => $userName,
                                                                        'customer_email' => $user?->email ?? '-',
                                                                        'customer_role' => $customerRole,
                                                                        'method' => $methodName,
                                                                        'amount' => $payment->amount,
                                                                        'amount_formatted' => 'Rp ' . number_format($payment->amount, 0, ',', '.'),
                                                                        'status' => $payment->status,
                                                                        'reference' => $payment->reference ?? '7728399102-X',
                                                                        'timestamp' => $payment->created_at ? $payment->created_at->format('Y-m-d H:i') : '2023-10-24 14:18',
                                                                        'proof_url' => $payment->proof_url,
                                                                        'has_proof' => (bool) $payment->has_proof,
                                                                        'match_warning' => $payment->match_warning ?? 'System mendeteksi kecocokan nama 99% antara pengirim dan profil user. Nomor rekening telah terlihat 3 kali sebelumnya.',
                                                                    ]) }})">
                                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                                                    <polyline points="22,6 12,13 2,6"></polyline>
                                                                </svg>
                                                            </button>
                                                        @elseif ($payment->status === 'success')
                                                            <span class="badge-audit-complete">Audit Selesai</span>
                                                            <button type="button"
                                                                    class="action-icon-circle-btn detail"
                                                                    title="Buka Panel Verifikasi"
                                                                    onclick="selectPaymentForVerification({{ json_encode([
                                                                        'id' => $payment->id,
                                                                        'trx_code' => $payment->trx_code,
                                                                        'customer_name' => $userName,
                                                                        'customer_email' => $user?->email ?? '-',
                                                                        'customer_role' => $customerRole,
                                                                        'method' => $methodName,
                                                                        'amount' => $payment->amount,
                                                                        'amount_formatted' => 'Rp ' . number_format($payment->amount, 0, ',', '.'),
                                                                        'status' => $payment->status,
                                                                        'reference' => $payment->reference ?? '7728399102-X',
                                                                        'timestamp' => $payment->created_at ? $payment->created_at->format('Y-m-d H:i') : '2023-10-24 14:18',
                                                                        'proof_url' => $payment->proof_url,
                                                                        'has_proof' => (bool) $payment->has_proof,
                                                                        'match_warning' => $payment->match_warning ?? 'System mendeteksi kecocokan nama 99% antara pengirim dan profil user. Nomor rekening telah terlihat 3 kali sebelumnya.',
                                                                    ]) }})">
                                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                                                    <polyline points="22,6 12,13 2,6"></polyline>
                                                                </svg>
                                                            </button>
                                                        @else
                                                            <a href="javascript:void(0)"
                                                               class="btn-retry-link"
                                                               onclick="openRejectModal('{{ $payment->id }}', '{{ $payment->trx_code }}')">
                                                                Tautan Coba Ulang
                                                            </a>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" style="text-align: center; padding: 48px 20px; color: #64748b;">
                                                    Belum ada transaksi pembayaran yang sesuai dengan filter ini.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination Footer -->
                            <div class="pagination-container-row">
                                <div class="results-counter-text">
                                    Menampilkan {{ $payments->firstItem() ?? 1 }} hingga {{ $payments->lastItem() ?? count($payments) }} dari {{ $payments->total() }} transaksi
                                </div>

                                @if ($payments->hasPages())
                                    <div class="pagination-pages-list">
                                        {{-- Previous Page Link --}}
                                        @if ($payments->onFirstPage())
                                            <span class="page-nav-link" style="opacity: 0.4; cursor: not-allowed;">&lsaquo;</span>
                                        @else
                                            <a href="{{ $payments->previousPageUrl() }}" class="page-nav-link">&lsaquo;</a>
                                        @endif

                                        {{-- Pagination Elements --}}
                                        @foreach ($payments->getUrlRange(1, $payments->lastPage()) as $page => $url)
                                            @if ($page == $payments->currentPage())
                                                <span class="page-nav-link active">{{ $page }}</span>
                                            @else
                                                <a href="{{ $url }}" class="page-nav-link">{{ $page }}</a>
                                            @endif
                                        @endforeach

                                        {{-- Next Page Link --}}
                                        @if ($payments->hasMorePages())
                                            <a href="{{ $payments->nextPageUrl() }}" class="page-nav-link">&rsaquo;</a>
                                        @else
                                            <span class="page-nav-link" style="opacity: 0.4; cursor: not-allowed;">&rsaquo;</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- ─── Right Section: Payment Verification Panel / Drawer ─── -->
                    <div id="verificationDrawer" class="payment-verification-drawer">
                        <div class="verification-drawer-header">
                            <h2 class="verification-drawer-title">Verifikasi Pembayaran</h2>
                            <button type="button" class="verification-drawer-close" onclick="closeVerificationDrawer()">&times;</button>
                        </div>

                        <!-- Transaction Summary -->
                        <div class="drawer-transaction-card">
                            <div class="meta-field-item">
                                <span class="meta-field-label">Metode Pembayaran</span>
                                <span class="meta-field-val" id="drawer_receipt_method">-</span>
                            </div>
                            <div class="receipt-info-item">
                                <span class="receipt-info-label">Pelanggan</span>
                                <span class="receipt-info-val" id="drawer_sender_name">-</span>
                            </div>
                            <div class="receipt-info-item">
                                <span class="receipt-info-label">Nominal</span>
                                <span class="receipt-info-val" id="drawer_receipt_amount">-</span>
                            </div>
                            <div class="receipt-info-item">
                                <span class="receipt-info-label">Kode Transaksi</span>
                                <span class="receipt-info-val" id="drawer_receipt_trx">-</span>
                            </div>
                        </div>

                        <!-- SUBMITTED PROOF SCREEN -->
                        <div class="submitted-proof-section">
                            <span class="submitted-proof-label">BUKTI YANG DIKIRIM</span>

                            {{-- Tampilan saat bukti ADA --}}
                            <div id="proof_has_proof" style="display: none;">
                                <div class="proof-display-card" onclick="openProofLightbox(currentVerificationPayment.proof_url, currentVerificationPayment.trx_code)">
                                    <img id="proof_has_proof_img" src="" alt="Bukti Pembayaran" class="proof-display-img">
                                    <span class="proof-click-hint">Klik untuk memperbesar</span>
                                </div>
                            </div>

                            {{-- Tampilan saat bukti TIDAK ADA --}}
                            <div id="proof_no_proof" style="display: none;">
                                <div class="proof-empty-card">
                                    <div class="proof-empty-icon">
                                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                            <polyline points="21 15 16 10 5 21"></polyline>
                                        </svg>
                                    </div>
                                    <span class="proof-empty-title">Bukti pembayaran belum dikirim</span>
                                    <span class="proof-empty-sub">User belum mengunggah bukti transfer untuk transaksi ini.</span>
                                </div>
                            </div>

                            {{-- Default: pilih transaksi --}}
                            <div id="proof_default">
                                <div class="proof-empty-card">
                                    <div class="proof-empty-icon">
                                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14 2 14 8 20 8"></polyline>
                                            <line x1="12" y1="18" x2="12" y2="12"></line>
                                            <line x1="9" y1="15" x2="15" y2="15"></line>
                                        </svg>
                                    </div>
                                    <span class="proof-empty-title">Pilih transaksi untuk melihat bukti</span>
                                    <span class="proof-empty-sub">Klik baris pembayaran pada tabel di sebelah kiri.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Reference Code & Bank Timestamp Metadata -->
                        <div class="metadata-grid-row">
                            <div class="meta-field-item">
                                <span class="meta-field-label">Kode Referensi</span>
                                <span class="meta-field-val" id="drawer_meta_ref">-</span>
                            </div>
                            <div class="meta-field-item">
                                <span class="meta-field-label">Waktu Bank</span>
                                <span class="meta-field-val" id="drawer_meta_time">-</span>
                            </div>
                        </div>

                        <!-- Automatic Match Warning -->
                        <div class="automatic-match-warning-box">
                            <div class="match-warning-header">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                    <polyline points="9 12 11 14 15 10"></polyline>
                                </svg>
                                <span>Peringatan Kecocokan Otomatis</span>
                            </div>
                            <p class="match-warning-text" id="drawer_match_warning">
                                Pilih baris pembayaran untuk memverifikasi kecocokan nama pengirim, nominal, dan referensi transfer secara otomatis.
                            </p>
                        </div>

                        <!-- Bottom Verify & Reject Actions -->
                        <div class="verification-drawer-actions" id="drawer_actions_wrap">
                            <button type="button"
                                    class="btn-drawer-reject"
                                    id="btn_drawer_reject"
                                    onclick="openRejectModal(currentVerificationPayment.id, currentVerificationPayment.trx_code)">
                                TOLAK BUKTI
                            </button>
                            <button type="button"
                                    class="btn-drawer-approve"
                                    id="btn_drawer_approve"
                                    onclick="openApproveModal(currentVerificationPayment.id, currentVerificationPayment.trx_code)">
                                SETUJUI PEMBAYARAN
                            </button>
                        </div>
                        <div id="drawer_status_settled" style="display: none; text-align: center; padding: 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; color: #166534; font-weight: 700; font-size: 13px; margin-top: 10px;">
                            ✓ Pembayaran telah disetujui (Lunas)
                        </div>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            @include('partials.footer', ['footerContext' => 'admin'])
        </div>
    </div>

    <!-- ─── Modal Approve Payment ─── -->
    <div id="approveModal" class="modal-overlay">
        <div class="modal-card" style="width: 440px;">
            <div class="modal-header">
                <h3 class="modal-title" style="color: #185d31;">Setujui Pembayaran</h3>
                <button type="button" class="btn-close-modal" onclick="closeApproveModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p id="approve_modal_desc" style="font-size: 14px; color: #374151; line-height: 1.5;">
                    Apakah Anda yakin ingin memverifikasi dan menyetujui pembayaran <strong id="approve_trx_code"></strong>? Status pesanan sewa akan otomatis diaktifkan.
                </p>
            </div>
            <div class="modal-footer">
                <form id="approveForm" method="POST" action="">
                    @csrf
                    <button type="button" class="btn-modal-cancel" onclick="closeApproveModal()">Batal</button>
                    <button type="submit" class="btn-modal-submit">Ya, Setujui</button>
                </form>
            </div>
        </div>
    </div>

    <!-- ─── Modal Reject Payment ─── -->
    <div id="rejectModal" class="modal-overlay">
        <div class="modal-card" style="width: 460px;">
            <div class="modal-header">
                <h3 class="modal-title" style="color: #dc2626;">Tolak Bukti Pembayaran</h3>
                <button type="button" class="btn-close-modal" onclick="closeRejectModal()">&times;</button>
            </div>
            <form id="rejectForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <p style="font-size: 14px; color: #374151; line-height: 1.5; margin-bottom: 12px;">
                        Masukkan alasan penolakan bukti pembayaran untuk transaksi <strong id="reject_trx_code"></strong>:
                    </p>
                    <div class="form-group-modal">
                        <label class="form-label-modal">Alasan Penolakan</label>
                        <input type="text" name="reason" class="form-input-modal" placeholder="Contoh: Bukti transfer tidak jelas / nominal tidak cocok" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" onclick="closeRejectModal()">Batal</button>
                    <button type="submit" class="btn-modal-submit" style="background-color: #dc2626;">Tolak Pembayaran</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── Modal Lightbox Proof Image ─── -->
    <div id="proofLightboxModal" class="modal-overlay">
        <div class="modal-card" style="width: 600px; max-width: 90vw;">
            <div class="modal-header">
                <h3 class="modal-title" id="lightbox_title">Bukti Pembayaran</h3>
                <button type="button" class="btn-close-modal" onclick="closeProofLightbox()">&times;</button>
            </div>
            <div class="modal-body" style="text-align: center; padding: 20px;">
                <img id="lightbox_img" src="" alt="Bukti Transfer Penuh" style="max-width: 100%; max-height: 70vh; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-submit" onclick="closeProofLightbox()">Tutup</button>
            </div>
        </div>
    </div>

    <!-- ─── JavaScript Interaction ─── -->
    <script>
        let currentVerificationPayment = {
            id: null,
            trx_code: '',
            customer_name: '',
            customer_email: '',
            customer_role: '',
            method: '',
            amount: 0,
            amount_formatted: 'Rp 0',
            status: 'pending',
            reference: '',
            timestamp: '',
            proof_url: '',
            has_proof: false,
            match_warning: 'Pilih baris pembayaran di tabel untuk menampilkan rincian bukti transfer dan verifikasi otomatis.'
        };

        function setDrawerText(id, value) {
            var el = document.getElementById(id);
            if (el) el.textContent = value;
        }

        function updateProofDisplay(hasProof, proofUrl) {
            var hasBox = document.getElementById('proof_has_proof');
            var noBox = document.getElementById('proof_no_proof');
            var defBox = document.getElementById('proof_default');
            if (!hasBox || !noBox || !defBox) return;

            if (hasProof && proofUrl) {
                document.getElementById('proof_has_proof_img').src = proofUrl;
                hasBox.style.display = 'block';
                noBox.style.display = 'none';
                defBox.style.display = 'none';
            } else {
                hasBox.style.display = 'none';
                noBox.style.display = 'block';
                defBox.style.display = 'none';
            }
        }

        function selectPaymentForVerification(paymentData) {
            currentVerificationPayment = paymentData;

            setDrawerText('drawer_receipt_bank', paymentData.method + ' Bukti Transaksi Berhasil');
            setDrawerText('drawer_receipt_amount', paymentData.amount_formatted);
            setDrawerText('drawer_sender_name', paymentData.customer_name);
            setDrawerText('drawer_receipt_method', paymentData.method);
            setDrawerText('drawer_receipt_trx', paymentData.trx_code);
            setDrawerText('drawer_receipt_ref', paymentData.reference);
            setDrawerText('drawer_meta_ref', paymentData.reference);
            setDrawerText('drawer_meta_time', paymentData.timestamp);
            setDrawerText('drawer_match_warning', paymentData.match_warning);

            updateProofDisplay(paymentData.has_proof, paymentData.proof_url);

            const actionsWrap = document.getElementById('drawer_actions_wrap');
            const settledBox = document.getElementById('drawer_status_settled');
            if (actionsWrap && settledBox) {
                if (paymentData.status === 'success') {
                    actionsWrap.style.display = 'none';
                    settledBox.style.display = 'block';
                } else {
                    actionsWrap.style.display = 'flex';
                    settledBox.style.display = 'none';
                }
            }

            const drawer = document.getElementById('verificationDrawer');
            drawer.style.display = 'flex';
        }

        function closeVerificationDrawer() {
            document.getElementById('verificationDrawer').style.display = 'none';
        }

        function openApproveModal(id, trxCode) {
            document.getElementById('approveForm').action = '/admin/pembayaran/' + id + '/approve';
            document.getElementById('approve_trx_code').textContent = trxCode;
            const desc = document.getElementById('approve_modal_desc');
            if (desc) {
                if (currentVerificationPayment && currentVerificationPayment.id == id && currentVerificationPayment.reference && currentVerificationPayment.reference.startsWith('FINE-')) {
                    desc.innerHTML = 'Apakah Anda yakin ingin memverifikasi dan menyetujui pembayaran sanksi/denda <strong id="approve_trx_code">' + trxCode + '</strong>? Status denda keterlambatan akan diperbarui menjadi Lunas.';
                } else {
                    desc.innerHTML = 'Apakah Anda yakin ingin memverifikasi dan menyetujui pembayaran <strong id="approve_trx_code">' + trxCode + '</strong>? Status pembayaran akan diverifikasi dan disetujui.';
                }
            }
            document.getElementById('approveModal').classList.add('active');
        }

        function closeApproveModal() {
            document.getElementById('approveModal').classList.remove('active');
        }

        function openRejectModal(id, trxCode) {
            document.getElementById('rejectForm').action = '/admin/pembayaran/' + id + '/reject';
            document.getElementById('reject_trx_code').textContent = trxCode;
            document.getElementById('rejectModal').classList.add('active');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.remove('active');
        }

        function openProofLightbox(url, trxCode) {
            document.getElementById('lightbox_title').textContent = 'Bukti Pembayaran ' + trxCode;
            document.getElementById('lightbox_img').src = url;
            document.getElementById('proofLightboxModal').classList.add('active');
        }

        function closeProofLightbox() {
            document.getElementById('proofLightboxModal').classList.remove('active');
        }

        // Close modal on Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeApproveModal();
                closeRejectModal();
                closeProofLightbox();
            }
        });
    </script>

</body>
</html>
