<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}">
    <title>Notifikasi - Summit Station</title>
    <link rel="stylesheet" href="{{ asset('css/summit-admin.css') . '?v=' . time() }}">
    <link rel="stylesheet" href="{{ asset('css/summit-footer.css') . '?v=' . filemtime(public_path('css/summit-footer.css')) }}">
</head>
<body>

    <!-- Blue Top Accent Line -->
    <div class="top-banner-line"></div>

    <div class="admin-layout">
        {{-- Sidebar (global) --}}
        @include('admin.partials.sidebar', ['activeMenu' => 'notifications'])

        <div class="admin-main">
            {{-- Header (global, memuat lonceng notifikasi) --}}
            @include('admin.partials.header', [
                'adminPageTitle' => 'Notifikasi',
                'adminPageSubtitle' => 'Riwayat aktivitas sistem',
            ])

            <main class="admin-content">
                @if (session('status'))
                    <div class="admin-flash-status">{{ session('status') }}</div>
                @endif

                <!-- ─── Page Heading & Filter Tabs ─── -->
                <div class="penyewaan-header-row">
                    <div class="penyewaan-title-block">
                        <h1 class="penyewaan-main-heading">Riwayat Notifikasi</h1>
                        <p class="penyewaan-subtitle">
                            Semua notifikasi aktivitas terbaru untuk tim admin.
                        </p>
                    </div>

                    <div class="penyewaan-actions-right">
                        <div class="filter-tab-pill-group">
                            <a href="{{ route('admin.notifications.index', array_merge(request()->query(), ['filter' => 'all'])) }}"
                               class="filter-tab-btn {{ $filter === 'all' ? 'active' : '' }}">Semua</a>
                            <a href="{{ route('admin.notifications.index', array_merge(request()->query(), ['filter' => 'unread'])) }}"
                               class="filter-tab-btn {{ $filter === 'unread' ? 'active' : '' }}">Belum Dibaca</a>
                            <a href="{{ route('admin.notifications.index', array_merge(request()->query(), ['filter' => 'read'])) }}"
                               class="filter-tab-btn {{ $filter === 'read' ? 'active' : '' }}">Sudah Dibaca</a>
                        </div>

                        @if (($adminUnreadCount ?? 0) > 0)
                            <form method="POST" action="{{ route('admin.notifications.readAll') }}" style="margin: 0;">
                                @csrf
                                <button type="submit" class="btn-notif-mark-all">Tandai semua dibaca</button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- ─── Daftar Notifikasi ─── -->
                <div class="notif-history-card">
                    @forelse ($notifications as $notif)
                        @php
                            $rawData = $notif->data;
                            $data = is_array($rawData)
                                ? $rawData
                                : (array) json_decode((string) $rawData, true);
                            $title = $data['title'] ?? 'Notifikasi';
                            $body = $data['body'] ?? '';
                            $icon = $data['icon'] ?? '🔔';
                            $link = route('admin.notifications.open', $notif->id);
                        @endphp
                        <a href="{{ $link }}" class="admin-notif-item {{ $notif->read_at ? '' : 'unread' }}">
                            <div class="admin-notif-item-icon">{{ $icon }}</div>
                            <div class="admin-notif-item-content">
                                <div class="admin-notif-item-title">
                                    @if (! $notif->read_at)<span class="admin-notif-dot"></span>@endif
                                    {{ $title }}
                                </div>
                                <div class="admin-notif-item-body">{{ $body }}</div>
                                <div class="admin-notif-item-time">
                                    {{ $notif->created_at ? $notif->created_at->format('d M Y, H:i') . ' • ' . $notif->created_at->diffForHumans() : '' }}
                                </div>
                            </div>
                            @unless ($notif->read_at)
                                <span class="notif-unread-tag">Baru</span>
                            @endunless
                        </a>
                    @empty
                        <div class="notif-history-empty">
                            <div class="notif-history-empty-icon">🔔</div>
                            <h3>Belum ada notifikasi</h3>
                            <p>
                                @if ($filter === 'unread')
                                    Tidak ada notifikasi yang belum dibaca. Bagus!
                                @elseif ($filter === 'read')
                                    Belum ada notifikasi yang sudah dibaca.
                                @else
                                    Notifikasi aktivitas penyewaan, pembayaran, pengembalian, dan refund akan muncul di sini.
                                @endif
                            </p>
                        </div>
                    @endforelse

                    <!-- ─── Pagination ─── -->
                    @if ($notifications->hasPages())
                        <div class="pagination-container-row" style="border-top: 1px solid #f1f5f9; margin-top: 0; padding-top: 16px;">
                            <div class="results-counter-text">
                                Menampilkan {{ $notifications->firstItem() ?? 1 }} hingga {{ $notifications->lastItem() ?? count($notifications) }} dari {{ $notifications->total() }} notifikasi
                            </div>

                            <div class="pagination-pages-list">
                                @if ($notifications->onFirstPage())
                                    <span class="page-nav-link" style="opacity: 0.4; cursor: not-allowed;">&lsaquo;</span>
                                @else
                                    <a href="{{ $notifications->previousPageUrl() }}" class="page-nav-link">&lsaquo;</a>
                                @endif

                                @foreach ($notifications->getUrlRange(1, $notifications->lastPage()) as $page => $url)
                                    @if ($page == $notifications->currentPage())
                                        <span class="page-nav-link active">{{ $page }}</span>
                                    @else
                                        <a href="{{ $url }}" class="page-nav-link">{{ $page }}</a>
                                    @endif
                                @endforeach

                                @if ($notifications->hasMorePages())
                                    <a href="{{ $notifications->nextPageUrl() }}" class="page-nav-link">&rsaquo;</a>
                                @else
                                    <span class="page-nav-link" style="opacity: 0.4; cursor: not-allowed;">&rsaquo;</span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </main>

            <!-- Footer -->
            @include('partials.footer', ['footerContext' => 'admin'])
        </div>
    </div>

</body>
</html>
