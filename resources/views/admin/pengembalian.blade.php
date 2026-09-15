<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}">
    <title>Pengembalian Alat - Summit Station Admin</title>
    <link rel="stylesheet" href="{{ asset('css/summit-admin.css') . '?v=' . time() }}">
    <link rel="stylesheet" href="{{ asset('css/summit-return.css') . '?v=' . filemtime(public_path('css/summit-return.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-footer.css') . '?v=' . filemtime(public_path('css/summit-footer.css')) }}">
</head>
<body>

    <!-- Blue Top Accent Line -->
    <div class="top-banner-line"></div>

    <div class="admin-layout">
        <!-- 1. Sidebar Admin -->
        @include('admin.partials.sidebar', ['activeMenu' => 'pengembalian'])

        <!-- 2. Main Content -->
        <div class="admin-main">
            @include('admin.partials.header', [
                'adminPageTitle' => 'Pengembalian',
                'adminPageSubtitle' => 'Kelola seluruh proses pengembalian alat',
            ])

            <main class="admin-content">

                @php
                    // ── Konfigurasi filter: pill tetap mempertahankan search/sort ──
                    $baseQuery = request()->query();
                    unset($baseQuery['filter'], $baseQuery['page']);
                    $pillUrl = function ($f) use ($baseQuery) {
                        $q = $baseQuery;
                        if ($f !== 'all') {
                            $q['filter'] = $f;
                        }
                        return route('admin.pengembalian', $q);
                    };

                    // ── Placeholder gambar (dipakai saat foto barang tidak tersedia) ──
                    $rtPlaceholder = 'data:image/svg+xml;utf8,' . rawurlencode(
                        "<svg xmlns='http://www.w3.org/2000/svg' width='160' height='160'><rect width='160' height='160' fill='%23EDF1EE'/><path d='M28 118 L58 68 L78 96 L94 66 L132 118 Z' fill='%23A9C4B4'/><path d='M16 118 h128 v8 H16 Z' fill='%23D6E3DB'/></svg>"
                    );
                @endphp

                <!-- Page Header -->
                <div class="pengembalian-header-row">
                    <div class="pengembalian-title-area">
                        <div class="logistics-label-line">PROSES PENGEMBALIAN</div>
                        <h1 class="pengembalian-main-heading">Pengembalian</h1>
                        <p class="pengembalian-subtitle">Kelola seluruh proses pengembalian alat — inspeksi, denda, hingga penyelesaian.</p>
                    </div>
                </div>

                @if (! $loadError)
                    <!-- Ringkasan Statistik (berbasis database) -->
                    <div class="rt-summary-grid">
                        <a href="{{ $pillUrl('all') }}" class="rt-summary-card {{ $filter === 'all' ? 'is-active' : '' }}">
                            <span class="rt-summary-icon rt-s-green">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><polyline points="3 3 3 8 8 8"/></svg>
                            </span>
                            <span class="rt-summary-info">
                                <strong class="rt-summary-value">{{ number_format($totalReturns, 0, ',', '.') }}</strong>
                                <span class="rt-summary-label">Total Pengembalian</span>
                            </span>
                        </a>
                        <a href="{{ $pillUrl('needs_inspection') }}" class="rt-summary-card {{ $filter === 'needs_inspection' ? 'is-active' : '' }}">
                            <span class="rt-summary-icon rt-s-blue">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            </span>
                            <span class="rt-summary-info">
                                <strong class="rt-summary-value">{{ number_format($pendingInspection, 0, ',', '.') }}</strong>
                                <span class="rt-summary-label">Menunggu Inspeksi</span>
                            </span>
                        </a>
                        <a href="{{ $pillUrl('terlambat') }}" class="rt-summary-card {{ $filter === 'terlambat' ? 'is-active' : '' }}">
                            <span class="rt-summary-icon rt-s-red">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </span>
                            <span class="rt-summary-info">
                                <strong class="rt-summary-value">{{ number_format($overdue, 0, ',', '.') }}</strong>
                                <span class="rt-summary-label">Terlambat</span>
                            </span>
                        </a>
                        <a href="{{ $pillUrl('ada_denda') }}" class="rt-summary-card {{ $filter === 'ada_denda' ? 'is-active' : '' }}">
                            <span class="rt-summary-icon rt-s-amber">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            </span>
                            <span class="rt-summary-info">
                                <strong class="rt-summary-value">{{ number_format($unpaidFines, 0, ',', '.') }}</strong>
                                <span class="rt-summary-label">Denda Belum Lunas</span>
                            </span>
                        </a>
                        <a href="{{ $pillUrl('selesai') }}" class="rt-summary-card {{ $filter === 'selesai' ? 'is-active' : '' }}">
                            <span class="rt-summary-icon rt-s-slate">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            </span>
                            <span class="rt-summary-info">
                                <strong class="rt-summary-value">{{ number_format($completedCount, 0, ',', '.') }}</strong>
                                <span class="rt-summary-label">Selesai</span>
                            </span>
                        </a>
                    </div>

                    <!-- Filter & Search Bar -->
                    <div class="pengembalian-filter-bar">
                        <div class="filter-pill-group">
                            <a href="{{ $pillUrl('all') }}" class="filter-pill-btn {{ $filter === 'all' ? 'active' : '' }}">Semua</a>
                            <a href="{{ $pillUrl('terlambat') }}" class="filter-pill-btn {{ $filter === 'terlambat' ? 'active' : '' }}">Terlambat</a>
                            <a href="{{ $pillUrl('needs_inspection') }}" class="filter-pill-btn {{ $filter === 'needs_inspection' ? 'active' : '' }}">Menunggu Inspeksi</a>
                            <a href="{{ $pillUrl('ada_denda') }}" class="filter-pill-btn {{ $filter === 'ada_denda' ? 'active' : '' }}">Ada Denda</a>
                            <a href="{{ $pillUrl('damaged') }}" class="filter-pill-btn {{ $filter === 'damaged' ? 'active' : '' }}">Rusak</a>
                            <a href="{{ $pillUrl('selesai') }}" class="filter-pill-btn {{ $filter === 'selesai' ? 'active' : '' }}">Selesai</a>
                        </div>

                        <div class="pengembalian-filter-actions">
                            <form method="GET" action="{{ route('admin.pengembalian') }}" class="sort-dropdown-wrapper pengembalian-search">
                                @if ($filter !== 'all')
                                    <input type="hidden" name="filter" value="{{ $filter }}">
                                @endif
                                @if ($sort !== 'desc')
                                    <input type="hidden" name="sort" value="{{ $sort }}">
                                @endif
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                <input type="text"
                                       name="search"
                                       value="{{ $search }}"
                                       class="sort-select-input pengembalian-search-input"
                                       placeholder="Cari pelanggan, ID pesanan, atau alat...">
                                @if ($search)
                                    <a href="{{ $pillUrl($filter === 'all' ? 'all' : $filter) }}"
                                       class="pengembalian-search-clear"
                                       title="Hapus pencarian">&times;</a>
                                @endif
                            </form>

                            <form method="GET" action="{{ route('admin.pengembalian') }}" id="sortForm" class="sort-dropdown-wrapper">
                                @if ($filter !== 'all')
                                    <input type="hidden" name="filter" value="{{ $filter }}">
                                @endif
                                @if ($search)
                                    <input type="hidden" name="search" value="{{ $search }}">
                                @endif
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="4" y1="6" x2="20" y2="6"></line>
                                    <line x1="7" y1="12" x2="17" y2="12"></line>
                                    <line x1="10" y1="18" x2="14" y2="18"></line>
                                </svg>
                                <span>Urutkan:</span>
                                <select name="sort" class="sort-select-input" onchange="this.form.submit()">
                                    <option value="desc" {{ $sort === 'desc' ? 'selected' : '' }}>Tanggal Menurun</option>
                                    <option value="asc" {{ $sort === 'asc' ? 'selected' : '' }}>Tanggal Menaik</option>
                                </select>
                            </form>
                        </div>
                    </div>
                @endif

                <!-- Daftar Kartu Pengembalian -->
                <div class="pengembalian-main-container">
                    <div class="pengembalian-list-section">

                        @if ($loadError)
                            <div class="rt-state rt-state-error">
                                <span class="rt-state-icon">
                                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                </span>
                                <h3>Gagal memuat data pengembalian</h3>
                                <p>Terjadi kesalahan saat mengambil data dari database. Silakan periksa koneksi dan coba lagi.</p>
                                <a href="{{ route('admin.pengembalian') }}" class="filter-pill-btn">Coba Lagi</a>
                            </div>
                        @else
                            @forelse ($orders as $order)
                                @php
                                    $returnRecord = $order->returns->firstWhere('order_item_id', null)
                                                    ?? $order->returns->sortByDesc('id')->first();
                                    $firstItem = $order->items->first();
                                    $product = $firstItem?->product;
                                    $itemCount = $order->items->count();
                                    $productName = $firstItem?->name ?? 'Peralatan Outdoor';
                                    if ($itemCount > 1) {
                                        $productName .= ' (+' . ($itemCount - 1) . ' lainnya)';
                                    }
                                    $productImage = $firstItem?->image ?? $product?->main_image ?? 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=300&q=80';

                                    $user = $order->user;
                                    $userName = $user?->name ?? 'Pelanggan';
                                    $userEmail = $user?->email ?? '';

                                    // ── Keterlambatan (berbasis backend) ──
                                    $actualDateObj = ($returnRecord && $returnRecord->returned_at)
                                        ? $returnRecord->returned_at
                                        : ($order->status === 'completed' ? $order->updated_at : null);
                                    $overdueInfo = $order->calculateOverdue($actualDateObj);
                                    $isOverdue = $overdueInfo['is_overdue'];
                                    $daysOverdue = (int) $overdueInfo['days_overdue'];
                                    $calculatedLateFee = (int) $overdueInfo['calculated_fee'];
                                    $latePenalty = $order->latePenalty;

                                    $expectedDate = $order->rent_end;

                                    $actualReturnText = 'Belum Dikembalikan';
                                    if ($returnRecord && $returnRecord->returned_at) {
                                        $actualReturnText = $returnRecord->returned_at->format('M d, Y') . ' • ' . $returnRecord->returned_at->format('H:i');
                                    } elseif ($order->status === 'completed') {
                                        $actualReturnText = $order->updated_at->format('M d, Y') . ' • ' . $order->updated_at->format('H:i');
                                    }

                                    $condition = $returnRecord?->condition;
                                    $hasReturned = (bool) ($returnRecord && $returnRecord->returned_at);
                                    $inspected = (bool) ($returnRecord && $condition !== null);
                                    $hasDamage = in_array($condition, ['minor_damage', 'major_damage']);

                                    // ── Status pengembalian ──
                                    // Selalu diturunkan dari data aktual di database
                                    // (ReturnRecord.returned_at, kondisi, status order),
                                    // bukan dari asumsi/teks frontend.
                                    if ($order->status === 'completed') {
                                        $conditionLabel = ($returnRecord && $condition !== null) ? $returnRecord->condition_label : 'Selesai';
                                        $conditionClass = ($returnRecord && $condition !== null) ? $returnRecord->condition_badge_class : 'condition-excellent';
                                    } elseif ($inspected) {
                                        $conditionLabel = $returnRecord->condition_label;
                                        $conditionClass = $returnRecord->condition_badge_class;
                                    } elseif ($hasReturned) {
                                        $conditionLabel = 'Menunggu Inspeksi';
                                        $conditionClass = 'condition-inspection';
                                    } else {
                                        $conditionLabel = 'Menunggu Pengembalian';
                                        $conditionClass = 'condition-waiting';
                                    }

                                    // ── Denda kerusakan (dari ReturnRecord) ──
                                    $damageDenda = ($returnRecord && (int) $returnRecord->damage_cost > 0) ? $returnRecord : null;
                                    $damageDendaPaid = $damageDenda?->is_denda_paid ?? false;

                                    // ── Pembayaran denda (FINE-*) ──
                                    $dendaPayments = $order->payments
                                        ->filter(fn ($p) => $p->is_denda_payment)
                                        ->sortByDesc('id')
                                        ->values();
                                    $pendingDendaPayment = $dendaPayments->first(fn ($p) => $p->status === 'pending');
                                    $paidDendaPayment  = $dendaPayments->first(fn ($p) => $p->status === 'success');
                                    $failedDendaPayment = $dendaPayments->first(fn ($p) => $p->status === 'failed');

                                    $damageStatusKey = 'waiting';
                                    $damageStatusLabel = 'Belum Diproses';
                                    if ($damageDenda) {
                                        if ($damageDendaPaid) {
                                            $damageStatusKey = 'lunas';
                                            $damageStatusLabel = 'Lunas & Dikonfirmasi';
                                        } elseif ($pendingDendaPayment) {
                                            $damageStatusKey = 'verify';
                                            $damageStatusLabel = 'Menunggu Verifikasi';
                                        } elseif ($failedDendaPayment) {
                                            $damageStatusKey = 'ditolak';
                                            $damageStatusLabel = 'Pembayaran Ditolak';
                                        }
                                    }

                                    // ── Sanksi keterlambatan ──
                                    $showLateBlock = $latePenalty
                                        && (int) $latePenalty->total_fee > 0
                                        && ! in_array($latePenalty->status, ['tidak_ada_sanksi', 'dibatalkan'], true);
                                    $lateStatusKey = 'waiting';
                                    $lateStatusLabel = 'Belum Diproses';
                                    if ($showLateBlock) {
                                        $lateStatusKey = match ($latePenalty->status) {
                                            'menunggu_verifikasi' => 'verify',
                                            'sudah_dibayar' => 'lunas',
                                            'menunggu_pembayaran' => 'waiting',
                                            default => 'waiting',
                                        };
                                        $lateStatusLabel = match ($latePenalty->status) {
                                            'menunggu_verifikasi' => 'Menunggu Verifikasi',
                                            'sudah_dibayar' => 'Lunas & Dikonfirmasi',
                                            'menunggu_pembayaran' => 'Menunggu Pembayaran',
                                            default => 'Belum Diproses',
                                        };
                                    }
                                    $needsLateAssignment = $daysOverdue > 0 && (! $latePenalty || $latePenalty->status === 'dibatalkan');
                                    $hasAnyDendaItem = $damageDenda || $showLateBlock || $needsLateAssignment;

                                    // ── Kelayakan tombol "Selesai" ──
                                    $isCompleted = $order->status === 'completed';

                                    $allFinesPaid = true;
                                    if ($damageDenda && ! $damageDendaPaid) {
                                        $allFinesPaid = false;
                                    }
                                    if ($latePenalty
                                        && in_array($latePenalty->status, ['menunggu_pembayaran', 'menunggu_verifikasi', 'belum_diproses'], true)
                                        && (int) $latePenalty->total_fee > 0) {
                                        $allFinesPaid = false;
                                    }

                                    $completeState = 'ready';
                                    $completeTitle = 'Selesaikan pengembalian';
                                    if ($isCompleted) {
                                        $completeState = 'done';
                                    } elseif (! $hasReturned) {
                                        $completeState = 'not-ready';
                                        $completeTitle = 'Barang belum dikembalikan oleh customer';
                                    } elseif (! $inspected) {
                                        $completeState = 'not-ready';
                                        $completeTitle = 'Rekam inspeksi pengembalian terlebih dahulu';
                                    } elseif ($needsLateAssignment) {
                                        $completeState = 'penalty';
                                        $completeTitle = 'Tetapkan sanksi keterlambatan sebelum menyelesaikan';
                                    } elseif ($latePenalty
                                        && in_array($latePenalty->status, ['menunggu_pembayaran', 'belum_diproses'], true)
                                        && (int) $latePenalty->total_fee > 0) {
                                        $completeState = 'penalty';
                                        $completeTitle = 'Kelola sanksi keterlambatan sebelum menyelesaikan';
                                    } elseif (! $allFinesPaid) {
                                        $completeState = 'not-ready';
                                        $completeTitle = $pendingDendaPayment
                                            ? 'Menunggu verifikasi pembayaran denda'
                                            : ($damageDenda && ! $damageDendaPaid ? 'Denda kerusakan belum lunas' : 'Denda belum lunas');
                                    }

                                    // ── Payload modal ──
                                    $inspectionPayload = [
                                        'order_id' => $order->id,
                                        'order_code' => $order->code,
                                        'customer_name' => $userName,
                                        'product_name' => $productName,
                                        'product_image' => $productImage,
                                        'expected_return' => $expectedDate ? $expectedDate->format('M d, Y') : '-',
                                        'actual_return' => $actualReturnText,
                                        'condition' => $condition ?? 'excellent',
                                        'inspection_note' => $returnRecord?->inspection_note ?? '',
                                        'damage_description' => $returnRecord?->damage_description ?? '',
                                        'damage_cost' => $returnRecord?->damage_cost ?? 0,
                                        'proof_url' => $returnRecord?->proof_url,
                                    ];

                                    $penaltyPayload = [
                                        'order_id' => $order->id,
                                        'order_code' => $order->code,
                                        'customer_name' => $userName,
                                        'product_name' => $productName,
                                        'product_image' => $productImage,
                                        'expected_return' => $expectedDate ? $expectedDate->format('d M Y') : '-',
                                        'actual_return' => $actualReturnText,
                                        'days_overdue' => $daysOverdue,
                                        'condition_label' => $conditionLabel,
                                        'fee_per_day' => 10000,
                                        'calculated_fee' => $calculatedLateFee,
                                        'current_status' => $latePenalty?->status ?? ($daysOverdue > 0 ? 'menunggu_pembayaran' : 'tidak_ada_sanksi'),
                                        'admin_notes' => $latePenalty?->admin_notes ?? '',
                                        'can_complete_order' => in_array($order->status, ['active', 'paid'], true),
                                    ];

                                    $cardModifier = $isOverdue ? 'rt-overdue' : ($hasDamage ? 'rt-damaged' : '');
                                @endphp

                                <article class="return-card {{ $cardModifier }}" style="--i: {{ $loop->index }};">

                                    <!-- Kolom 1: Foto + Pelanggan + ID Transaksi -->
                                    <div class="rc-identity">
                                        <div class="rc-thumbs">
                                            <div class="rc-thumb-main">
                                                <img src="{{ $productImage }}"
                                                     alt="{{ $productName ?: 'Foto barang' }}"
                                                     class="rc-img rc-img-gear"
                                                     loading="lazy"
                                                     onerror="rtImgFallback(this)"
                                                     title="{{ $productName }}">
                                            </div>
                                            @if ($returnRecord && $returnRecord->proof_url)
                                                <button type="button"
                                                        class="rc-proof"
                                                        title="Lihat foto bukti pengembalian"
                                                        onclick="openProofViewer('{{ $returnRecord->proof_url }}')">
                                                    <img src="{{ $returnRecord->proof_url }}"
                                                         alt="Bukti pengembalian #{{ $order->code }}"
                                                         class="rc-img"
                                                         loading="lazy"
                                                         onerror="rtImgFallback(this)">
                                                    <span class="rc-proof-badge">
                                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><polyline points="9 15 21 3"/><path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"/></svg>
                                                    </span>
                                                </button>
                                            @endif
                                        </div>

                                        <div class="rc-customer">
                                            <span class="rc-label">Pelanggan</span>
                                            <div class="rc-customer-name" title="{{ $userName }}">{{ $userName }}</div>
                                            <div class="rc-customer-email" title="{{ $userEmail }}">@if ($userEmail) {{ $userEmail }} @else <span class="rc-muted">tanpa email</span> @endif</div>
                                            <div class="rc-order-code" title="ID Transaksi">#{{ $order->code }}</div>
                                            <div class="rc-product-name" title="{{ $productName }}">{{ $productName }}</div>
                                        </div>
                                    </div>

                                    <!-- Kolom 2: Tanggal & Kondisi -->
                                    <div class="rc-mid">
                                        <div class="rc-block">
                                            <span class="rc-label">Perkiraan Pengembalian</span>
                                            <div class="rc-date">{{ $expectedDate ? $expectedDate->format('M d, Y') : '-' }}</div>
                                            <span class="rc-badge {{ $isOverdue ? 'badge-late' : 'badge-ontime' }}">
                                                <span class="rc-badge-dot"></span>
                                                @if ($isOverdue)
                                                    ⏰ Terlambat {{ $daysOverdue }} Hari
                                                @else
                                                    Tepat Waktu
                                                @endif
                                            </span>
                                        </div>

                                        <div class="rc-sep"></div>

                                        <div class="rc-block">
                                            <span class="rc-label">Pengembalian Aktual</span>
                                            <div class="rc-date">{{ $actualReturnText }}</div>
                                            <span class="rc-chip {{ $conditionClass }}">
                                                @if ($condition)
                                                    @if (in_array($condition, ['excellent', 'good'], true))
                                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                    @elseif (in_array($condition, ['minor_damage', 'major_damage'], true))
                                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                                    @else
                                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                                    @endif
                                                @endif
                                                {{ $conditionLabel }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Kolom 3: Sanksi / Denda -->
                                    <div class="rc-denda">
                                        <span class="rc-label">Sanksi / Denda</span>
                                        <div class="rc-denda-list">
                                            @if ($damageDenda)
                                                <div class="rc-denda-item {{ $damageStatusKey === 'lunas' ? 'paid' : ($damageStatusKey === 'verify' ? 'verify' : 'danger') }}">
                                                    <div class="rc-denda-head">
                                                        <span class="rc-denda-type">Denda Kerusakan</span>
                                                        <span class="rc-denda-amount">Rp {{ number_format($damageDenda->damage_cost, 0, ',', '.') }}</span>
                                                    </div>
                                                    <span class="rc-denda-status st-{{ $damageStatusKey }}">{{ $damageStatusLabel }}</span>
                                                    @if ($damageStatusKey === 'lunas' && $paidDendaPayment?->proof_url)
                                                        <a href="{{ $paidDendaPayment->proof_url }}" target="_blank" rel="noopener" class="rc-denda-proof">
                                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                                            Lihat Bukti
                                                        </a>
                                                    @endif
                                                    @if ($damageStatusKey === 'ditolak' || $damageStatusKey === 'verify')
                                                        <span class="rc-denda-note {{ $damageStatusKey === 'verify' ? 'link' : '' }}">{{ $damageStatusKey === 'verify' ? 'Bukti diunggah, menunggu verifikasi admin.' : 'Pembayaran ditolak. Customer perlu mengunggah ulang.' }}</span>
                                                    @endif
                                                </div>
                                            @endif

                                            @if ($showLateBlock)
                                                <div class="rc-denda-item {{ $lateStatusKey === 'lunas' ? 'paid' : ($lateStatusKey === 'verify' ? 'verify' : 'warn') }}">
                                                    <div class="rc-denda-head">
                                                        <span class="rc-denda-type">Denda Keterlambatan</span>
                                                        <span class="rc-denda-amount">Rp {{ number_format($latePenalty->total_fee, 0, ',', '.') }}</span>
                                                    </div>
                                                    <span class="rc-denda-status st-{{ $lateStatusKey }}">{{ $lateStatusLabel }}</span>
                                                    @if ($lateStatusKey === 'lunas')
                                                        <span class="rc-denda-confirm">Dikonfirmasi: {{ $latePenalty->paid_at ? $latePenalty->paid_at->format('d M Y, H:i') : '-' }}</span>
                                                        @if (($latePenalty->payment?->proof_url ?? $paidDendaPayment?->proof_url))
                                                            <a href="{{ $latePenalty->payment?->proof_url ?? $paidDendaPayment->proof_url }}" target="_blank" rel="noopener" class="rc-denda-proof">
                                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                                                Lihat Bukti
                                                            </a>
                                                        @endif
                                                    @elseif ($lateStatusKey === 'verify')
                                                        <span class="rc-denda-note link">Bukti diunggah, menunggu verifikasi admin.</span>
                                                    @endif
                                                    @if (in_array($latePenalty->status, ['menunggu_pembayaran', 'belum_diproses', 'dibatalkan'], true))
                                                        <div class="rc-denda-buttons">
                                                            <button type="button" class="rc-btn-mini manage" onclick='openLatePenaltyModal(@json($penaltyPayload))'>
                                                                {{ in_array($latePenalty->status, ['menunggu_pembayaran', 'belum_diproses'], true) ? 'Ubah' : 'Kelola Ulang' }}
                                                            </button>
                                                            @if (in_array($latePenalty->status, ['menunggu_pembayaran', 'belum_diproses'], true))
                                                                <button type="button" class="rc-btn-mini cancel" onclick="confirmCancelLatePenalty({{ $order->id }}, '{{ addslashes($order->code) }}')">Batal</button>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                            @elseif ($needsLateAssignment)
                                                <div class="rc-denda-item warn">
                                                    <div class="rc-denda-head">
                                                        <span class="rc-denda-type">Denda Keterlambatan</span>
                                                        <span class="rc-denda-amount">Estimasi Rp {{ number_format($calculatedLateFee, 0, ',', '.') }}</span>
                                                    </div>
                                                    <span class="rc-denda-status st-waiting">Belum Diproses</span>
                                                    <div class="rc-denda-buttons">
                                                        <button type="button" class="rc-btn-mini manage" onclick='openLatePenaltyModal(@json($penaltyPayload))'>Tetapkan Sanksi</button>
                                                    </div>
                                                </div>
                                            @endif

                                            @if (! $hasAnyDendaItem)
                                                <div class="rc-denda-none">
                                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="16 10 11 15 8 12"/></svg>
                                                    Tidak Ada Sanksi
                                                </div>
                                            @endif
                                        </div>

                                        @if ($pendingDendaPayment)
                                            <div class="rc-approval">
                                                <span class="rc-approval-label">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12v10H4V12"/><path d="M2 7h20v5H2z"/><path d="M12 22V7"/><path d="M12 7h.01"/></svg>
                                                    Pembayaran Denda
                                                </span>
                                                <span class="rc-approval-amount">Rp {{ number_format($pendingDendaPayment->amount, 0, ',', '.') }}</span>
                                                <span class="rc-approval-hint">Menunggu verifikasi admin</span>
                                                @if ($pendingDendaPayment->proof_url)
                                                    <a href="{{ $pendingDendaPayment->proof_url }}" target="_blank" rel="noopener" class="rc-denda-proof">Lihat Bukti</a>
                                                @endif
                                                <div class="rc-approval-actions">
                                                    <form method="POST" action="{{ route('admin.pengembalian.approveDenda', $pendingDendaPayment->id) }}" data-lock>
                                                        @csrf
                                                        <button type="submit" class="rc-btn-approve">Setujui</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('admin.pengembalian.rejectDenda', $pendingDendaPayment->id) }}" data-lock>
                                                        @csrf
                                                        <button type="submit" class="rc-btn-reject">Tolak</button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Kolom 4: Aksi -->
                                    <div class="rc-actions">
                                        <div class="rc-action-icons">
                                            <button type="button"
                                                    class="rc-icon-btn {{ $hasDamage ? 'danger' : '' }}"
                                                    title="{{ $hasDamage ? 'Rekam / lihat laporan kerusakan' : 'Inspeksi pengembalian' }}"
                                                    onclick='openInspectionModal(@json($inspectionPayload))'>
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                                    <path d="M3.3 7 12 12l8.7-5"/><path d="M12 22V12"/>
                                                </svg>
                                            </button>
                                            @if ($returnRecord && $returnRecord->proof_url)
                                                <button type="button"
                                                        class="rc-icon-btn"
                                                        title="Lihat foto bukti pengembalian"
                                                        onclick="openProofViewer('{{ $returnRecord->proof_url }}')">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                                </button>
                                            @endif
                                        </div>

                                        <div class="rc-complete">
                                            @if ($completeState === 'done')
                                                <span class="rc-btn-done">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                    Selesai
                                                </span>
                                            @elseif ($completeState === 'penalty')
                                                <button type="button"
                                                        class="rc-btn-complete orange"
                                                        title="{{ $completeTitle }}"
                                                        onclick='openLatePenaltyModal(@json($penaltyPayload))'>
                                                    Selesai
                                                </button>
                                            @elseif ($completeState === 'not-ready')
                                                <button type="button" class="rc-btn-complete" disabled title="{{ $completeTitle }}">
                                                    Selesai
                                                </button>
                                            @else
                                                <button type="button"
                                                        class="rc-btn-complete"
                                                        title="{{ $completeTitle }}"
                                                        onclick="openCompleteModal({{ $order->id }}, '{{ addslashes($order->code) }}', '{{ addslashes($userName) }}', {{ $inspected ? 'true' : 'false' }})">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 21 1 13 4 10 9 15 20 4 23 7 9 21"/></svg>
                                                    Selesaikan
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <div class="rt-state">
                                    <span class="rt-state-icon">
                                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><polyline points="3 3 3 8 8 8"/></svg>
                                    </span>
                                    <h3>
                                        @if ($search)
                                            Tidak ada hasil untuk pencarian
                                        @elseif ($filter === 'needs_inspection')
                                            Tidak ada pengembalian yang menunggu inspeksi
                                        @elseif ($filter === 'terlambat')
                                            Tidak ada pengembalian yang terlambat
                                        @elseif ($filter === 'ada_denda')
                                            Tidak ada pengembalian dengan denda
                                        @elseif ($filter === 'damaged')
                                            Tidak ada pengembalian dengan catatan kerusakan
                                        @elseif ($filter === 'selesai')
                                            Belum ada pengembalian yang selesai
                                        @else
                                            Belum ada data pengembalian
                                        @endif
                                    </h3>
                                    <p>
                                        @if ($search)
                                            Tidak ditemukan pelanggan, ID pesanan, atau alat yang cocok dengan "{{ $search }}".
                                        @else
                                            Semua penyewaan aktif yang siap dikembalikan akan muncul di halaman ini.
                                        @endif
                                    </p>
                                    @if ($search)
                                        <a href="{{ route('admin.pengembalian') }}" class="filter-pill-btn">Tampilkan Semua Pengembalian</a>
                                    @endif
                                </div>
                            @endforelse

                            @if ($orders->hasPages() || $orders->total() > 0)
                                <nav class="pagination-nav-bar" aria-label="Pagination Pengembalian">
                                    @if ($orders->onFirstPage())
                                        <span class="page-nav-btn disabled" aria-disabled="true">Previous</span>
                                    @else
                                        <a href="{{ $orders->previousPageUrl() }}" class="page-nav-btn" rel="prev">Previous</a>
                                    @endif

                                    @foreach ($orders->getUrlRange(max(1, $orders->currentPage() - 2), min($orders->lastPage(), $orders->currentPage() + 2)) as $page => $url)
                                        @if ($page == $orders->currentPage())
                                            <span class="page-num-btn active" aria-current="page">{{ $page }}</span>
                                        @else
                                            <a href="{{ $url }}" class="page-num-btn">{{ $page }}</a>
                                        @endif
                                    @endforeach

                                    @if ($orders->lastPage() > $orders->currentPage() + 2)
                                        <span class="page-num-ellipsis">...</span>
                                        <a href="{{ $orders->url($orders->lastPage()) }}" class="page-num-btn">{{ $orders->lastPage() }}</a>
                                    @endif

                                    @if ($orders->hasMorePages())
                                        <a href="{{ $orders->nextPageUrl() }}" class="page-nav-btn" rel="next">Next</a>
                                    @else
                                        <span class="page-nav-btn disabled" aria-disabled="true">Next</span>
                                    @endif
                                </nav>
                            @endif
                        @endif
                    </div>
                </div>
            </main>

            @include('partials.footer', ['footerContext' => 'admin'])
        </div>
    </div>

    <!-- Modal: Inspeksi Pengembalian -->
    <div id="inspectionModal" class="inspection-modal-overlay" onclick="closeInspectionModal(event)">
        <div class="inspection-modal-card" onclick="event.stopPropagation()">
            <div class="inspection-modal-header">
                <div>
                    <h3 class="inspection-modal-title">Inspeksi Pengembalian</h3>
                    <p class="rt-modal-sub">Periksa dan tentukan kondisi peralatan yang dikembalikan</p>
                </div>
                <button type="button" class="inspection-modal-close-btn" onclick="hideInspectionModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="inspection-info-box">
                <img id="modalProductImage" src="" alt="Product" class="inspection-info-img" onerror="rtImgFallback(this)">
                <div style="flex: 1; min-width: 0;">
                    <div id="modalProductName" class="rt-modal-product-name">Nama Produk</div>
                    <div class="rt-modal-meta">Pelanggan: <strong id="modalCustomerName">Alex Thompson</strong></div>
                    <div class="rt-modal-meta">Pesanan: <strong id="modalOrderCode" class="rt-modal-code">#ORD-9921-X</strong></div>
                    <div class="rt-modal-sub">Diharapkan: <span id="modalExpectedReturn">Oct 24, 2023</span> • Aktual: <span id="modalActualReturn">Oct 24, 14:30</span></div>
                </div>
            </div>

            <form id="inspectionForm" method="POST" action="" enctype="multipart/form-data" data-lock>
                @csrf
                <div class="inspection-form-group">
                    <label class="inspection-form-label">Kondisi Barang Saat Dikembalikan <span style="color: #dc2626;">*</span></label>
                    <select name="condition" id="modalConditionSelect" class="inspection-form-select" onchange="toggleDamageFields(this.value)" required>
                        <option value="excellent">Sangat Baik - Kondisi sangat prima tanpa cacat</option>
                        <option value="good">Baik - Kondisi baik dan layak pakai</option>
                        <option value="needs_cleaning">Perlu Pembersihan - Memerlukan pembersihan standar</option>
                        <option value="minor_damage">Kerusakan Ringan - Kerusakan ringan / lecet</option>
                        <option value="major_damage">Kerusakan Berat - Kerusakan berat / perlu servis</option>
                    </select>
                </div>

                <div id="damageFieldsGroup" style="display: none;">
                    <div class="inspection-form-group">
                        <label class="inspection-form-label">Deskripsi Kerusakan <span style="color: #dc2626;">*</span></label>
                        <textarea name="damage_description" id="modalDamageDesc" rows="2" class="inspection-form-textarea" placeholder="Jelaskan detail bagian alat yang mengalami kerusakan..."></textarea>
                    </div>
                    <div class="inspection-form-group">
                        <label class="inspection-form-label">Biaya Kerusakan / Denda (Rp)</label>
                        <input type="number" name="damage_cost" id="modalDamageCost" class="inspection-form-input" placeholder="Contoh: 50000" min="0" step="1000">
                    </div>
                </div>

                <div class="inspection-form-group">
                    <label class="inspection-form-label">Catatan Inspeksi</label>
                    <textarea name="inspection_note" id="modalInspectionNote" rows="2" class="inspection-form-textarea" placeholder="Catatan tambahan teknisi atau staf logistik..."></textarea>
                </div>

                <div class="inspection-form-group">
                    <label class="inspection-form-label">Bukti Pengembalian</label>
                    <div id="modalReturnProofBox" style="display: none;">
                        <img id="modalReturnProof" src="" alt="Foto Bukti Pengembalian" title="Klik untuk memperbesar"
                             style="display: block; max-width: 100%; max-height: 220px; border-radius: 10px; border: 1px solid #e5e7eb; cursor: zoom-in; object-fit: cover;"
                             onclick="openProofViewer(this.src)">
                        <span class="rt-modal-sub" style="margin-top: 4px;">Foto bukti yang diunggah user. Klik foto untuk memperbesar.</span>
                    </div>
                    <div id="modalReturnProofEmpty" style="font-size: 12px; color: #9CA3AF;">Tidak ada foto bukti dari user.</div>
                </div>

                <div class="inspection-form-group">
                    <label class="inspection-form-label">Unggah Foto Kondisi Barang (Opsional)</label>
                    <input type="file" name="proof_path" accept="image/jpeg,image/png,image/webp" class="inspection-form-input">
                    <span class="rt-modal-sub">Format didukung: JPG, PNG, WEBP. Maks 5MB.</span>
                </div>

                <div class="inspection-modal-actions">
                    <button type="button" class="filter-pill-btn" onclick="hideInspectionModal()">Batal</button>
                    <button type="submit" class="rc-btn-complete" style="width: auto; padding: 0 20px;">Simpan Hasil Inspeksi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Konfirmasi Selesai -->
    <div id="completeModal" class="inspection-modal-overlay" onclick="closeCompleteModal(event)">
        <div class="inspection-modal-card" style="max-width: 460px;" onclick="event.stopPropagation()">
            <div class="inspection-modal-header">
                <h3 class="inspection-modal-title">Selesaikan Pengembalian</h3>
                <button type="button" class="inspection-modal-close-btn" onclick="hideCompleteModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <p class="rt-modal-body">Apakah Anda yakin ingin menyelesaikan pengembalian pesanan <strong id="completeOrderCode" class="rt-modal-code">#ORD</strong> untuk customer <strong id="completeCustomerName">Customer</strong>?</p>
            <div class="rt-modal-note">
                ✓ Status pesanan akan diubah menjadi <strong>Selesai</strong>.<br>
                ✓ Stok alat yang dalam kondisi baik akan otomatis dikembalikan ke inventaris.
            </div>

            <form id="completeForm" method="POST" action="" data-lock>
                @csrf
                <div class="inspection-modal-actions">
                    <button type="button" class="filter-pill-btn" onclick="hideCompleteModal()">Batal</button>
                    <button type="submit" class="rc-btn-complete" style="width: auto; padding: 0 20px;">Ya, Selesaikan Pengembalian</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Sanksi Keterlambatan -->
    <div id="latePenaltyModal" class="inspection-modal-overlay" onclick="closeLatePenaltyModal(event)">
        <div class="inspection-modal-card" style="max-width: 540px;" onclick="event.stopPropagation()">
            <div class="inspection-modal-header">
                <div>
                    <h3 class="inspection-modal-title">Pemeriksaan Sanksi Keterlambatan</h3>
                    <p class="rt-modal-sub">Kelola dan tetapkan sanksi denda keterlambatan pengembalian alat</p>
                </div>
                <button type="button" class="inspection-modal-close-btn" onclick="hideLatePenaltyModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="inspection-info-box" style="background: #FEFCE8; border: 1px solid #FDE68A;">
                <img id="penaltyModalProductImage" src="" alt="Product" class="inspection-info-img" onerror="rtImgFallback(this)">
                <div style="flex: 1; min-width: 0;">
                    <div id="penaltyModalProductName" class="rt-modal-product-name">Nama Alat</div>
                    <div class="rt-modal-meta">Customer: <strong id="penaltyModalCustomerName">Nama Customer</strong> • Pesanan: <strong id="penaltyModalOrderCode" class="rt-modal-code">#ORD</strong></div>
                    <div class="rt-modal-sub">Kondisi Fisik: <strong id="penaltyModalConditionLabel">-</strong></div>
                </div>
            </div>

            <div class="rt-penalty-board">
                <div class="rt-penalty-grid">
                    <div>
                        <div class="rt-modal-sub">Pengembalian Seharusnya</div>
                        <div id="penaltyModalExpectedDate" class="rt-penalty-value">-</div>
                    </div>
                    <div>
                        <div class="rt-modal-sub">Pengembalian Aktual</div>
                        <div id="penaltyModalActualDate" class="rt-penalty-value">-</div>
                    </div>
                </div>
                <div class="rt-penalty-row">
                    <span>Keterlambatan</span>
                    <strong id="penaltyModalDaysOverdue" style="color: #DC2626;">0 Hari</strong>
                </div>
                <div class="rt-penalty-row">
                    <span>Tarif Denda per Hari</span>
                    <strong>Rp 10.000</strong>
                </div>
                <div class="rt-penalty-row rt-penalty-total">
                    <span>Total Denda Keterlambatan</span>
                    <strong id="penaltyModalTotalFee" style="color: #DC2626;">Rp 0</strong>
                </div>
            </div>

            <form id="latePenaltyForm" method="POST" action="" data-lock>
                @csrf
                <div class="inspection-form-group">
                    <label class="inspection-form-label">Tindakan Sanksi <span style="color: #dc2626;">*</span></label>
                    <select name="status" id="penaltyModalStatusSelect" class="inspection-form-select" onchange="syncPenaltyComplete(this.value)" required>
                        <option value="menunggu_pembayaran">Kenakan Denda (Menunggu Pembayaran)</option>
                        <option value="sudah_dibayar">Tandai Sudah Dibayar (Lunas)</option>
                        <option value="tidak_ada_sanksi">Bebaskan Denda (Tidak Ada Sanksi)</option>
                    </select>
                </div>

                <div class="inspection-form-group">
                    <label class="inspection-form-label">Catatan Admin (Opsional)</label>
                    <textarea name="admin_notes" id="penaltyModalAdminNotes" rows="2" class="inspection-form-textarea" placeholder="Alasan pembebasan, rincian toleransi, atau catatan instruksi untuk customer..."></textarea>
                </div>

                <div id="penaltyCompleteOrderWrap" class="rt-penalty-check">
                    <label>
                        <input type="checkbox" name="complete_order" value="1" id="penaltyModalCompleteCheck" checked>
                        <span>Selesaikan pengembalian pesanan ini dan kembalikan stok alat ke inventaris.</span>
                    </label>
                </div>

                <div class="inspection-modal-actions">
                    <button type="button" class="filter-pill-btn" onclick="hideLatePenaltyModal()">Batal</button>
                    <button type="submit" class="rc-btn-complete" style="width: auto; padding: 0 20px; background-color: #15803d;">Simpan Sanksi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Batalkan Sanksi -->
    <div id="cancelPenaltyModal" class="inspection-modal-overlay" onclick="closeCancelPenaltyModal(event)">
        <div class="inspection-modal-card" style="max-width: 440px;" onclick="event.stopPropagation()">
            <div class="inspection-modal-header">
                <h3 class="inspection-modal-title" style="color: #B91C1C;">Batalkan Sanksi Keterlambatan</h3>
                <button type="button" class="inspection-modal-close-btn" onclick="hideCancelPenaltyModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <p class="rt-modal-body">Apakah Anda yakin ingin membatalkan sanksi ini?</p>
            <div class="rt-modal-note" style="background: #FEF2F2; border-color: #FECACA; color: #991B1B;">
                Status sanksi untuk order <strong id="cancelPenaltyOrderCode" class="rt-modal-code">#ORD</strong> akan diubah menjadi <strong>Dibatalkan</strong> dan kewajiban denda keterlambatan dinonaktifkan.
            </div>

            <form id="cancelPenaltyForm" method="POST" action="" data-lock>
                @csrf
                <div class="inspection-modal-actions">
                    <button type="button" class="filter-pill-btn" onclick="hideCancelPenaltyModal()">Kembali</button>
                    <button type="submit" class="rc-btn-complete" style="width: auto; padding: 0 20px; background-color: #DC2626;">Ya, Batalkan Sanksi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Perbesar Foto Bukti -->
    <div id="proofViewer" class="inspection-modal-overlay" onclick="closeProofViewer()">
        <div style="max-width: min(92vw, 900px);" onclick="event.stopPropagation()">
            <img id="proofViewerImg" src="" alt="Foto Bukti Pengembalian"
                 style="display: block; max-width: 100%; max-height: 88vh; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.35); background: #fff;" onerror="rtImgFallback(this)">
            <div style="text-align: right; margin-top: 12px;">
                <button type="button" class="filter-pill-btn" onclick="closeProofViewer()">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Toast Notifikasi -->
    <div class="rt-toast-root" id="rtToastRoot"
         data-success="{{ session('success') }}"
         data-error="{{ session('error') }}"
         data-info="{{ session('info') }}"></div>

    <script>
        window.RTPlaceholder = @json($rtPlaceholder);

        // ── Fallback gambar agar layout tidak rusak saat foto tidak tersedia ──
        function rtImgFallback(img) {
            img.onerror = null;
            img.src = window.RTPlaceholder || '';
        }

        // ── Toast notifikasi dari flash session ──
        (function () {
            var root = document.getElementById('rtToastRoot');
            if (!root) return;
            var messages = [
                { type: 'success', text: root.dataset.success },
                { type: 'error', text: root.dataset.error },
                { type: 'info', text: root.dataset.info }
            ];
            messages.forEach(function (m) {
                if (m.text) showToast(m.type, m.text);
            });
        })();

        function showToast(type, message) {
            var icons = {
                success: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="16 10 11 15 8 12"/></svg>',
                error: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
                info: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
            };
            var el = document.createElement('div');
            el.className = 'rt-toast rt-toast-' + type;
            el.innerHTML = '<span class="rt-toast-icon">' + (icons[type] || '') + '</span>' +
                '<span class="rt-toast-text"></span>' +
                '<button type="button" class="rt-toast-close" aria-label="Tutup">&times;</button>';
            el.querySelector('.rt-toast-text').textContent = message;
            el.querySelector('.rt-toast-close').addEventListener('click', function () {
                el.classList.add('out');
                setTimeout(function () { el.remove(); }, 200);
            });
            document.body.appendChild(el);
            setTimeout(function () { el.classList.add('in'); }, 10);
            setTimeout(function () {
                el.classList.add('out');
                setTimeout(function () { el.remove(); }, 220);
            }, 5000);
        }

        // ── Cegah double-submit pada semua form aksi (POST) ──
        (function () {
            var forms = document.querySelectorAll('form[data-lock]');
            forms.forEach(function (form) {
                form.addEventListener('submit', function () {
                    if (form.dataset.locked === '1') {
                        return false;
                    }
                    form.dataset.locked = '1';
                    var btns = form.querySelectorAll('button[type="submit"]');
                    btns.forEach(function (btn) {
                        btn.disabled = true;
                        btn.classList.add('is-loading');
                        var label = btn.textContent.trim() || 'Memproses...';
                        btn.dataset.origLabel = label;
                        btn.innerHTML = '<span class="rt-spinner"></span> ' + label;
                    });
                });
            });
        })();

        // ── Inspeksi Modal ──
        function openInspectionModal(data) {
            document.getElementById('modalProductImage').src = data.product_image || window.RTPlaceholder;
            document.getElementById('modalProductName').textContent = data.product_name;
            document.getElementById('modalCustomerName').textContent = data.customer_name;
            document.getElementById('modalOrderCode').textContent = '#' + data.order_code;
            document.getElementById('modalExpectedReturn').textContent = data.expected_return;
            document.getElementById('modalActualReturn').textContent = data.actual_return;

            document.getElementById('modalConditionSelect').value = data.condition || 'excellent';
            document.getElementById('modalInspectionNote').value = data.inspection_note || '';
            document.getElementById('modalDamageDesc').value = data.damage_description || '';
            document.getElementById('modalDamageCost').value = data.damage_cost || 0;

            var proofBox = document.getElementById('modalReturnProofBox');
            var proofEmpty = document.getElementById('modalReturnProofEmpty');
            if (data.proof_url) {
                document.getElementById('modalReturnProof').src = data.proof_url;
                proofBox.style.display = 'block';
                proofEmpty.style.display = 'none';
            } else {
                proofBox.style.display = 'none';
                proofEmpty.style.display = 'block';
            }

            toggleDamageFields(data.condition || 'excellent');

            document.getElementById('inspectionForm').action = "/admin/pengembalian/" + data.order_id + "/record";
            document.getElementById('inspectionForm').dataset.locked = '0';
            document.getElementById('inspectionModal').classList.add('show');
            document.body.classList.add('rt-modal-open');
        }

        function hideInspectionModal() { closeModal('inspectionModal'); }

        function closeInspectionModal(event) {
            if (event.target === document.getElementById('inspectionModal')) hideInspectionModal();
        }

        function toggleDamageFields(condition) {
            var isDamage = condition === 'minor_damage' || condition === 'major_damage';
            document.getElementById('damageFieldsGroup').style.display = isDamage ? 'block' : 'none';
        }

        // ── Complete Confirmation Modal ──
        function openCompleteModal(orderId, orderCode, customerName, hasInspected) {
            document.getElementById('completeOrderCode').textContent = '#' + orderCode;
            document.getElementById('completeCustomerName').textContent = customerName;
            document.getElementById('completeForm').action = "/admin/pengembalian/" + orderId + "/complete";
            document.getElementById('completeForm').dataset.locked = '0';
            document.getElementById('completeModal').classList.add('show');
            document.body.classList.add('rt-modal-open');
        }

        function hideCompleteModal() { closeModal('completeModal'); }

        function closeCompleteModal(event) {
            if (event.target === document.getElementById('completeModal')) hideCompleteModal();
        }

        // ── Late Penalty Modal ──
        function openLatePenaltyModal(data) {
            document.getElementById('penaltyModalProductImage').src = data.product_image || window.RTPlaceholder;
            document.getElementById('penaltyModalProductName').textContent = data.product_name;
            document.getElementById('penaltyModalCustomerName').textContent = data.customer_name;
            document.getElementById('penaltyModalOrderCode').textContent = '#' + data.order_code;
            document.getElementById('penaltyModalConditionLabel').textContent = data.condition_label || 'Kondisi Baik';
            document.getElementById('penaltyModalExpectedDate').textContent = data.expected_return;
            document.getElementById('penaltyModalActualDate').textContent = data.actual_return;
            document.getElementById('penaltyModalDaysOverdue').textContent = data.days_overdue + ' Hari';
            document.getElementById('penaltyModalTotalFee').textContent = 'Rp ' + Number(data.calculated_fee || 0).toLocaleString('id-ID');

            var statusSelect = document.getElementById('penaltyModalStatusSelect');
            if (data.current_status === 'tidak_ada_sanksi') {
                statusSelect.value = 'tidak_ada_sanksi';
            } else if (data.current_status === 'sudah_dibayar') {
                statusSelect.value = 'sudah_dibayar';
            } else {
                statusSelect.value = 'menunggu_pembayaran';
            }

            syncPenaltyComplete(statusSelect.value);

            document.getElementById('penaltyModalAdminNotes').value = data.admin_notes || '';

            var completeWrap = document.getElementById('penaltyCompleteOrderWrap');
            if (data.can_complete_order) {
                completeWrap.style.display = 'block';
                document.getElementById('penaltyModalCompleteCheck').checked = true;
            } else {
                completeWrap.style.display = 'none';
                document.getElementById('penaltyModalCompleteCheck').checked = false;
            }

            document.getElementById('latePenaltyForm').action = "/admin/pengembalian/" + data.order_id + "/penalty";
            document.getElementById('latePenaltyForm').dataset.locked = '0';
            document.getElementById('latePenaltyModal').classList.add('show');
            document.body.classList.add('rt-modal-open');
        }

        function hideLatePenaltyModal() { closeModal('latePenaltyModal'); }

        // Saat memilih "Kenakan Denda (Menunggu Pembayaran)", order tidak boleh
        // langsung diselesaikan — checkbox auto-complete dinonaktifkan hingga
        // sanksi dituntaskan (lunas / dibebaskan).
        function syncPenaltyComplete(status) {
            var wrap = document.getElementById('penaltyCompleteOrderWrap');
            var check = document.getElementById('penaltyModalCompleteCheck');
            if (!wrap) return;
            if (status === 'menunggu_pembayaran') {
                check.checked = false;
                check.disabled = true;
                wrap.classList.add('is-disabled');
            } else {
                check.disabled = false;
                wrap.classList.remove('is-disabled');
            }
        }

        function closeLatePenaltyModal(event) {
            if (event.target === document.getElementById('latePenaltyModal')) hideLatePenaltyModal();
        }

        // ── Cancel Penalty Modal ──
        function confirmCancelLatePenalty(orderId, orderCode) {
            document.getElementById('cancelPenaltyOrderCode').textContent = '#' + orderCode;
            document.getElementById('cancelPenaltyForm').action = "/admin/pengembalian/" + orderId + "/penalty/cancel";
            document.getElementById('cancelPenaltyForm').dataset.locked = '0';
            document.getElementById('cancelPenaltyModal').classList.add('show');
            document.body.classList.add('rt-modal-open');
        }

        function hideCancelPenaltyModal() { closeModal('cancelPenaltyModal'); }

        function closeCancelPenaltyModal(event) {
            if (event.target === document.getElementById('cancelPenaltyModal')) hideCancelPenaltyModal();
        }

        // ── Utility ──
        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
            document.body.classList.remove('rt-modal-open');
        }

        function openProofViewer(src) {
            if (!src) return;
            document.getElementById('proofViewerImg').src = src;
            document.getElementById('proofViewer').classList.add('show');
            document.body.classList.add('rt-modal-open');
        }

        function closeProofViewer() {
            document.getElementById('proofViewer').classList.remove('show');
            document.getElementById('proofViewerImg').removeAttribute('src');
            document.body.classList.remove('rt-modal-open');
        }

        // Tutup modal dengan tombol Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.inspection-modal-overlay.show').forEach(function (m) {
                    m.classList.remove('show');
                });
                document.body.classList.remove('rt-modal-open');
            }
        });
    </script>
</body>
</html>