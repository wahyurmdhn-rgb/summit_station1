<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}">
    <title>Riwayat Penyewaan - Summit Station</title>
    <link rel="stylesheet" href="{{ asset('css/summit-history.css') . '?v=' . filemtime(public_path('css/summit-history.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-footer.css') . '?v=' . filemtime(public_path('css/summit-footer.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-navbar.css') . '?v=' . filemtime(public_path('css/summit-navbar.css')) }}">
</head>
<body>
    @include('layouts.navbar')

    <main class="history-page">
        @if (session('status'))
            <div style="background: #dcfce7; border: 1px solid #86efac; color: #15803d; padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; font-size: 13px;">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div style="background: #fee2e2; border: 1px solid #f5b5b5; color: #b91c1c; padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; font-size: 13px;">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="history-hero">
            <div>
                <p class="eyebrow">PORTAL ANGGOTA</p>
                <h1>Riwayat Penyewaan</h1>
                <p>Pantau ekspedisi Anda yang sedang berjalan dan kelola riwayat penyewaan peralatan outdoor Summit Station.</p>
            </div>
            <div class="history-stats">
                <div><span>Sewa Aktif</span><strong>{{ sprintf('%02d', $activeCount) }}</strong></div>
                <div><span>Selesai</span><strong>{{ sprintf('%02d', $completedCount) }}</strong></div>
            </div>
        </section>

        <section class="history-toolbar">
            <div class="history-tabs">
                <a href="{{ route('history', ['status' => 'all']) }}" class="{{ $filter === 'all' ? 'active' : '' }}">
                    Semua Pesanan
                </a>
                <a href="{{ route('history', ['status' => 'active']) }}" class="{{ $filter === 'active' ? 'active' : '' }}">
                    Aktif
                    @if ($activeCount > 0)
                        <span class="tab-counter">{{ sprintf('%02d', $activeCount) }}</span>
                    @endif
                </a>
                <a href="{{ route('history', ['status' => 'completed']) }}" class="{{ $filter === 'completed' ? 'active' : '' }}">
                    Selesai
                    @if ($completedCount > 0)
                        <span class="tab-counter">{{ sprintf('%02d', $completedCount) }}</span>
                    @endif
                </a>
            </div>
            <form method="GET" action="{{ route('history') }}" class="history-search">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="M21 21l-4.3-4.3"></path>
                </svg>
                <input type="search" name="search" value="{{ $search }}" placeholder="Cari nomor pesanan atau barang...">
            </form>
        </section>

        <section class="order-cards-container">
            @forelse ($orders as $order)
                @php
                    $firstItem = $order->items->first();
                    $itemsCount = $order->items->count();
                    $productTitle = $firstItem ? $firstItem->name : 'Penyewaan Peralatan';
                    if ($itemsCount > 1) {
                        $productTitle .= ' (+' . ($itemsCount - 1) . ' item lainnya)';
                    }
                    $productImage = $firstItem && $firstItem->image ? $firstItem->image : 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=300&q=80';
                    $payment = $order->payments->first();
                    $successPayment = $order->payments->first(fn ($p) => $p->status === 'success');
                    $returnState = $order->returnState();
                    $hasOpenReturn = $order->returns->first(fn ($r) => in_array($r->status, ['pending', 'approved']));

                    // Status tampilan: jika user sudah mengirim bukti pengembalian,
                    // label "Waktunya Dikembalikan" / "Terlambat" berubah jadi
                    // "Menunggu Konfirmasi Admin".
                    $returnDisplayState = $returnState;
                    if ($hasOpenReturn && in_array($returnState['state'], ['due', 'overdue'])) {
                        $returnDisplayState = [
                            'state'   => 'waiting',
                            'label'   => 'Menunggu Konfirmasi Admin',
                            'badge'   => 'pending',
                            'message' => 'Bukti pengembalian Anda telah dikirim dan sedang menunggu konfirmasi admin.',
                        ];
                    }
                    $latestRefund = $order->refunds->sortByDesc('created_at')->first();
                    $hasActiveRefund = $order->refunds->contains(fn ($r) => in_array($r->status, ['pending', 'approved']));
                    $hasCompletedRefund = $order->refunds->contains('status', 'completed');
                    $canRequestRefund = in_array($order->status, ['active', 'paid'])
                        && ! $hasActiveRefund && ! $hasCompletedRefund;

                    // Denda Kerusakan Fisik (data dari return_records)
                    $returnRecord = $order->returns->firstWhere('order_item_id', null)
                                    ?? $order->returns->sortByDesc('id')->first();
                    $hasDamageDenda = $returnRecord ? (int) $returnRecord->damage_cost > 0 : false;
                    $damageDendaPaid = $returnRecord && $returnRecord->is_denda_paid;
                    $damageDendaAmount = $returnRecord ? (int) $returnRecord->damage_cost : 0;
                    $damageDendaReason = $returnRecord ? trim((string) $returnRecord->damage_description) : '';
                    $damageDendaSanction = $returnRecord ? trim((string) $returnRecord->inspection_note) : '';
                    $damageDendaStatusLabel = $returnRecord ? $returnRecord->denda_status_label : '';

                    // Sanksi Keterlambatan Pengembalian (Late Penalty)
                    $latePenalty = $order->latePenalty;
                    $hasLatePenalty = $latePenalty && $latePenalty->days_overdue > 0 && $latePenalty->status !== 'tidak_ada_sanksi';
                    $latePenaltyPaid = $latePenalty && $latePenalty->is_paid;
                    $latePenaltyAmount = ($hasLatePenalty && $latePenalty->status !== 'dibatalkan') ? (int) $latePenalty->total_fee : 0;
                    $latePenaltyStatusLabel = $latePenalty ? $latePenalty->status_label : '';

                    // Pending verifikasi bukti denda
                    $dendaPaymentPending = $order->payments->contains(
                        fn ($p) => $p->is_denda_payment && $p->status === 'pending'
                    );
                    $isPenaltyVerifying = ($latePenalty && $latePenalty->status === 'menunggu_verifikasi')
                                        || (! $latePenaltyPaid && $dendaPaymentPending);

                    // Status gabungan & total unpaid
                    $hasAnyDenda = $hasDamageDenda || ($hasLatePenalty && $latePenaltyAmount > 0);
                    $unpaidDendaTotal = ($hasDamageDenda && ! $damageDendaPaid ? $damageDendaAmount : 0)
                                      + ($hasLatePenalty && ! $latePenaltyPaid && ! $isPenaltyVerifying ? $latePenaltyAmount : 0);

                    // Timeline Step Calculations
                    $step1 = ['status' => 'completed', 'label' => 'Booking Dibuat', 'desc' => $order->created_at ? $order->created_at->format('d M, H:i') : 'Selesai'];

                    if (in_array($order->status, ['cancelled', 'rejected'])) {
                        $step2 = ['status' => 'cancelled', 'label' => 'Pembayaran', 'desc' => 'Dibatalkan'];
                        $step3 = ['status' => 'upcoming', 'label' => 'Konfirmasi Admin', 'desc' => '-'];
                        $step4 = ['status' => 'upcoming', 'label' => 'Masa Sewa', 'desc' => '-'];
                        $step5 = ['status' => 'upcoming', 'label' => 'Pengembalian & Selesai', 'desc' => '-'];
                    } elseif ($order->status === 'pending') {
                        $step2 = ['status' => 'current', 'label' => 'Pembayaran', 'desc' => 'Menunggu Pembayaran'];
                        $step3 = ['status' => 'upcoming', 'label' => 'Konfirmasi Admin', 'desc' => 'Menunggu Verifikasi'];
                        $step4 = ['status' => 'upcoming', 'label' => 'Masa Sewa', 'desc' => $order->rent_start ? $order->rent_start->format('d M') : 'Mendatang'];
                        $step5 = ['status' => 'upcoming', 'label' => 'Pengembalian & Selesai', 'desc' => 'Jadwal Sesuai Kalender'];
                    } elseif ($order->status === 'paid') {
                        $step2 = ['status' => 'completed', 'label' => 'Pembayaran', 'desc' => 'Berhasil'];
                        $step3 = ['status' => 'current', 'label' => 'Konfirmasi Admin', 'desc' => 'Pengecekan Admin'];
                        $step4 = ['status' => 'upcoming', 'label' => 'Masa Sewa', 'desc' => $order->rent_start ? $order->rent_start->format('d M') : 'Mendatang'];
                        $step5 = ['status' => 'upcoming', 'label' => 'Pengembalian & Selesai', 'desc' => 'Jadwal Sesuai Kalender'];
                    } elseif ($order->status === 'active') {
                        $step2 = ['status' => 'completed', 'label' => 'Pembayaran', 'desc' => 'Lunas'];
                        $step3 = ['status' => 'completed', 'label' => 'Konfirmasi Admin', 'desc' => 'Disetujui'];
                        $step4 = ['status' => 'current', 'label' => 'Masa Sewa', 'desc' => 'Sedang Digunakan'];
                        if ($hasOpenReturn) {
                            $step5 = ['status' => 'current', 'label' => 'Pengembalian', 'desc' => 'Menunggu Konfirmasi Admin'];
                        } elseif ($returnState['state'] === 'due' || $returnState['state'] === 'overdue') {
                            $step5 = ['status' => 'alert', 'label' => 'Pengembalian', 'desc' => $returnState['state'] === 'overdue' ? 'Batas Lewat!' : 'Hari Ini!'];
                        } else {
                            $step5 = ['status' => 'upcoming', 'label' => 'Pengembalian & Selesai', 'desc' => $order->rent_end ? 'Batas: ' . $order->rent_end->format('d M') : 'Menunggu'];
                        }
                    } else { // completed / returned
                        $step2 = ['status' => 'completed', 'label' => 'Pembayaran', 'desc' => 'Lunas'];
                        $step3 = ['status' => 'completed', 'label' => 'Konfirmasi Admin', 'desc' => 'Disetujui'];
                        $step4 = ['status' => 'completed', 'label' => 'Masa Sewa', 'desc' => 'Selesai'];
                        $step5 = ['status' => 'completed', 'label' => 'Pengembalian & Selesai', 'desc' => 'Barang Diterima & Beres'];
                    }
                    $timelineSteps = [$step1, $step2, $step3, $step4, $step5];
                @endphp

                <article class="order-card" data-status="{{ $order->status }}" style="--order-idx: {{ $loop->index }};">
                    <!-- Card Header -->
                    <div class="order-card-header">
                        <div class="order-header-meta">
                            <span class="order-code-chip">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 7V4h16v3M9 20h6M12 4v16"></path>
                                </svg>
                                #{{ $order->code }}
                            </span>
                            <span class="order-date-chip">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                {{ $order->created_at ? $order->created_at->format('d M Y, H:i') : '-' }} WIB
                            </span>
                            <span class="order-duration-chip">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                {{ $order->rentalDays() }} Hari Sewa
                            </span>
                        </div>

                        <div class="order-badges-wrap">
                            @if ($order->status === 'active')
                                <span class="badge-status badge-active">
                                    <span class="badge-dot pulse-green"></span>
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M2 20h20M5 20L12 4l7 16M12 4v16"></path>
                                    </svg>
                                    Sedang Disewa
                                </span>
                            @elseif ($order->status === 'completed')
                                <span class="badge-status badge-completed">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    Selesai
                                </span>
                            @elseif ($order->status === 'pending')
                                <span class="badge-status badge-pending">
                                    <span class="badge-dot pulse-amber"></span>
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    Menunggu Pembayaran
                                </span>
                            @elseif ($order->status === 'paid')
                                <span class="badge-status badge-paid">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    Sudah Dibayar
                                </span>
                            @elseif ($order->status === 'cancelled')
                                <span class="badge-status badge-cancelled">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="15" y1="9" x2="9" y2="15"></line>
                                        <line x1="9" y1="9" x2="15" y2="15"></line>
                                    </svg>
                                    Dibatalkan
                                </span>
                            @elseif ($order->status === 'returned')
                                <span class="badge-status badge-returned">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="9 14 4 9 9 4"></polyline>
                                        <path d="M20 20v-7a4 4 0 0 0-4-4H4"></path>
                                    </svg>
                                    Barang Dikembalikan
                                </span>
                            @else
                                <span class="badge-status badge-default">
                                    {{ ucfirst($order->status) }}
                                </span>
                            @endif

                            @if ($hasDamageDenda)
                                <span class="badge-status {{ $damageDendaPaid ? 'badge-completed' : 'badge-denda' }}" title="{{ $damageDendaPaid ? 'Denda kerusakan lunas' : 'Denda kerusakan belum lunas' }}">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="2" y="6" width="20" height="12" rx="2"></rect>
                                        <circle cx="12" cy="12" r="2.5"></circle>
                                        <path d="M6 6V4h12v2"></path>
                                    </svg>
                                    Denda Kerusakan
                                </span>
                            @endif

                            @if ($hasLatePenalty)
                                @if ($latePenaltyPaid)
                                    <span class="badge-status badge-completed" title="Sanksi keterlambatan lunas" style="background: #f0fdf4; color: #166534; border-color: #bbf7d0;">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        Denda Keterlambatan: Lunas
                                    </span>
                                @elseif ($isPenaltyVerifying)
                                    <span class="badge-status" title="Pembayaran sanksi keterlambatan sedang diverifikasi admin" style="background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe;">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        Denda: Menunggu Verifikasi
                                    </span>
                                @else
                                    <span class="badge-status badge-denda" title="Sanksi keterlambatan menunggu pembayaran" style="background: #fff1f2; color: #e11d48; border-color: #fecdd3;">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        {{ $latePenalty->days_overdue }} Hari Terlambat
                                    </span>
                                @endif
                            @endif

                            @if ($hasOpenReturn && $order->status !== 'completed')
                                <span class="badge-status badge-return-pending">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
                                    </svg>
                                    Pengajuan Pengembalian
                                </span>
                            @endif

                            @if (in_array($returnDisplayState['state'], ['due', 'overdue', 'waiting']))
                                <span class="badge-status badge-{{ $returnDisplayState['badge'] }}">
                                    @if ($returnDisplayState['state'] === 'waiting')
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                    @else
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                            <line x1="12" y1="9" x2="12" y2="13"></line>
                                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                        </svg>
                                    @endif
                                    {{ $returnDisplayState['label'] }}
                                </span>
                            @endif

                            @if ($latestRefund)
                                @php
                                    $refundBadgeClass = match ($latestRefund->status) {
                                        'pending'   => 'badge-refund-pending',
                                        'approved'  => 'badge-refund-approved',
                                        'rejected'  => 'badge-refund-rejected',
                                        'completed' => 'badge-refund-completed',
                                        default     => 'badge-default',
                                    };
                                @endphp
                                <span class="badge-status {{ $refundBadgeClass }}">
                                    Refund: {{ $latestRefund->status_label }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="order-card-body">
                        <!-- Product Preview Column -->
                        <div class="order-product-col">
                            <div class="order-thumb-wrap">
                                <img src="{{ $productImage }}" alt="{{ $productTitle }}" class="order-thumb-img">
                                @if ($itemsCount > 1)
                                    <span class="thumb-count-overlay">+{{ $itemsCount - 1 }}</span>
                                @endif
                            </div>
                            <div class="order-product-info">
                                <h2 class="order-product-title">{{ $productTitle }}</h2>
                                <div class="order-rental-period">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    <span>{{ $order->rent_start ? $order->rent_start->format('d M Y') : '-' }} &mdash; {{ $order->rent_end ? $order->rent_end->format('d M Y') : '-' }}</span>
                                </div>
                                @if (!empty($order->delivery_method))
                                    <div class="order-delivery-meta">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            @if ($order->delivery_method === 'delivery')
                                                <path d="M1 3h15v13H1z"></path>
                                                <path d="M16 8h4l3 3v5h-7V8z"></path>
                                                <circle cx="5.5" cy="18.5" r="2.5"></circle>
                                                <circle cx="18.5" cy="18.5" r="2.5"></circle>
                                            @else
                                                <path d="M4 10h16l-1.5 11h-13L4 10z"></path>
                                                <path d="M2 7l2-4h16l2 4H2z"></path>
                                                <path d="M12 10v11"></path>
                                            @endif
                                        </svg>
                                        <span>
                                            @if ($order->delivery_method === 'delivery')
                                                Dikirim ke: {{ $order->delivery_address ?? '-' }}
                                            @else
                                                Ambil di Tempat (Summit Station)
                                            @endif
                                        </span>
                                    </div>
                                @endif
                                <div class="order-items-summary">
                                    <span class="items-count-tag">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                        </svg>
                                        {{ $itemsCount }} Produk Peralatan
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing & Payment Column -->
                        <div class="order-price-col">
                            <span class="price-caption">Total Pembayaran</span>
                            <div class="price-val">Rp {{ number_format($order->total, 0, ',', '.') }}</div>
                            <div class="payment-summary-pill">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                    <line x1="1" y1="10" x2="23" y2="10"></line>
                                </svg>
                                <span>{{ $payment?->formatted_method ?? 'QRIS' }}</span>
                                <span class="sep-dot">&bull;</span>
                                @if ($successPayment || $order->status === 'paid' || in_array($order->status, ['active', 'completed', 'returned']))
                                    <strong class="text-paid">Lunas</strong>
                                @elseif ($order->status === 'pending')
                                    <strong class="text-unpaid">Belum Bayar</strong>
                                @else
                                    <strong>{{ ucfirst($order->status) }}</strong>
                                @endif
                            </div>
                        </div>

                        <!-- Action Buttons Column -->
                        <div class="order-actions-col">
                            <button type="button" class="btn-order-action btn-toggle-drawer" id="drawer-btn-{{ $order->id }}" onclick="toggleOrderDrawer('{{ $order->id }}')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                                <span class="drawer-btn-text">Detail Pesanan</span>
                                <svg class="chevron-toggle" id="drawer-chevron-{{ $order->id }}" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>

                            <button type="button" class="btn-order-action btn-proof" onclick="showProofModal('{{ $order->code }}', '{{ number_format($order->total, 0, ',', '.') }}', '{{ $payment?->formatted_method ?? 'QRIS' }}', '{{ $payment?->trx_code ?? '#TRX-' . $order->code }}', '{{ $order->created_at ? $order->created_at->format('M d, Y H:i') : '-' }}', '{{ $payment?->proof_url ?? '' }}')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                </svg>
                                Bukti Bayar
                            </button>

                            @if ($canRequestRefund)
                                <button type="button" class="btn-order-action btn-refund" onclick="openRefundModal('{{ $order->id }}', '{{ $order->code }}', '{{ addslashes($productTitle) }}', '{{ number_format($order->total, 0, ',', '.') }}')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 10h4l3-6 4 12 3-6h4"></path>
                                        <path d="M13 21c-4 0-8-1.5-10-4"></path>
                                    </svg>
                                    Ajukan Refund
                                </button>
                            @endif

                            @if ($order->status === 'active' && ! $hasOpenReturn)
                                <button type="button" class="btn-order-action btn-return" onclick="showReturnModal('{{ $order->id }}', '{{ $order->code }}', '{{ addslashes($firstItem ? $firstItem->name : 'Penyewaan Peralatan') }}', '{{ $itemsCount }}', '{{ $order->rent_end ? $order->rent_end->format('d M Y') : '' }}')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="9 14 4 9 9 4"></polyline>
                                        <path d="M20 20v-7a4 4 0 0 0-4-4H4"></path>
                                    </svg>
                                    Kembalikan Barang
                                </button>
                            @elseif (in_array($order->status, ['completed', 'returned']))
                                @if ($order->review)
                                    <div class="reviewed-badge" title="Ulasan: {{ $order->review->comment }}">
                                        <div class="reviewed-stars">
                                            @for ($s = 1; $s <= 5; $s++)
                                                @if ($s <= $order->review->rating)
                                                    ★
                                                @else
                                                    <span class="star-empty">★</span>
                                                @endif
                                            @endfor
                                        </div>
                                        <span class="reviewed-label">Sudah Diulas</span>
                                    </div>
                                @else
                                    <button type="button" class="btn-order-action btn-rating" onclick="openReviewModal('{{ $order->id }}', '{{ $firstItem?->product_id }}', '{{ addslashes($productTitle) }}', '{{ $order->code }}')">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="#eab308" stroke="#eab308" stroke-width="1.5">
                                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                        </svg>
                                        Beri Rating
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>

                    <!-- Return Alert Banner (if applicable) -->
                    @if (in_array($returnDisplayState['state'], ['due', 'overdue', 'waiting']))
                        <div class="order-card-alert alert-{{ $returnDisplayState['badge'] }}">
                            <span class="alert-icon">{{ $returnDisplayState['state'] === 'overdue' ? '🚨' : ($returnDisplayState['state'] === 'waiting' ? '⏳' : '⚠️') }}</span>
                            <div class="alert-content">
                                <strong>{{ $returnDisplayState['label'] }}</strong>
                                <p>
                                    {{ $returnDisplayState['message'] }}
                                    @if ($returnDisplayState['state'] !== 'waiting')
                                        Batas pengembalian: <strong>{{ $order->rent_end ? $order->rent_end->format('d M Y') : '-' }}</strong>
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endif

                    <!-- Expandable Drawer -->
                    <div class="order-detail-drawer" id="order-drawer-{{ $order->id }}" hidden>
                        <!-- 1. Visual Transaction Timeline -->
                        <div class="drawer-section timeline-section">
                            <h3 class="section-heading">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                Progress & Tahapan Rental
                            </h3>
                            <div class="timeline-container">
                                <div class="timeline-track">
                                    @foreach ($timelineSteps as $idx => $step)
                                        <div class="timeline-step step-{{ $step['status'] }}">
                                            <div class="step-indicator">
                                                @if ($step['status'] === 'completed')
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                        <polyline points="20 6 9 17 4 12"></polyline>
                                                    </svg>
                                                @elseif ($step['status'] === 'current')
                                                    <span class="current-pulse"></span>
                                                @elseif ($step['status'] === 'alert')
                                                    <span class="alert-pulse">!</span>
                                                @elseif ($step['status'] === 'cancelled')
                                                    &times;
                                                @else
                                                    <span class="upcoming-dot"></span>
                                                @endif
                                            </div>
                                            <div class="step-content">
                                                <div class="step-title">{{ $step['label'] }}</div>
                                                <div class="step-desc">{{ $step['desc'] }}</div>
                                            </div>
                                            @if ($idx < count($timelineSteps) - 1)
                                                <div class="step-line line-{{ $step['status'] === 'completed' ? 'done' : 'pending' }}"></div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- 2. Breakdown Grid (3 Columns) -->
                        <div class="drawer-breakdown-grid">
                            <!-- Col 1: Rincian Peralatan -->
                            <div class="breakdown-card">
                                <div class="breakdown-card-head">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                    </svg>
                                    <h4>Rincian Peralatan</h4>
                                    <span class="head-badge">{{ $order->items->count() }} Produk</span>
                                </div>
                                <div class="breakdown-item-list">
                                    @foreach ($order->items as $item)
                                        <div class="breakdown-item-row">
                                            <img src="{{ $item->image ?: 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=100&q=80' }}" alt="{{ $item->name }}" class="breakdown-item-thumb">
                                            <div class="breakdown-item-detail">
                                                <h5>{{ $item->name }}</h5>
                                                <div class="breakdown-item-sub">
                                                    <span>{{ $item->quantity }}x</span>
                                                    <span>&bull;</span>
                                                    <span>{{ $item->days ?? $order->rentalDays() }} Hari</span>
                                                    <span>&bull;</span>
                                                    <span>@ Rp {{ number_format($item->unit_price, 0, ',', '.') }}</span>
                                                </div>
                                            </div>
                                            <div class="breakdown-item-price">
                                                Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if ($returnRecord && $returnRecord->proof_url)
                                    <div class="breakdown-return-proof">
                                        <div class="breakdown-return-proof-head">
                                            <span class="breakdown-return-proof-title">Bukti Pengembalian Barang</span>
                                            <span class="breakdown-return-proof-date">
                                                {{ $returnRecord->returned_at ? 'Dikembalikan ' . $returnRecord->returned_at->format('d M Y') : ($returnRecord->status === 'approved' ? 'Diterima Admin' : 'Menunggu Verifikasi') }}
                                            </span>
                                        </div>
                                        <button type="button" class="breakdown-return-proof-img" onclick="window.open('{{ $returnRecord->proof_url }}', '_blank')" title="Klik untuk melihat foto bukti pengembalian">
                                            <img src="{{ $returnRecord->proof_url }}" alt="Foto bukti pengembalian #{{ $order->code }}" loading="lazy">
                                            <span class="breakdown-return-proof-zoom">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><polyline points="9 15 21 3"/><path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"/></svg>
                                            </span>
                                        </button>
                                    </div>
                                @endif
                            </div>

                            <!-- Col 2: Rincian Pembayaran -->
                            <div class="breakdown-card">
                                <div class="breakdown-card-head">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                                        <line x1="2" y1="10" x2="22" y2="10"></line>
                                    </svg>
                                    <h4>Rincian Pembayaran</h4>
                                </div>
                                <div class="breakdown-price-list">
                                    <div class="price-line">
                                        <span>Subtotal Sewa</span>
                                        <span>Rp {{ number_format($order->subtotal ?: $order->items->sum('subtotal'), 0, ',', '.') }}</span>
                                    </div>
                                    @if ($order->service_fee > 0)
                                        <div class="price-line">
                                            <span>Biaya Layanan & Perawatan</span>
                                            <span>Rp {{ number_format($order->service_fee, 0, ',', '.') }}</span>
                                        </div>
                                    @endif
                                    @if ($order->discount > 0)
                                        <div class="price-line discount">
                                            <span>Diskon Promo</span>
                                            <span>- Rp {{ number_format($order->discount, 0, ',', '.') }}</span>
                                        </div>
                                    @endif
                                    @if ($hasDamageDenda)
                                        <div class="price-line" style="color: #b91c1c;">
                                            <span>Biaya Kerusakan Barang</span>
                                            <span>+ Rp {{ number_format($damageDendaAmount, 0, ',', '.') }}</span>
                                        </div>
                                    @endif
                                    @if ($hasLatePenalty && $latePenaltyAmount > 0)
                                        <div class="price-line" style="color: #c2410c;">
                                            <span>Denda Keterlambatan ({{ $latePenalty->days_overdue }} Hari)</span>
                                            <span>+ Rp {{ number_format($latePenaltyAmount, 0, ',', '.') }}</span>
                                        </div>
                                    @endif
                                    <div class="price-line total-line">
                                        <strong>Total Biaya</strong>
                                        <strong class="final-total">Rp {{ number_format($order->total + ($hasDamageDenda ? $damageDendaAmount : 0) + ($hasLatePenalty ? $latePenaltyAmount : 0), 0, ',', '.') }}</strong>
                                    </div>
                                    <div class="breakdown-payment-box">
                                        <div class="bp-label">Metode Pembayaran:</div>
                                        <div class="bp-val">
                                            <span class="bp-method">{{ $payment?->formatted_method ?? 'QRIS' }}</span>
                                            <span class="bp-code">{{ $payment?->trx_code ?? '#TRX-' . $order->code }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Col 3: Panduan & Basecamp -->
                            <div class="breakdown-card">
                                <div class="breakdown-card-head">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                    <h4>Pengambilan & Pengembalian</h4>
                                </div>
                                <div class="breakdown-guide-body">
                                    <div class="guide-item">
                                        <span class="guide-badge-loc">Basecamp Summit Station</span>
                                        @php
                                            $guideAddress = $siteSettings['address'] ?? '';
                                            $guideParts = array_map('trim', explode(',', $guideAddress));
                                        @endphp
                                        <p class="guide-address-text">
                                            @if (count($guideParts) >= 5)
                                                <strong>{{ $guideParts[0] }}</strong><br>
                                                {{ implode(', ', array_slice($guideParts, 1, 3)) }}<br>
                                                {{ implode(', ', array_slice($guideParts, 4)) }}
                                            @else
                                                {{ $guideAddress }}
                                            @endif
                                        </p>
                                    </div>
                                    <div class="guide-contact-row">
                                        <a href="https://wa.me/6282244556677" target="_blank" rel="noopener noreferrer" class="btn-wa-guide">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                            </svg>
                                            Hubungi WhatsApp Basecamp
                                        </a>
                                    </div>
                                    <div class="guide-warning-tip">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="12" y1="8" x2="12" y2="12"></line>
                                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                        </svg>
                                        <span>Periksa fisik perlengkapan saat serah terima dan pastikan dalam kondisi bersih saat dikembalikan.</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3b. Sanksi Keterlambatan Pengembalian (jika ada) -->
                        @if ($hasLatePenalty)
                            @if ($latePenaltyPaid)
                                {{-- 1. KARTU SUKSES/LUNAS (HIJAU) --}}
                                <div class="denda-card" style="border-color: #86efac; background: #f0fdf4; margin-top: 18px;">
                                    <div class="denda-card-head" style="border-bottom-color: #bbf7d0;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        <h4 style="color: #166534;">Sanksi Keterlambatan Pengembalian</h4>
                                        <span class="denda-status-chip denda-paid" style="background: #dcfce7; color: #15803d; border-color: #86efac;">
                                            Lunas
                                        </span>
                                    </div>

                                    <div class="denda-body">
                                        <div class="denda-field">
                                            <span class="denda-label" style="color: #166534;">Keterlambatan</span>
                                            <strong class="denda-value" style="color: #15803d;">{{ $latePenalty->days_overdue }} Hari</strong>
                                        </div>
                                        <div class="denda-field">
                                            <span class="denda-label" style="color: #166534;">Tarif Denda</span>
                                            <span class="denda-text" style="font-weight: 700; color: #1e293b;">Rp {{ number_format($latePenalty->fee_per_day, 0, ',', '.') }} / hari</span>
                                        </div>
                                        <div class="denda-field">
                                            <span class="denda-label" style="color: #166534;">Total Denda Keterlambatan</span>
                                            <strong class="denda-value" style="color: #15803d;">Rp {{ number_format($latePenalty->total_fee, 0, ',', '.') }}</strong>
                                        </div>
                                        <div class="denda-field">
                                            <span class="denda-label" style="color: #166534;">Status Pembayaran</span>
                                            <span class="denda-text denda-text-paid" style="color: #15803d; font-weight: 700;">
                                                Pembayaran Berhasil
                                            </span>
                                        </div>
                                        <div class="denda-field denda-field-full" style="background: #eafaf1; padding: 10px 14px; border-radius: 8px; border: 1px solid #bbf7d0; margin-top: 4px;">
                                            <span style="color: #15803d; font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 6px;">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                Pembayaran denda telah dikonfirmasi oleh admin.
                                            </span>
                                        </div>
                                        @if ($latePenalty->admin_notes)
                                            <div class="denda-field denda-field-full">
                                                <span class="denda-label" style="color: #166534;">Catatan Admin</span>
                                                <span class="denda-text">{{ $latePenalty->admin_notes }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @elseif ($isPenaltyVerifying)
                                {{-- 2. KARTU MENUNGGU VERIFIKASI (BIRU) --}}
                                <div class="denda-card" style="border-color: #bfdbfe; background: #eff6ff; margin-top: 18px;">
                                    <div class="denda-card-head" style="border-bottom-color: #dbeafe;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        <h4 style="color: #1e40af;">Sanksi Keterlambatan Pengembalian</h4>
                                        <span class="denda-status-chip" style="background: #dbeafe; color: #1d4ed8; border-color: #93c5fd;">
                                            Menunggu Verifikasi
                                        </span>
                                    </div>

                                    <div class="denda-body">
                                        <div class="denda-field">
                                            <span class="denda-label" style="color: #1e40af;">Keterlambatan</span>
                                            <strong class="denda-value" style="color: #1d4ed8;">{{ $latePenalty->days_overdue }} Hari</strong>
                                        </div>
                                        <div class="denda-field">
                                            <span class="denda-label" style="color: #1e40af;">Tarif Denda</span>
                                            <span class="denda-text" style="font-weight: 700; color: #1e293b;">Rp {{ number_format($latePenalty->fee_per_day, 0, ',', '.') }} / hari</span>
                                        </div>
                                        <div class="denda-field">
                                            <span class="denda-label" style="color: #1e40af;">Total Denda Keterlambatan</span>
                                            <strong class="denda-value" style="color: #1d4ed8;">Rp {{ number_format($latePenalty->total_fee, 0, ',', '.') }}</strong>
                                        </div>
                                        <div class="denda-field">
                                            <span class="denda-label" style="color: #1e40af;">Status Pembayaran</span>
                                            <span class="denda-text" style="color: #2563eb; font-weight: 700;">
                                                Menunggu Verifikasi
                                            </span>
                                        </div>
                                        @if ($latePenalty->admin_notes)
                                            <div class="denda-field denda-field-full">
                                                <span class="denda-label" style="color: #1e40af;">Catatan Admin</span>
                                                <span class="denda-text">{{ $latePenalty->admin_notes }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                {{-- 3. KARTU MENUNGGU PEMBAYARAN (MERAH) --}}
                                <div class="denda-card" style="border-color: #fca5a5; background: #fff5f5; margin-top: 18px;">
                                    <div class="denda-card-head" style="border-bottom-color: #fecdd3;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        <h4 style="color: #991b1b;">Sanksi Keterlambatan Pengembalian</h4>
                                        <span class="denda-status-chip denda-unpaid">
                                            Menunggu Pembayaran
                                        </span>
                                    </div>

                                    <div class="denda-body">
                                        <div class="denda-field">
                                            <span class="denda-label" style="color: #991b1b;">Keterlambatan</span>
                                            <strong class="denda-value" style="color: #dc2626;">{{ $latePenalty->days_overdue }} Hari</strong>
                                        </div>
                                        <div class="denda-field">
                                            <span class="denda-label" style="color: #991b1b;">Tarif Denda</span>
                                            <span class="denda-text" style="font-weight: 700; color: #1e293b;">Rp {{ number_format($latePenalty->fee_per_day, 0, ',', '.') }} / hari</span>
                                        </div>
                                        <div class="denda-field">
                                            <span class="denda-label" style="color: #991b1b;">Total Denda Keterlambatan</span>
                                            <strong class="denda-value" style="color: #dc2626;">Rp {{ number_format($latePenalty->total_fee, 0, ',', '.') }}</strong>
                                        </div>
                                        <div class="denda-field">
                                            <span class="denda-label" style="color: #991b1b;">Status Pembayaran</span>
                                            <span class="denda-text denda-text-unpaid">
                                                Menunggu Pembayaran
                                            </span>
                                        </div>
                                        @if ($latePenalty->admin_notes)
                                            <div class="denda-field denda-field-full">
                                                <span class="denda-label" style="color: #991b1b;">Catatan Admin</span>
                                                <span class="denda-text">{{ $latePenalty->admin_notes }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endif

                        <!-- 3c. Denda Kerusakan Fisik (jika ada) -->
                        @if ($hasDamageDenda)
                            <div class="denda-card" style="margin-top: 18px;">
                                <div class="denda-card-head">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="2" y="6" width="20" height="12" rx="2"></rect>
                                        <circle cx="12" cy="12" r="2.5"></circle>
                                        <path d="M6 6V4h12v2"></path>
                                    </svg>
                                    <h4>Denda Kerusakan Peralatan</h4>
                                    <span class="denda-status-chip {{ $damageDendaPaid ? 'denda-paid' : 'denda-unpaid' }}">
                                        {{ $damageDendaStatusLabel }}
                                    </span>
                                </div>

                                <div class="denda-body">
                                    <div class="denda-field">
                                        <span class="denda-label">Nominal Kerusakan</span>
                                        <strong class="denda-value">Rp {{ number_format($damageDendaAmount, 0, ',', '.') }}</strong>
                                    </div>
                                    <div class="denda-field">
                                        <span class="denda-label">Kondisi Barang</span>
                                        <span class="denda-text" style="font-weight: 700; color: #1e293b;">{{ $returnRecord->condition_label }}</span>
                                    </div>
                                    <div class="denda-field denda-field-full">
                                        <span class="denda-label">Deskripsi Kerusakan</span>
                                        <span class="denda-text">{{ $damageDendaReason ?: '-' }}</span>
                                    </div>
                                    <div class="denda-field">
                                        <span class="denda-label">Status Pembayaran</span>
                                        <span class="denda-text {{ $damageDendaPaid ? 'denda-text-paid' : 'denda-text-unpaid' }}">
                                            {{ $damageDendaStatusLabel }}
                                        </span>
                                    </div>
                                    <div class="denda-field denda-field-full">
                                        <span class="denda-label">Catatan Inspeksi Staf</span>
                                        <span class="denda-text">{{ $damageDendaSanction ?: '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Aksi Pembayaran Denda (Jika Ada Denda Keterlambatan / Kerusakan) -->
                        @if ($hasAnyDenda)
                            <div style="margin-top: 14px; display: flex; flex-direction: column; gap: 8px;">
                                @if ($dendaPaymentPending || $isPenaltyVerifying)
                                    <div class="denda-waiting-box">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        Bukti pembayaran denda sedang diverifikasi admin.
                                    </div>
                                @elseif ($unpaidDendaTotal <= 0)
                                    <div class="denda-paid-box">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        Semua denda untuk pesanan ini telah LUNAS.
                                    </div>
                                @else
                                    <div style="display: flex; align-items: center; justify-content: space-between; background: #fffbeb; border: 1px solid #fef08a; padding: 12px 16px; border-radius: 10px;">
                                        <div>
                                            <div style="font-size: 11px; font-weight: 700; color: #854d0e; text-transform: uppercase;">Total Denda yang Harus Dibayar:</div>
                                            <div style="font-size: 16px; font-weight: 800; color: #b45309;">Rp {{ number_format($unpaidDendaTotal, 0, ',', '.') }}</div>
                                        </div>
                                        <button type="button" class="btn-order-action btn-denda" onclick="openPayDendaModal('{{ $order->id }}', '{{ $order->code }}', '{{ number_format($unpaidDendaTotal, 0, ',', '.') }}')">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                                <line x1="1" y1="10" x2="23" y2="10"></line>
                                            </svg>
                                            Bayar Denda
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="history-empty-card">
                    <div class="empty-illustration">
                        <svg width="120" height="120" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="60" cy="60" r="56" fill="#f0f7f2" stroke="#d5e6da" stroke-width="2" />
                            <!-- Mountain silhouettes -->
                            <path d="M22 84L46 48L64 74L78 56L98 84H22Z" fill="#cfe3d4" />
                            <path d="M42 84L60 58L72 74L84 62L98 84H42Z" fill="#b1d1bc" />
                            <path d="M46 48L53 58.5L46 62L39 58.5L46 48Z" fill="#ffffff" />
                            <path d="M78 56L83 63L78 66L73 63L78 56Z" fill="#ffffff" />
                            <!-- Tent -->
                            <polygon points="50,84 65,60 80,84" fill="#185d31" />
                            <polygon points="65,60 65,84 78,84" fill="#2d7748" />
                            <polygon points="61,84 65,72 69,84" fill="#e9ba62" />
                            <!-- Pine Trees -->
                            <polygon points="28,84 34,74 40,84" fill="#1e4e30" />
                            <polygon points="30,76 34,68 38,76" fill="#2d7748" />
                            <polygon points="86,84 92,75 98,84" fill="#1e4e30" />
                            <!-- Stars / Moon -->
                            <circle cx="88" cy="34" r="7" fill="#facc15" />
                            <circle cx="85" cy="32" r="6" fill="#f0f7f2" />
                            <circle cx="34" cy="38" r="1.5" fill="#facc15" />
                            <circle cx="48" cy="32" r="1.5" fill="#facc15" />
                            <circle cx="70" cy="40" r="1.5" fill="#facc15" />
                        </svg>
                    </div>
                    <h2>Belum Ada Petualangan Dimulai</h2>
                    <p>Peralatan outdoor dan pendakian terbaik siap menemani setiap langkah ekspedisi Anda ke puncak impian. Temukan peralatan yang Anda butuhkan sekarang!</p>
                    <a href="{{ route('catalog') }}" class="btn-empty-explore">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                            <polyline points="2 17 12 22 22 17"></polyline>
                            <polyline points="2 12 12 17 22 12"></polyline>
                        </svg>
                        Jelajahi Alat Outdoor Sekarang &rarr;
                    </a>
                </div>
            @endforelse

            <div class="table-foot">
                <p>Menampilkan {{ $orders->total() }} pesanan</p>
            </div>

            @if ($orders->hasPages())
                <div class="history-pagination">
                    {{ $orders->links('pagination::custom') }}
                </div>
            @endif
        </section>

        <section class="next-peak">
            <div>
                <h2>Merencanakan pendakian berikutnya?</h2>
                <p>Lihat koleksi alat pendakian kami dan pilih perlengkapan yang sesuai untuk perjalanan Anda.</p>
            </div>
            <a href="{{ route('catalog') }}">Jelajahi Katalog</a>
        </section>
    </main>

    <!-- Modal Bukti Pembayaran -->
    <div class="payment-proof-modal" id="payment-proof-modal" hidden>
        <div class="payment-proof-backdrop" onclick="closeProofModal()"></div>
        <section class="payment-proof-dialog" role="dialog" aria-modal="true">
            <button type="button" class="payment-proof-close" onclick="closeProofModal()">&times;</button>

            <div class="receipt-brand">
                <div>
                    <h2>Summit Station</h2>
                    <p>Penyewaan Peralatan Outdoor</p>
                </div>
                <div class="receipt-badge">LUNAS</div>
            </div>

            <div class="receipt-store-address" id="modal-store-address">
                <span class="receipt-store-name">Basecamp Summit Station</span>
                <span class="receipt-store-street">{{ $siteSettings['address'] ?? '' }}</span>
            </div>

            <div class="receipt-divider"></div>

            <div class="receipt-row">
                <span class="receipt-label">KODE PESANAN</span>
                <span class="receipt-val" id="modal-order-code">#RS-0000</span>
            </div>

            <div class="receipt-row">
                <span class="receipt-label">TOTAL PEMBAYARAN</span>
                <span class="receipt-val total" id="modal-amount">Rp 0</span>
            </div>

            <div class="receipt-row">
                <span class="receipt-label">METODE</span>
                <span class="receipt-val" id="modal-method">QRIS Dynamic</span>
            </div>

            <div class="receipt-row">
                <span class="receipt-label">KODE TRANSAKSI</span>
                <span class="receipt-val" id="modal-trx-code">#TRX-0000</span>
            </div>

            <div class="receipt-row">
                <span class="receipt-label">WAKTU BAYAR</span>
                <span class="receipt-val" id="modal-time">-</span>
            </div>

            <div class="receipt-row">
                <span class="receipt-label">STATUS</span>
                <span class="receipt-val status-verified">&check; Terverifikasi Otomatis</span>
            </div>

            <div class="receipt-proof-section">
                <span class="receipt-label">BUKTI PEMBAYARAN</span>
                <div class="receipt-proof" id="modal-proof-area" hidden>
                    <img id="modal-proof-img" src="" alt="Bukti Pembayaran">
                </div>
                <p class="receipt-proof-empty" id="modal-proof-empty" hidden>Bukti pembayaran tidak tersedia.</p>
            </div>

            <div class="receipt-actions">
                <button type="button" class="btn-receipt-print" onclick="printReceipt()">Cetak Bukti</button>
                <button type="button" class="btn-receipt-close" onclick="closeProofModal()">Tutup</button>
            </div>
        </section>
    </div>

    <!-- Modal Pengembalian Barang -->
    <div class="return-modal" id="return-modal" hidden>
        <div class="return-backdrop" onclick="closeReturnModal()"></div>
        <section class="return-dialog" role="dialog" aria-modal="true" aria-labelledby="return-title">
            <button type="button" class="return-close" onclick="closeReturnModal()" aria-label="Tutup modal">&times;</button>

            <header class="return-heading">
                <div class="return-heading-top">
                    <span class="return-heading-ico" aria-hidden="true">&#128230;</span>
                    <h2 id="return-title">Pengembalian Barang</h2>
                </div>
                <p class="return-subtitle">Bawa peralatan sewa ke basecamp Summit Station terdekat untuk pemeriksaan kondisi oleh tim logistik kami.</p>
            </header>

            <div class="return-info-card">
                <div class="return-info-row">
                    <div class="return-info-head">
                        <span class="return-ico" aria-hidden="true">&#128230;</span>
                        <span class="return-info-label">Paket</span>
                    </div>
                    <div class="return-info-value">
                        <span id="return-package-name">-</span>
                        <span class="return-package-extra" id="return-package-extra"></span>
                    </div>
                </div>
                <div class="return-info-row">
                    <div class="return-info-head">
                        <span class="return-ico" aria-hidden="true">&#128279;</span>
                        <span class="return-info-label">Kode Pesanan</span>
                    </div>
                    <div class="return-info-value return-code" id="return-order-code">#RS-0000</div>
                </div>
                <div class="return-info-row" id="return-return-date-row" hidden>
                    <div class="return-info-head">
                        <span class="return-ico" aria-hidden="true">&#128197;</span>
                        <span class="return-info-label">Tanggal Pengembalian</span>
                    </div>
                    <div class="return-info-value" id="return-return-date">-</div>
                </div>
                <div class="return-info-row" id="return-return-time-row" hidden>
                    <div class="return-info-head">
                        <span class="return-ico" aria-hidden="true">&#128336;</span>
                        <span class="return-info-label">Waktu Pengembalian</span>
                    </div>
                    <div class="return-info-value" id="return-return-time">-</div>
                </div>
            </div>

            <p class="return-note">
                Silakan bawa peralatan sewa ke basecamp Summit Station terdekat untuk dilakukan inspeksi kondisi oleh tim logistik kami.
            </p>

            <div class="return-guide">
                <h4>Petunjuk Pengembalian</h4>
                <ul>
                    <li>Pastikan semua komponen dan pasak tenda lengkap.</li>
                    <li>Bersihkan debu atau kotoran kasar pada perlengkapan.</li>
                    <li>Ambil foto barang sebagai bukti telah dikembalikan.</li>
                </ul>
            </div>

            <form method="POST" id="return-form" action="" enctype="multipart/form-data" onsubmit="return validateReturnForm(event)">
                @csrf
                <input type="hidden" name="order_id" id="return-order-id" value="">

                <div class="return-upload">
                    <label class="return-upload-label" for="return-proof-input">
                        <span class="return-ico" aria-hidden="true">&#128247;</span>
                        <span>Foto Bukti Pengembalian <span style="color: #dc2626;">*</span></span>
                    </label>
                    <p class="return-upload-hint">Upload foto barang sebagai bukti bahwa barang telah dikembalikan.</p>
                    <input type="file" name="return_proof" id="return-proof-input" accept="image/jpeg,image/jpg,image/png,image/webp" class="return-upload-input" onchange="previewReturnProof(this)">
                    <div class="return-preview" id="return-preview" hidden>
                        <img id="return-preview-img" alt="Preview Foto Barang">
                        <div class="return-preview-actions">
                            <button type="button" class="return-preview-remove" onclick="clearReturnProof()">Ganti Foto</button>
                        </div>
                    </div>
                    <span class="return-format-note">Format didukung: JPG, JPEG, PNG, WEBP. Maksimal 5MB.</span>
                    <p class="return-upload-error" id="return-upload-error" hidden>Foto bukti pengembalian wajib diupload.</p>
                </div>

                <div class="return-actions">
                    <button type="button" class="return-cancel" onclick="closeReturnModal()">Tutup</button>
                    <button type="submit" class="return-submit">Kirim Pengembalian</button>
                </div>

                <p style="text-align: center; margin-top: 14px;">
                    <a href="{{ route('store.location') }}" style="color: #1f6b38; font-size: 12.5px; font-weight: 700; text-decoration: none;">Lihat Lokasi Basecamp &rarr;</a>
                </p>
            </form>
        </section>
    </div>

    <!-- Modal Ajukan Refund -->
    <div class="payment-proof-modal" id="refund-modal" hidden>
        <div class="payment-proof-backdrop" onclick="closeRefundModal()"></div>
        <section class="payment-proof-dialog" role="dialog" style="max-width: 500px;">
            <button type="button" class="payment-proof-close" onclick="closeRefundModal()">&times;</button>
            <div style="text-align: center; margin-bottom: 20px;">
                <div style="width: 48px; height: 48px; border-radius: 50%; background: #fef3c7; color: #b45309; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 22px;">
                    &#128184;
                </div>
                <h2 style="font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">Ajukan Refund</h2>
                <p id="refund-order-info" style="font-size: 13px; color: #64748b;"></p>
                <p id="refund-order-amount" style="font-size: 16px; font-weight: 800; color: #166534; margin-top: 6px;"></p>
            </div>

            <form method="POST" id="refund-form" action="">
                @csrf
                <input type="hidden" name="order_id" id="refund-order-id" value="">

                <div style="margin-bottom: 16px;">
                    <label for="refund-reason" style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">ALASAN REFUND</label>
                    <select name="reason" id="refund-reason" required style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 11px 12px; font-family: inherit; font-size: 13px; color: #1e293b; background: #fff; box-sizing: border-box;">
                        @foreach (\App\Http\Controllers\RefundController::REFUND_REASONS as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom: 20px;">
                    <label for="refund-description" style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">KETERANGAN <span style="color:#dc2626;">*</span></label>
                    <textarea name="description" id="refund-description" rows="4" required placeholder="Jelaskan alasan Anda mengajukan refund. Contoh: tidak jadi menggunakan barang..." style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; font-family: inherit; font-size: 13px; color: #1e293b; resize: vertical; box-sizing: border-box;"></textarea>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeRefundModal()" style="padding: 10px 18px; border: 1px solid #cbd5e1; background: #fff; color: #475569; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer;">
                        Batal
                    </button>
                    <button type="submit" style="padding: 10px 22px; background: #b45309; color: #fff; border: none; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer;">
                        Kirim Pengajuan Refund
                    </button>
                </div>
            </form>
        </section>
    </div>

    <!-- Modal Bayar Denda -->
    <div class="payment-proof-modal" id="pay-denda-modal" hidden>
        <div class="payment-proof-backdrop" onclick="closePayDendaModal()"></div>
        <section class="payment-proof-dialog" role="dialog" style="max-width: 500px;">
            <button type="button" class="payment-proof-close" onclick="closePayDendaModal()">&times;</button>

            <div class="denda-modal-head">
                <div class="denda-modal-icon">&#128184;</div>
                <h2>Bayar Denda</h2>
                <p id="pay-denda-order-info" style="font-size: 13px; color: #64748b;"></p>
                <p id="pay-denda-amount" style="font-size: 18px; font-weight: 800; color: #b45309; margin-top: 6px;"></p>
            </div>

            <form method="POST" id="pay-denda-form" action="" enctype="multipart/form-data" onsubmit="return validatePayDendaForm(event)">
                @csrf

                <div class="return-upload">
                    <label class="return-upload-label" for="denda-proof-input">
                        <span class="return-ico" aria-hidden="true">&#128247;</span>
                        <span>Upload Bukti Pembayaran Denda <span style="color: #dc2626;">*</span></span>
                    </label>
                    <p class="return-upload-hint">Upload foto bukti transfer pembayaran denda. Status akan menjadi Lunas setelah diverifikasi admin.</p>
                    <input type="file" name="denda_proof" id="denda-proof-input" accept="image/jpeg,image/jpg,image/png,image/webp" class="return-upload-input" onchange="previewDendaProof(this)">
                    <div class="return-preview" id="denda-preview" hidden>
                        <img id="denda-preview-img" alt="Preview Bukti Pembayaran Denda">
                        <div class="return-preview-actions">
                            <button type="button" class="return-preview-remove" onclick="clearDendaProof()">Ganti Foto</button>
                        </div>
                    </div>
                    <span class="return-format-note">Format didukung: JPG, JPEG, PNG, WEBP. Maksimal 5MB.</span>
                    <p class="return-upload-error" id="denda-upload-error" hidden>Bukti pembayaran denda wajib diupload.</p>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 18px;">
                    <button type="button" onclick="closePayDendaModal()" style="padding: 10px 18px; border: 1px solid #cbd5e1; background: #fff; color: #475569; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer;">
                        Batal
                    </button>
                    <button type="submit" id="pay-denda-submit" style="padding: 10px 22px; background: #b45309; color: #fff; border: none; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer;">
                        Kirim Bukti Pembayaran
                    </button>
                </div>
            </form>
        </section>
    </div>

    <!-- Modal Beri Rating & Review -->
    <div class="payment-proof-modal review-modal" id="review-modal" hidden>
        <div class="payment-proof-backdrop" onclick="closeReviewModal()"></div>
        <section class="payment-proof-dialog" role="dialog" aria-modal="true" aria-labelledby="review-title">
            <button type="button" class="payment-proof-close" onclick="closeReviewModal()" aria-label="Tutup modal rating">&times;</button>

            <header class="review-head">
                <span class="review-head-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path></svg>
                </span>
                <div class="review-head-text">
                    <h2 id="review-title">Beri Rating &amp; Ulasan</h2>
                    <p id="review-order-info">Apex Ultralight (#RS-000)</p>
                </div>
            </header>

            <form method="POST" action="{{ route('reviews.store') }}" id="review-form">
                @csrf
                <input type="hidden" name="order_id" id="review-order-id" value="">
                <input type="hidden" name="product_id" id="review-product-id" value="">
                <input type="hidden" name="rating" id="review-rating-val" value="5">

                <!-- Interactive Star Selector -->
                <div class="review-stars-block">
                    <span class="review-sub-label" id="review-stars-label">Pilih Rating Anda</span>
                    <div id="star-rating-container" class="review-stars" role="radiogroup" aria-labelledby="review-stars-label">
                        <button type="button" class="star-item" data-val="1" onclick="setRating(1, this)" aria-label="1 bintang - Sangat Tidak Puas" aria-pressed="false">★</button>
                        <button type="button" class="star-item" data-val="2" onclick="setRating(2, this)" aria-label="2 bintang - Tidak Puas" aria-pressed="false">★</button>
                        <button type="button" class="star-item" data-val="3" onclick="setRating(3, this)" aria-label="3 bintang - Cukup" aria-pressed="false">★</button>
                        <button type="button" class="star-item" data-val="4" onclick="setRating(4, this)" aria-label="4 bintang - Puas" aria-pressed="false">★</button>
                        <button type="button" class="star-item" data-val="5" onclick="setRating(5, this)" aria-label="5 bintang - Sangat Puas & Direkomendasikan" aria-pressed="false">★</button>
                    </div>
                    <p id="rating-label" class="review-rating-label" role="status" aria-live="polite"></p>
                </div>

                <div class="review-field">
                    <div class="review-field-head">
                        <label for="review-comment" class="review-field-label">Pengalaman Anda</label>
                        <span class="review-counter" id="review-counter">0/1000</span>
                    </div>
                    <textarea name="comment" id="review-comment" rows="4" maxlength="1000" required placeholder="Ceritakan kondisi alat, kebersihan, kemudahan penggunaan, atau pelayanan Summit Station..." aria-describedby="review-counter"></textarea>
                </div>

                <div class="review-actions">
                    <button type="button" class="review-btn review-btn--ghost" onclick="closeReviewModal()">Batal</button>
                    <button type="submit" class="review-btn review-btn--primary" id="review-submit-btn">
                        <span class="review-btn-label">Kirim Rating</span>
                    </button>
                </div>
            </form>
        </section>
    </div>

    <!-- Footer -->
    @include('partials.footer', ['footerContext' => 'user'])

    <script>
        function showProofModal(orderCode, totalAmount, method, trxCode, time, proofUrl) {
            document.getElementById('modal-order-code').textContent = '#' + orderCode;
            document.getElementById('modal-amount').textContent = 'Rp ' + totalAmount;
            document.getElementById('modal-method').textContent = method;
            document.getElementById('modal-trx-code').textContent = trxCode;
            document.getElementById('modal-time').textContent = time;

            var proofImg = document.getElementById('modal-proof-img');
            var proofArea = document.getElementById('modal-proof-area');
            var proofEmpty = document.getElementById('modal-proof-empty');
            if (proofUrl) {
                proofImg.src = proofUrl;
                proofArea.hidden = false;
                proofEmpty.hidden = true;
            } else {
                proofImg.removeAttribute('src');
                proofArea.hidden = true;
                proofEmpty.hidden = false;
            }

            document.getElementById('payment-proof-modal').hidden = false;
        }

        function closeProofModal() {
            document.getElementById('payment-proof-modal').hidden = true;
        }

        function printReceipt() {
            var dialog = document.querySelector('#payment-proof-modal .payment-proof-dialog');
            if (!dialog) return;

            var content = dialog.cloneNode(true);
            content.removeAttribute('style');
            var actions = content.querySelector('.receipt-actions');
            if (actions) actions.remove();
            var close = content.querySelector('.payment-proof-close');
            if (close) close.remove();

            var proofImg = document.getElementById('modal-proof-img');
            if (proofImg && proofImg.src) {
                var clonedImg = content.querySelector('#modal-proof-img');
                if (clonedImg) clonedImg.setAttribute('src', proofImg.src);
            }

            var cssLinks = '';
            document.querySelectorAll('link[rel="stylesheet"]').forEach(function (l) {
                if (l.href) cssLinks += '<link rel="stylesheet" href="' + l.href + '">';
            });

            var iframe = document.createElement('iframe');
            iframe.setAttribute('aria-hidden', 'true');
            iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden;';
            document.body.appendChild(iframe);

            var doc = iframe.contentWindow.document;
            doc.open();
            doc.write('<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Bukti Pembayaran</title>' + cssLinks);
            doc.write('<style>@page{margin:14mm;} html,body{margin:0;padding:0;background:#fff;} .payment-proof-dialog{max-width:380px;max-height:none;overflow:visible;margin:0 auto;box-shadow:none;border:0;} .receipt-proof img{max-width:100%; height:auto;}</style>');
            doc.write('</head><body>' + content.outerHTML + '</body></html>');
            doc.close();

            iframe.contentWindow.focus();
            var win = iframe.contentWindow;
            var waited = 0;
            function printWhenReady() {
                var imgs = win.document.images;
                var ready = true;
                for (var i = 0; i < imgs.length; i++) {
                    if (!imgs[i].complete) { ready = false; break; }
                }
                if (ready || waited >= 4000) {
                    win.print();
                    setTimeout(function () { iframe.remove(); }, 1000);
                } else {
                    waited += 250;
                    setTimeout(printWhenReady, 250);
                }
            }
            setTimeout(printWhenReady, 300);
        }

        function showReturnModal(orderId, orderCode, productName, itemsCount, returnDate, returnTime) {
            document.getElementById('return-package-name').textContent = productName;

            var extra = document.getElementById('return-package-extra');
            var count = parseInt(itemsCount, 10);
            extra.textContent = count > 1 ? '+' + (count - 1) + ' item' : '';

            document.getElementById('return-order-code').textContent = '#' + orderCode;
            document.getElementById('return-form').action = '/history/' + orderId + '/return';
            document.getElementById('return-order-id').value = orderId;
            resetReturnForm();

            var dateRow = document.getElementById('return-return-date-row');
            if (returnDate) {
                document.getElementById('return-return-date').textContent = returnDate;
                dateRow.hidden = false;
            } else {
                dateRow.hidden = true;
            }

            var timeRow = document.getElementById('return-return-time-row');
            if (returnTime) {
                document.getElementById('return-return-time').textContent = returnTime;
                timeRow.hidden = false;
            } else {
                timeRow.hidden = true;
            }

            document.getElementById('return-modal').hidden = false;
        }

        function closeReturnModal() {
            document.getElementById('return-modal').hidden = true;
        }

        function resetReturnForm() {
            var input = document.getElementById('return-proof-input');
            input.value = '';
            document.getElementById('return-preview').hidden = true;
            document.getElementById('return-upload-error').hidden = true;
        }

        function previewReturnProof(input) {
            var preview = document.getElementById('return-preview');
            var err = document.getElementById('return-upload-error');
            err.hidden = true;
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('return-preview-img').src = e.target.result;
                    preview.hidden = false;
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.hidden = true;
            }
        }

        function clearReturnProof() {
            var input = document.getElementById('return-proof-input');
            input.value = '';
            document.getElementById('return-preview').hidden = true;
            document.getElementById('return-upload-error').hidden = true;
        }

        function validateReturnForm(e) {
            var input = document.getElementById('return-proof-input');
            var err = document.getElementById('return-upload-error');
            if (!input.files || !input.files[0]) {
                err.hidden = false;
                e.preventDefault();
                return false;
            }
            err.hidden = true;
            return true;
        }

        function openReviewModal(orderId, productId, productTitle, orderCode) {
            document.getElementById('review-order-id').value = orderId;
            document.getElementById('review-product-id').value = productId || '';
            document.getElementById('review-order-info').textContent = productTitle + ' (#' + orderCode + ')';
            document.getElementById('review-comment').value = '';
            document.getElementById('review-counter').textContent = '0/1000';
            var modal = document.getElementById('review-modal');
            modal.classList.remove('is-closing');
            modal.hidden = false;
            setRating(5);
            var submitBtn = document.getElementById('review-submit-btn');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('is-loading');
                const lbl = submitBtn.querySelector('.review-btn-label');
                if (lbl) lbl.textContent = 'Kirim Rating';
            }
        }

        function closeReviewModal() {
            var modal = document.getElementById('review-modal');
            if (!modal || modal.hidden) return;
            modal.classList.add('is-closing');
            setTimeout(function () {
                modal.hidden = true;
                modal.classList.remove('is-closing');
            }, 180);
        }

        function openRefundModal(orderId, orderCode, productTitle, orderTotal) {
            document.getElementById('refund-form').action = '/history/' + orderId + '/refund';
            document.getElementById('refund-order-id').value = orderId;
            document.getElementById('refund-order-info').textContent = productTitle + ' (#' + orderCode + ')';
            document.getElementById('refund-order-amount').textContent = 'Total pembayaran: Rp ' + orderTotal;
            document.getElementById('refund-description').value = '';
            document.getElementById('refund-reason').selectedIndex = 0;
            document.getElementById('refund-modal').hidden = false;
        }

        function closeRefundModal() {
            document.getElementById('refund-modal').hidden = true;
        }

        function openPayDendaModal(orderId, orderCode, amount) {
            document.getElementById('pay-denda-form').action = '/history/' + orderId + '/pay-denda';
            document.getElementById('pay-denda-order-info').textContent = 'Pesanan #' + orderCode;
            document.getElementById('pay-denda-amount').textContent = 'Nominal: Rp ' + amount;
            resetDendaProof();
            document.getElementById('pay-denda-modal').hidden = false;
        }

        function closePayDendaModal() {
            document.getElementById('pay-denda-modal').hidden = true;
        }

        function resetDendaProof() {
            var input = document.getElementById('denda-proof-input');
            if (input) input.value = '';
            document.getElementById('denda-preview').hidden = true;
            document.getElementById('denda-upload-error').hidden = true;
        }

        function previewDendaProof(input) {
            var preview = document.getElementById('denda-preview');
            var err = document.getElementById('denda-upload-error');
            err.hidden = true;
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('denda-preview-img').src = e.target.result;
                    preview.hidden = false;
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.hidden = true;
            }
        }

        function clearDendaProof() {
            var input = document.getElementById('denda-proof-input');
            if (input) input.value = '';
            document.getElementById('denda-preview').hidden = true;
            document.getElementById('denda-upload-error').hidden = true;
        }

        function validatePayDendaForm(e) {
            var input = document.getElementById('denda-proof-input');
            var err = document.getElementById('denda-upload-error');
            if (!input.files || !input.files[0]) {
                err.hidden = false;
                e.preventDefault();
                return false;
            }
            err.hidden = true;
            return true;
        }

        const ratingLabels = {
            1: 'Sangat Tidak Puas',
            2: 'Tidak Puas',
            3: 'Cukup',
            4: 'Puas',
            5: 'Sangat Puas & Direkomendasikan'
        };

        function setRating(val, trigger) {
            document.getElementById('review-rating-val').value = val;
            const stars = document.querySelectorAll('#star-rating-container .star-item');
            stars.forEach((star, index) => {
                const active = index < val;
                star.classList.toggle('is-active', active);
                star.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
            if (trigger && trigger.classList.contains('star-item')) {
                trigger.classList.remove('is-pop');
                void trigger.offsetWidth;
                trigger.classList.add('is-pop');
            }
            const label = document.getElementById('rating-label');
            label.textContent = ratingLabels[val] || (val + '/5');
            label.classList.remove('is-swap');
            void label.offsetWidth;
            label.classList.add('is-swap');
        }

        /* ---------- Review modal: character counter, star hover, anti double submit ---------- */
        (function () {
            const reviewForm = document.getElementById('review-form');
            const reviewSubmit = document.getElementById('review-submit-btn');
            const reviewComment = document.getElementById('review-comment');
            const reviewCounter = document.getElementById('review-counter');

            if (reviewComment && reviewCounter) {
                reviewComment.addEventListener('input', function () {
                    reviewCounter.textContent = reviewComment.value.length + '/1000';
                });
            }

            if (reviewSubmit && reviewForm) {
                reviewForm.addEventListener('submit', function () {
                    reviewSubmit.disabled = true;
                    reviewSubmit.classList.add('is-loading');
                    const label = reviewSubmit.querySelector('.review-btn-label');
                    if (label) label.textContent = 'Mengirim...';
                });
            }

            const starsContainer = document.getElementById('star-rating-container');
            if (starsContainer) {
                starsContainer.addEventListener('mouseover', function (e) {
                    const star = e.target.closest('.star-item');
                    if (!star) return;
                    const hovered = parseInt(star.dataset.val, 10) || 0;
                    starsContainer.querySelectorAll('.star-item').forEach(function (s, i) {
                        s.classList.toggle('is-hover', i < hovered);
                    });
                });
                starsContainer.addEventListener('mouseleave', function () {
                    starsContainer.querySelectorAll('.star-item').forEach(function (s) {
                        s.classList.remove('is-hover');
                    });
                });
            }
        })();

        function toggleOrderDrawer(orderId) {
            const drawer = document.getElementById('order-drawer-' + orderId);
            const chevron = document.getElementById('drawer-chevron-' + orderId);
            const btn = document.getElementById('drawer-btn-' + orderId);
            if (!drawer) return;

            const isCurrentlyHidden = drawer.hidden;
            if (isCurrentlyHidden) {
                drawer.hidden = false;
                if (chevron) chevron.style.transform = 'rotate(180deg)';
                if (btn) btn.classList.add('active');
            } else {
                drawer.hidden = true;
                if (chevron) chevron.style.transform = 'rotate(0deg)';
                if (btn) btn.classList.remove('active');
            }
        }
    </script>
    <script src="{{ asset('js/summit-navbar.js') }}"></script>
</body>
</html>
