<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}">
    <title>Manajemen Pengembalian Dana - Summit Station</title>
    <link rel="stylesheet" href="{{ asset('css/summit-admin.css') . '?v=' . time() }}">
    <link rel="stylesheet" href="{{ asset('css/summit-footer.css') . '?v=' . filemtime(public_path('css/summit-footer.css')) }}">
    <style>
        .refund-badge-pending { background: #fef3c7; color: #92400e; }
        .refund-badge-approved { background: #dbeafe; color: #1e40af; }
        .refund-badge-rejected { background: #fee2e2; color: #991b1b; }
        .refund-badge-completed { background: #dcfce7; color: #166534; }
        .refund-sidebar-badge {
            margin-left: auto;
            background: #dc2626;
            color: #fff;
            border-radius: 999px;
            min-width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 800;
            padding: 0 6px;
            box-sizing: border-box;
        }
        .refund-reason-cell { max-width: 220px; font-size: 12.5px; color: #334155; }
    </style>
</head>
<body>

    <!-- Blue Top Accent Line -->
    <div class="top-banner-line"></div>

    <div class="admin-layout">
        <!-- ─── Sidebar ─── -->
        @include('admin.partials.sidebar', ['activeMenu' => 'refund'])

        <!-- ─── Main Content Area ─── -->
        <div class="admin-main">
            <!-- Header with Breadcrumb & Admin Profile -->
            @include('admin.partials.header', [
                'adminPageTitle' => 'Pengembalian Dana',
                'adminPageSubtitle' => 'Verifikasi pengembalian dana pelanggan',
            ])

            <!-- Main Content Body -->
            <main class="admin-content">
                @if (session('success'))
                    <div style="background-color: #dcfce7; border-left: 4px solid #16a34a; padding: 14px 18px; border-radius: 8px; color: #166534; font-size: 13px; font-weight: 700; margin-bottom: 20px;">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div style="background-color: #fee2e2; border-left: 4px solid #ef4444; padding: 14px 18px; border-radius: 8px; color: #991b1b; font-size: 13px; font-weight: 700; margin-bottom: 20px;">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div style="background-color: #fee2e2; border-left: 4px solid #ef4444; padding: 14px 18px; border-radius: 8px; color: #991b1b; font-size: 13px; font-weight: 700; margin-bottom: 20px;">
                        {{ $errors->first() }}
                    </div>
                @endif

                <!-- ─── 4 Statistic Cards Row ─── -->
                <div class="stats-grid-penyewaan">
                    <div class="stat-card-penyewaan">
                        <div class="stat-card-penyewaan-top">
                            <div class="stat-penyewaan-label">PENGEMBALIAN<br>DANA MENUNGGU</div>
                            <div class="stat-icon-penyewaan orange" style="width: 28px; height: 28px; border-radius: 6px;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <div class="stat-penyewaan-val" style="font-size: 42px;">{{ sprintf('%02d', $pendingCount) }}</div>
                        </div>
                        <div class="stat-subtext-note muted" style="font-size: 11px;">
                            <span>Menunggu pemeriksaan</span>
                        </div>
                    </div>

                    <div class="stat-card-penyewaan">
                        <div class="stat-card-penyewaan-top">
                            <div class="stat-penyewaan-label">DISETUJUI</div>
                            <div class="stat-icon-penyewaan" style="width: 28px; height: 28px; border-radius: 6px; background: #dbeafe; color: #1e40af;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <div class="stat-penyewaan-val" style="font-size: 42px;">{{ sprintf('%02d', $approvedCount) }}</div>
                        </div>
                        <div class="stat-subtext-note muted" style="font-size: 11px;">
                            <span>Menunggu diproses</span>
                        </div>
                    </div>

                    <div class="stat-card-penyewaan">
                        <div class="stat-card-penyewaan-top">
                            <div class="stat-penyewaan-label">SELESAI</div>
                            <div class="stat-icon-penyewaan green" style="width: 28px; height: 28px; border-radius: 6px;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <div class="stat-penyewaan-val" style="font-size: 42px;">{{ sprintf('%02d', $completedCount) }}</div>
                        </div>
                        <div class="stat-subtext-note muted" style="font-size: 11px;">
                            <span>Dana telah dikembalikan</span>
                        </div>
                    </div>

                    <div class="stat-card-penyewaan">
                        <div class="stat-card-penyewaan-top">
                            <div class="stat-penyewaan-label">TOTAL PENGEMBALIAN DANA</div>
                            <div class="stat-icon-penyewaan" style="width: 28px; height: 28px; border-radius: 6px;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                                    <line x1="2" y1="10" x2="22" y2="10"></line>
                                </svg>
                            </div>
                        </div>
                        <div>
                            @php
                                $rf = number_format($totalRefunded, 0, ',', '.');
                            @endphp
                            <div class="stat-penyewaan-val" style="font-size: 26px; line-height: 1.2;">Rp {{ $rf }}</div>
                        </div>
                        <div class="stat-subtext-note muted" style="font-size: 11px;">
                            <span>Disetujui + Selesai</span>
                        </div>
                    </div>
                </div>

                <!-- ─── Refund Table ─── -->
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; margin-top: 22px;">
                    <div class="penyewaan-header-row" style="padding: 18px 20px 0;">
                        <div class="penyewaan-title-block">
                            <h1 class="penyewaan-main-heading">Manajemen Pengembalian Dana</h1>
                            <p class="penyewaan-subtitle">
                                Kelola semua pengajuan refund dari user (data tersinkron dari database yang sama).
                            </p>
                        </div>

                        <div class="penyewaan-actions-right" style="display: flex; gap: 8px;">
                            <form method="GET" action="{{ route('admin.refund') }}" style="display: flex; gap: 8px;">
                                <select name="status" class="filter-select-input" onchange="this.form.submit()" style="padding: 8px 14px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 13px; font-weight: 700; background: #ffffff; color: #374151; cursor: pointer;">
                                    <option value="all" {{ $currentFilter === 'all' ? 'selected' : '' }}>Filter: Semua Status</option>
                                    <option value="pending" {{ $currentFilter === 'pending' ? 'selected' : '' }}>Menunggu</option>
                                    <option value="approved" {{ $currentFilter === 'approved' ? 'selected' : '' }}>Disetujui</option>
                                    <option value="rejected" {{ $currentFilter === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                                    <option value="completed" {{ $currentFilter === 'completed' ? 'selected' : '' }}>Selesai</option>
                                </select>
                                <input type="search" name="search" value="{{ $searchTerm }}" placeholder="Cari refund / user..." style="padding: 8px 12px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 13px;">
                                <button type="submit" style="padding: 8px 16px; border-radius: 8px; border: 1px solid #185d31; background: #185d31; color: #fff; font-weight: 700; font-size: 13px; cursor: pointer;">Cari</button>
                            </form>
                        </div>
                    </div>

                    <div class="alat-table-container" style="padding: 0 20px 20px;">
                        <div class="table-responsive">
                            <table class="alat-table">
                                <thead>
                                    <tr>
                                        <th>ID PENGEMBALIAN</th>
                                        <th>PESANAN</th>
                                        <th>PENGGUNA</th>
                                        <th>JUMLAH</th>
                                        <th>ALASAN</th>
                                        <th>TANGGAL</th>
                                        <th>STATUS</th>
                                        <th>AKSI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($refunds as $refund)
                                        @php
                                            $order = $refund->order;
                                            $bookTitle = $order?->items->first()?->name ?? 'Equipment Rental';
                                            if ($order && $order->items->count() > 1) {
                                                $bookTitle .= ' (+' . ($order->items->count() - 1) . ' item)';
                                            }
                                        @endphp
                                        <tr>
                                            <td>
                                                <div style="display: flex; flex-direction: column; gap: 2px;">
                                                    <span style="font-size: 13px; font-weight: 800; color: #1a1d1a; font-family: monospace;">{{ $refund->code }}</span>
                                                    <span style="font-size: 11px; color: #64748b;">#{{ $refund->id }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="display: flex; flex-direction: column; gap: 2px;">
                                                    <span style="font-size: 12.5px; font-weight: 700; color: #185d31;">#{{ $order?->code ?? '-' }}</span>
                                                    <span style="font-size: 11px; color: #64748b;">{{ $bookTitle }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="display: flex; flex-direction: column; gap: 2px;">
                                                    <span style="font-size: 13px; font-weight: 700; color: #1a1d1a;">{{ $order?->user?->name ?? '-' }}</span>
                                                    <span style="font-size: 11px; color: #64748b;">{{ $order?->user?->email ?? '' }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span style="font-size: 13px; font-weight: 800; color: #1a1d1a;">Rp {{ number_format($refund->refund_amount, 0, ',', '.') }}</span>
                                            </td>
                                            <td>
                                                <span class="refund-reason-cell">{{ $refund->reason }}</span>
                                            </td>
                                            <td>
                                                <span style="font-size: 12px; color: #64748b;">{{ $refund->created_at ? $refund->created_at->format('d M Y • H:i') : '-' }}</span>
                                            </td>
                                            <td>
                                                <span class="badge-status {{ $refund->status_badge_class }}">
                                                    @if ($refund->status === 'approved' || $refund->status === 'completed')
                                                        <span class="status-dot"></span>
                                                    @endif
                                                    {{ $refund->status_label }}
                                                </span>
                                            </td>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <a href="{{ route('admin.refund.detail', $refund->id) }}"
                                                       style="display: inline-flex; align-items: center; gap: 5px; padding: 7px 12px; background: #f8fafc; border: 1px solid #cbd5e1; color: #334155; border-radius: 8px; font-weight: 700; font-size: 12px; text-decoration: none;">
                                                        Detail
                                                    </a>
                                                    @if ($refund->status === 'pending')
                                                        <button type="button" class="action-icon-circle-btn approve" title="Setujui Refund"
                                                                onclick="openApproveModal({{ $refund->id }}, '{{ $refund->code }}', {{ $refund->refund_amount }})">
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                                <polyline points="20 6 9 17 4 12"></polyline>
                                                            </svg>
                                                        </button>
                                                        <button type="button" class="action-icon-circle-btn reject" title="Tolak Refund"
                                                                onclick="openRejectModal({{ $refund->id }}, '{{ $refund->code }}')">
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                                                <line x1="6" y1="6" x2="18" y2="18"></line>
                                                            </svg>
                                                        </button>
                                                    @elseif ($refund->status === 'approved')
                                                        <button type="button" class="action-icon-circle-btn approve" title="Selesaikan Refund (dana dikembalikan)"
                                                                onclick="openCompleteModal({{ $refund->id }}, '{{ $refund->code }}')">
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                                                <polyline points="10 9 15 14 20 9"></polyline>
                                                            </svg>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" style="text-align: center; padding: 48px 20px; color: #64748b;">
                                                Belum ada pengajuan refund yang sesuai dengan filter ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($refunds->hasPages())
                            <div class="pagination-container-row">
                                <div class="pagination-pages-list">
                                    @if ($refunds->onFirstPage())
                                        <span class="page-nav-link" style="opacity: 0.4; cursor: not-allowed;">&lsaquo;</span>
                                    @else
                                        <a href="{{ $refunds->previousPageUrl() }}" class="page-nav-link">&lsaquo;</a>
                                    @endif

                                    @foreach ($refunds->getUrlRange(1, $refunds->lastPage()) as $page => $url)
                                        @if ($page == $refunds->currentPage())
                                            <span class="page-nav-link active">{{ $page }}</span>
                                        @else
                                            <a href="{{ $url }}" class="page-nav-link">{{ $page }}</a>
                                        @endif
                                    @endforeach

                                    @if ($refunds->hasMorePages())
                                        <a href="{{ $refunds->nextPageUrl() }}" class="page-nav-link">&rsaquo;</a>
                                    @else
                                        <span class="page-nav-link" style="opacity: 0.4; cursor: not-allowed;">&rsaquo;</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </main>

            <!-- Footer -->
            @include('partials.footer', ['footerContext' => 'admin'])
        </div>
    </div>

    <!-- ─── Modal Approve Refund ─── -->
    <div id="approveModal" class="modal-overlay">
        <div class="modal-card" style="width: 480px;">
            <div class="modal-header">
                <h3 class="modal-title" style="color: #185d31;">Setujui Refund</h3>
                <button type="button" class="btn-close-modal" onclick="closeApproveModal()">&times;</button>
            </div>
            <form id="approveForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <p style="font-size: 14px; color: #374151; line-height: 1.5;">
                        Setujui refund <strong id="approve_refund_code"></strong>? User akan menerima notifikasi setelah Anda menyetujui.
                    </p>
                    <div class="form-group-modal" style="margin-top: 14px;">
                        <label class="form-label-modal">Nominal Refund (opsional penyesuaian)</label>
                        <input type="number" name="refund_amount" id="approve_refund_amount" class="form-input-modal" min="0" placeholder="Default dari database">
                    </div>
                    <div class="form-group-modal" style="margin-top: 10px;">
                        <label class="form-label-modal">Alasan Penyesuaian (opsional)</label>
                        <input type="text" name="adjustment_reason" class="form-input-modal" placeholder="Contoh: dikurangi biaya kerusakan">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" onclick="closeApproveModal()">Batal</button>
                    <button type="submit" class="btn-modal-submit">Setujui Refund</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── Modal Reject Refund ─── -->
    <div id="rejectModal" class="modal-overlay">
        <div class="modal-card" style="width: 480px;">
            <div class="modal-header">
                <h3 class="modal-title" style="color: #dc2626;">Tolak Refund</h3>
                <button type="button" class="btn-close-modal" onclick="closeRejectModal()">&times;</button>
            </div>
            <form id="rejectForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <p style="font-size: 14px; color: #374151; line-height: 1.5; margin-bottom: 12px;">
                        Tolak refund <strong id="reject_refund_code"></strong>. Alasan penolakan wajib diisi dan akan ditampilkan ke user.
                    </p>
                    <div class="form-group-modal">
                        <label class="form-label-modal">Alasan Penolakan <span style="color:#dc2626;">*</span></label>
                        <textarea name="reject_reason" class="form-input-modal" rows="3" required placeholder="Contoh: Pengajuan melewati batas waktu refund."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" onclick="closeRejectModal()">Batal</button>
                    <button type="submit" class="btn-modal-submit" style="background-color: #dc2626;">Tolak Refund</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── Modal Complete Refund ─── -->
    <div id="completeModal" class="modal-overlay">
        <div class="modal-card" style="width: 440px;">
            <div class="modal-header">
                <h3 class="modal-title" style="color: #185d31;">Selesaikan Refund</h3>
                <button type="button" class="btn-close-modal" onclick="closeCompleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p style="font-size: 14px; color: #374151; line-height: 1.5;">
                    Tandai refund <strong id="complete_refund_code"></strong> sebagai selesai? Pastikan dana sudah benar-benar dikembalikan ke user secara manual sebelum melanjutkan.
                </p>
            </div>
            <div class="modal-footer">
                <form id="completeForm" method="POST" action="">
                    @csrf
                    <button type="button" class="btn-modal-cancel" onclick="closeCompleteModal()">Batal</button>
                    <button type="submit" class="btn-modal-submit">Tandai Sudah Dikembalikan</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openApproveModal(id, code, amount) {
            document.getElementById('approveForm').action = '/admin/refund/' + id + '/approve';
            document.getElementById('approve_refund_code').textContent = code;
            document.getElementById('approve_refund_amount').value = '';
            document.getElementById('approveModal').classList.add('active');
        }

        function closeApproveModal() {
            document.getElementById('approveModal').classList.remove('active');
        }

        function openRejectModal(id, code) {
            document.getElementById('rejectForm').action = '/admin/refund/' + id + '/reject';
            document.getElementById('reject_refund_code').textContent = code;
            document.getElementById('rejectModal').classList.add('active');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.remove('active');
        }

        function openCompleteModal(id, code) {
            document.getElementById('completeForm').action = '/admin/refund/' + id + '/complete';
            document.getElementById('complete_refund_code').textContent = code;
            document.getElementById('completeModal').classList.add('active');
        }

        function closeCompleteModal() {
            document.getElementById('completeModal').classList.remove('active');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeApproveModal();
                closeRejectModal();
                closeCompleteModal();
            }
        });
    </script>
</body>
</html>
