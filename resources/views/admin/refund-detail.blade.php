<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}">
    <title>Refund Detail {{ $refund->code }} - Summit Station</title>
    <link rel="stylesheet" href="{{ asset('css/summit-admin.css') . '?v=' . time() }}">
    <link rel="stylesheet" href="{{ asset('css/summit-footer.css') . '?v=' . filemtime(public_path('css/summit-footer.css')) }}">
    <style>
        .rd-badge-pending { background: #fef3c7; color: #92400e; }
        .rd-badge-approved { background: #dbeafe; color: #1e40af; }
        .rd-badge-rejected { background: #fee2e2; color: #991b1b; }
        .rd-badge-completed { background: #dcfce7; color: #166534; }
        .rd-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; margin-bottom: 18px; }
        .rd-label { font-size: 11px; font-weight: 800; color: #64748b; letter-spacing: .5px; text-transform: uppercase; }
        .rd-value { font-size: 14px; font-weight: 700; color: #1a1d1a; margin-top: 3px; }
        .rd-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
        @media (max-width: 900px) { .rd-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 600px) { .rd-grid { grid-template-columns: 1fr; } }
        .rd-proof { max-width: 420px; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
        .rd-proof img { width: 100%; display: block; }
        .rd-note-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; font-size: 13px; color: #334155; line-height: 1.6; }
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
            @include('admin.partials.header', [
                'adminPageTitle' => 'Detail Refund',
                'adminPageSubtitle' => 'Detail pengembalian dana',
            ])

            <main class="admin-content">
                @if (session('success'))
                    <div style="background-color: #dcfce7; border-left: 4px solid #16a34a; padding: 14px 18px; border-radius: 8px; color: #166534; font-size: 13px; font-weight: 700; margin-bottom: 20px;">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div style="background-color: #fee2e2; border-left: 4px solid #ef4444; padding: 14px 18px; border-radius: 8px; color: #991b1b; font-size: 13px; font-weight: 700; margin-bottom: 20px;">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div style="background-color: #fee2e2; border-left: 4px solid #ef4444; padding: 14px 18px; border-radius: 8px; color: #991b1b; font-size: 13px; font-weight: 700; margin-bottom: 20px;">{{ $errors->first() }}</div>
                @endif

                <!-- Header + Status -->
                <div class="rd-card" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <p class="rd-label">PENGEMBALIAN DANA</p>
                        <h1 style="font-size: 24px; font-weight: 900; color: #1a1d1a; margin: 2px 0 0;">{{ $refund->code }}</h1>
                        <p style="font-size: 13px; color: #64748b; margin-top: 4px;">
                            Diajukan {{ $refund->created_at ? $refund->created_at->format('d M Y • H:i') : '-' }}
                        </p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                        <span class="badge-status {{ $refund->status_badge_class }}" style="font-size: 13px; padding: 8px 16px;">
                            @if (in_array($refund->status, ['approved', 'completed']))<span class="status-dot"></span>@endif
                            {{ $refund->status_label }}
                        </span>

                        @if ($refund->status === 'pending')
                            <button type="button" class="btn-modal-submit" onclick="openApproveModal()">Setujui Refund</button>
                            <button type="button" class="btn-modal-submit" style="background-color: #dc2626;" onclick="openRejectModal()">Tolak Refund</button>
                        @elseif ($refund->status === 'approved')
                            <button type="button" class="btn-modal-submit" onclick="openCompleteModal()">Tandai Sudah Dikembalikan</button>
                        @endif

                        <a href="{{ route('admin.refund') }}" class="btn-modal-cancel" style="text-decoration: none; display: inline-flex;">&larr; Kembali</a>
                    </div>
                </div>

                <!-- Info Booking & Refund -->
                <div class="rd-grid">
                    <div class="rd-card" style="margin-bottom: 0;">
                        <p class="rd-label">ID Pengembalian</p>
                        <p class="rd-value">{{ $refund->code }} <span style="font-weight: 500; color: #64748b;">(#{{ $refund->id }})</span></p>
                    </div>
                    <div class="rd-card" style="margin-bottom: 0;">
                        <p class="rd-label">ID Pesanan</p>
                        <p class="rd-value">#{{ $refund->order?->code ?? '-' }}</p>
                    </div>
                    <div class="rd-card" style="margin-bottom: 0;">
                        <p class="rd-label">Pengguna</p>
                        <p class="rd-value">{{ $refund->order?->user?->name ?? '-' }}</p>
                        <p style="font-size: 12px; color: #64748b;">{{ $refund->order?->user?->email ?? '' }}</p>
                    </div>
                    <div class="rd-card" style="margin-bottom: 0;">
                        <p class="rd-label">Nama Barang / Paket</p>
                        <p class="rd-value">
                            @php
                                $items = $refund->order?->items ?? collect();
                            @endphp
                            @forelse ($items as $item)
                                {{ $item->name }}<span style="font-weight: 500; color:#64748b;"> x{{ $item->quantity }}</span>@if (!$loop->last)<br>@endif
                            @empty
                                Equipment Rental
                            @endforelse
                        </p>
                    </div>
                    <div class="rd-card" style="margin-bottom: 0;">
                        <p class="rd-label">Tanggal Booking</p>
                        <p class="rd-value">{{ $refund->order?->rent_start ? $refund->order->rent_start->format('d M Y') : '-' }}</p>
                    </div>
                    <div class="rd-card" style="margin-bottom: 0;">
                        <p class="rd-label">Tanggal Pengembalian</p>
                        <p class="rd-value">{{ $refund->order?->rent_end ? $refund->order->rent_end->format('d M Y') : '-' }}</p>
                    </div>
                    <div class="rd-card" style="margin-bottom: 0;">
                        <p class="rd-label">Total Pembayaran</p>
                        <p class="rd-value">Rp {{ number_format($refund->original_amount ?: ($refund->order?->total ?? 0), 0, ',', '.') }}</p>
                    </div>
                    <div class="rd-card" style="margin-bottom: 0;">
                        <p class="rd-label">Nominal Refund</p>
                        <p class="rd-value" style="color: #166534;">Rp {{ number_format($refund->refund_amount, 0, ',', '.') }}</p>
                        @if ($refund->adjustment_reason)
                            <p class="rd-note-box" style="margin-top: 8px; font-size: 12px;">
                                <strong>Penyesuaian:</strong> {{ $refund->adjustment_reason }}
                            </p>
                        @endif
                    </div>
                    <div class="rd-card" style="margin-bottom: 0;">
                        <p class="rd-label">Alasan Refund</p>
                        <p class="rd-value">{{ $refund->reason ?? '-' }}</p>
                    </div>
                    <div class="rd-card" style="margin-bottom: 0;">
                        <p class="rd-label">Tanggal Pengajuan</p>
                        <p class="rd-value">{{ $refund->created_at ? $refund->created_at->format('d M Y • H:i') : '-' }}</p>
                    </div>
                    <div class="rd-card" style="margin-bottom: 0;">
                        <p class="rd-label">Metode Pembayaran</p>
                        <p class="rd-value">
                            {{ $refund->payment?->formatted_method ?? ($refund->order?->latestPayment()?->formatted_method ?? '-') }}
                        </p>
                    </div>
                </div>

                <!-- Keterangan User & Reject Reason -->
                <div class="rd-grid" style="margin-top: 18px;">
                    <div class="rd-card" style="margin-bottom: 0;">
                        <p class="rd-label">Keterangan User</p>
                        <p class="rd-note-box" style="margin-top: 8px;">{{ $refund->description ?? '-' }}</p>
                    </div>
                    @if ($refund->status === 'rejected')
                        <div class="rd-card" style="margin-bottom: 0;">
                            <p class="rd-label">Alasan Penolakan</p>
                            <p class="rd-note-box" style="margin-top: 8px; border-color: #fecaca; background: #fef2f2; color: #991b1b;">{{ $refund->reject_reason }}</p>
                        </div>
                    @endif
                </div>

                <!-- Timeline Processing -->
                @if ($refund->status !== 'pending')
                    <div class="rd-card">
                        <p class="rd-label">Riwayat Proses</p>
                        <div style="margin-top: 10px; display: flex; flex-direction: column; gap: 8px; font-size: 13px; color: #334155;">
                            @if ($refund->approved_at)
                                <div>✅ <strong>Disetujui</strong> oleh {{ $refund->processedBy?->name ?? 'Admin' }} pada {{ $refund->approved_at->format('d M Y • H:i') }}</div>
                            @endif
                            @if ($refund->rejected_at)
                                <div>❌ <strong>Ditolak</strong> oleh {{ $refund->processedBy?->name ?? 'Admin' }} pada {{ $refund->rejected_at->format('d M Y • H:i') }}</div>
                            @endif
                            @if ($refund->completed_at)
                                <div>💰 <strong>Selesai</strong> (dana dikembalikan) pada {{ $refund->completed_at->format('d M Y • H:i') }}</div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Bukti Pembayaran -->
                <div class="rd-card">
                    <p class="rd-label">Bukti Pembayaran</p>
                    @php
                        $proofUrl = $refund->payment?->proof_url ?? $refund->order?->latestPayment()?->proof_url;
                    @endphp
                    @if ($proofUrl)
                        <div class="rd-proof" style="margin-top: 12px;">
                            <a href="{{ $proofUrl }}" target="_blank" rel="noopener">
                                <img src="{{ $proofUrl }}" alt="Bukti Pembayaran">
                            </a>
                        </div>
                        <p style="font-size: 12px; color: #64748b; margin-top: 8px;">Klik gambar untuk membuka bukti pembayaran.</p>
                    @else
                        <p style="font-size: 13px; color: #64748b; margin-top: 8px;">Tidak ada bukti pembayaran tersimpan untuk booking ini.</p>
                    @endif
                </div>
            </main>

            @include('partials.footer', ['footerContext' => 'admin'])
        </div>
    </div>

    <!-- Approve Modal -->
    <div id="approveModal" class="modal-overlay">
        <div class="modal-card" style="width: 480px;">
            <div class="modal-header">
                <h3 class="modal-title" style="color: #185d31;">Setujui Refund</h3>
                <button type="button" class="btn-close-modal" onclick="closeApproveModal()">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.refund.approve', $refund->id) }}">
                @csrf
                <div class="modal-body">
                    <p style="font-size: 14px; color: #374151; line-height: 1.5;">
                        Setujui refund <strong>{{ $refund->code }}</strong> dengan nominal <strong>Rp {{ number_format($refund->refund_amount, 0, ',', '.') }}</strong>? User akan menerima notifikasi.
                    </p>
                    <div class="form-group-modal" style="margin-top: 14px;">
                        <label class="form-label-modal">Nominal Refund (opsional penyesuaian)</label>
                        <input type="number" name="refund_amount" class="form-input-modal" min="0" value="{{ $refund->refund_amount }}" placeholder="Default dari database">
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

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal-overlay">
        <div class="modal-card" style="width: 480px;">
            <div class="modal-header">
                <h3 class="modal-title" style="color: #dc2626;">Tolak Refund</h3>
                <button type="button" class="btn-close-modal" onclick="closeRejectModal()">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.refund.reject', $refund->id) }}">
                @csrf
                <div class="modal-body">
                    <p style="font-size: 14px; color: #374151; line-height: 1.5; margin-bottom: 12px;">
                        Tolak refund <strong>{{ $refund->code }}</strong>. Alasan penolakan wajib diisi dan akan ditampilkan ke user.
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

    <!-- Complete Modal -->
    <div id="completeModal" class="modal-overlay">
        <div class="modal-card" style="width: 440px;">
            <div class="modal-header">
                <h3 class="modal-title" style="color: #185d31;">Selesaikan Refund</h3>
                <button type="button" class="btn-close-modal" onclick="closeCompleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p style="font-size: 14px; color: #374151; line-height: 1.5;">
                    Tandai refund <strong>{{ $refund->code }}</strong> sebagai selesai? Pastikan dana sudah benar-benar dikembalikan ke user secara manual sebelum melanjutkan.
                </p>
            </div>
            <div class="modal-footer">
                <form method="POST" action="{{ route('admin.refund.complete', $refund->id) }}">
                    @csrf
                    <button type="button" class="btn-modal-cancel" onclick="closeCompleteModal()">Batal</button>
                    <button type="submit" class="btn-modal-submit">Tandai Sudah Dikembalikan</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openApproveModal() { document.getElementById('approveModal').classList.add('active'); }
        function closeApproveModal() { document.getElementById('approveModal').classList.remove('active'); }
        function openRejectModal() { document.getElementById('rejectModal').classList.add('active'); }
        function closeRejectModal() { document.getElementById('rejectModal').classList.remove('active'); }
        function openCompleteModal() { document.getElementById('completeModal').classList.add('active'); }
        function closeCompleteModal() { document.getElementById('completeModal').classList.remove('active'); }

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
