<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}">
    <title>Profil Saya - Summit Station</title>
    <link rel="stylesheet" href="{{ asset('css/summit-profile.css') . '?v=' . filemtime(public_path('css/summit-profile.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-footer.css') . '?v=' . filemtime(public_path('css/summit-footer.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-navbar.css') . '?v=' . filemtime(public_path('css/summit-navbar.css')) }}">
</head>
<body>

    @include('layouts.navbar')

    @php
        $handle = ($user->username !== null && trim($user->username) !== '')
            ? ltrim($user->username, '@')
            : ($user->name ?? '');
        $avatarInitial = $handle !== '' ? mb_strtoupper(mb_substr($handle, 0, 1, 'UTF-8')) : 'U';

        $statusClass = match ($user->status) {
            'suspended' => 'suspended',
            'inactive' => 'inactive',
            'pending', 'pending_verification' => 'pending',
            default => 'active',
        };

        $accountStatus = ucfirst(strtolower($user->status_label));

        $lastStatusLabels = [
            'pending' => 'Menunggu',
            'active' => 'Aktif',
            'paid' => 'Sudah Dibayar',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            'returned' => 'Barang Dikembalikan',
            'rejected' => 'Ditolak',
        ];
    @endphp

    <main class="cp-page profile-page">

        @if (session('status'))
            <div class="cp-flash cp-flash-success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="cp-flash cp-flash-error">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <div>
                    <strong>Terjadi kesalahan</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- ═══ PROFIL AKUN ═══ --}}
        <section class="cp-hero">
            <div class="cp-avatar-block">
                <div class="cp-avatar">
                    @if ($user->avatar_path)
                        <img src="{{ $user->avatar_path }}" alt="Foto profil {{ $user->name }}"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                        <span class="cp-avatar-initial" style="display:none">{{ $avatarInitial }}</span>
                    @else
                        <span class="cp-avatar-initial">{{ $avatarInitial }}</span>
                    @endif
                </div>
                <button type="button" class="cp-avatar-edit" title="Ubah foto &amp; profil" onclick="openEditProfileModal()" aria-label="Ubah foto dan profil">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </button>
            </div>

            <div class="cp-hero-info">
                <h1 class="cp-name">{{ $user->name }}</h1>
                <p class="cp-username">{{ '@' . $handle }}</p>
                <div class="cp-hero-meta">
                    @if ($user->email)
                        <span class="cp-meta-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                            {{ $user->email }}
                        </span>
                    @endif
                    @if ($user->phone)
                        <span class="cp-meta-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7l.5 2.5a2 2 0 0 1-.5 1.8L8 9a16 16 0 0 0 7 7l1-1.1a2 2 0 0 1 1.8-.5l2.5.5a2 2 0 0 1 1.7 2z"></path>
                            </svg>
                            {{ $user->phone }}
                        </span>
                    @endif
                    <span class="cp-account-status {{ $statusClass }}">
                        <span class="cp-status-dot"></span>{{ $accountStatus }}
                    </span>
                </div>
            </div>

            <div class="cp-hero-actions">
                <button type="button" class="cp-btn cp-btn-primary" onclick="openEditProfileModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    Edit Profil
                </button>
                <button type="button" class="cp-btn cp-btn-ghost" onclick="openPasswordModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    Ubah Password
                </button>
                <button type="button" class="cp-btn cp-btn-danger" data-logout-open title="Keluar dari akun">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Keluar
                </button>
            </div>
        </section>

        {{-- ═══ AKTIVITAS RENTAL ═══ --}}
        <section class="cp-section cp-activity">
            <div class="cp-section-head">
                <div class="cp-section-title">
                    <span class="cp-eyebrow">AKTIVITAS</span>
                    <h2>Aktivitas Rental</h2>
                    <p>Ringkasan aktivitas penyewaan Anda</p>
                </div>
                <div class="cp-section-action">
                    <a href="{{ route('history') }}" class="cp-btn cp-btn-ghost cp-btn-sm">
                        Lihat Riwayat Penyewaan
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </a>
                </div>
            </div>

            <div class="cp-stats">
                <div class="cp-stat">
                    <span class="cp-stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="21 8 21 21 3 21 3 8"></polyline><rect x="1" y="3" width="22" height="5"></rect><line x1="10" y1="12" x2="14" y2="12"></line></svg>
                    </span>
                    <strong class="cp-stat-value">{{ number_format($totalOrdersCount, 0, ',', '.') }}</strong>
                    <span class="cp-stat-label">Total Rental</span>
                </div>
                <div class="cp-stat">
                    <span class="cp-stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    </span>
                    <strong class="cp-stat-value">{{ number_format($activeOrdersCount, 0, ',', '.') }}</strong>
                    <span class="cp-stat-label">Booking Aktif</span>
                </div>
                <div class="cp-stat">
                    <span class="cp-stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    </span>
                    <strong class="cp-stat-value">{{ number_format($completedOrdersCount, 0, ',', '.') }}</strong>
                    <span class="cp-stat-label">Selesai</span>
                </div>
                <div class="cp-stat">
                    <span class="cp-stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </span>
                    <strong class="cp-stat-value">{{ number_format($pendingOrdersCount, 0, ',', '.') }}</strong>
                    <span class="cp-stat-label">Menunggu Pembayaran</span>
                </div>
            </div>
        </section>

        {{-- ═══ INFORMASI PRIBADI ═══ --}}
        <section class="cp-section cp-info-section">
            <div class="cp-section-head">
                <div class="cp-section-title">
                    <span class="cp-eyebrow">DATA DIRI</span>
                    <h2>Informasi Pribadi</h2>
                    <p>Kelola informasi akun Anda.</p>
                </div>
            </div>

            <div class="cp-info-split">
                <div class="cp-info-group">
                    <h3 class="cp-group-title">Data Pribadi</h3>
                    <dl>
                        <div class="cp-info-item"><dt>Nama Lengkap</dt><dd>{{ $user->name }}</dd></div>
                        <div class="cp-info-item"><dt>Username</dt><dd>{{ $handle !== '' ? '@' . $handle : '-' }}</dd></div>
                        <div class="cp-info-item"><dt>Email</dt><dd>{{ $user->email }}</dd></div>
                        <div class="cp-info-item"><dt>Nomor Telepon</dt><dd>{{ $user->phone ?: '-' }}</dd></div>
                        <div class="cp-info-item"><dt>Domisili</dt><dd>{{ $user->domicile ?: '-' }}</dd></div>
                        <div class="cp-info-item"><dt>Tanggal Lahir</dt><dd>{{ $user->date_of_birth_formatted ?: '-' }}</dd></div>
                    </dl>
                </div>

                <div class="cp-info-group">
                    <h3 class="cp-group-title">Informasi Akun</h3>
                    <dl>
                        <div class="cp-info-item"><dt>Status Akun</dt><dd>{{ $accountStatus }}</dd></div>
                        <div class="cp-info-item"><dt>Anggota Sejak</dt><dd>{{ $user->created_at ? $user->created_at->format('M Y') : '-' }}</dd></div>
                        <div class="cp-info-item"><dt>Terakhir Diperbarui</dt><dd>{{ $user->updated_at ? $user->updated_at->format('d M Y, H:i') : '-' }}</dd></div>
                    </dl>
                </div>
            </div>
        </section>

        {{-- ═══ PENYEWAAN TERAKHIR ═══ --}}
        <section class="cp-section cp-recent">
            <div class="cp-section-head">
                <div class="cp-section-title">
                    <span class="cp-eyebrow">RIWAYAT TERBARU</span>
                    <h2>Penyewaan Terakhir</h2>
                    <p>Pesanan paling terbaru Anda</p>
                </div>
                <a href="{{ route('history') }}" class="cp-link">
                    Lihat Semua
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
            </div>

            @if ($latestOrder)
                @php
                    $firstItem = $latestOrder->items->first();
                    $productTitle = $firstItem ? $firstItem->name : 'Penyewaan Peralatan';
                    $productImage = $firstItem && $firstItem->image
                        ? $firstItem->image
                        : 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=160&q=80';
                @endphp
                <article class="cp-order">
                    <img class="cp-order-img" src="{{ $productImage }}" alt="{{ $productTitle }}">
                    <div class="cp-order-info">
                        <small>PESANAN: #{{ $latestOrder->code }}</small>
                        <h3>{{ $productTitle }}</h3>
                        <p>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            {{ $latestOrder->rent_start ? $latestOrder->rent_start->format('M d') : '-' }} - {{ $latestOrder->rent_end ? $latestOrder->rent_end->format('M d, Y') : '-' }}
                        </p>
                    </div>
                    <div class="cp-order-total">
                        <span class="cp-order-status st-{{ $latestOrder->status }}">{{ $lastStatusLabels[$latestOrder->status] ?? ucfirst($latestOrder->status) }}</span>
                        <small>TOTAL</small>
                        <strong>Rp {{ number_format($latestOrder->total, 0, ',', '.') }}</strong>
                        <a href="{{ route('history') }}">Detail</a>
                    </div>
                </article>
            @else
                <div class="cp-empty">
                    <div class="cp-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"></path></svg>
                    </div>
                    <h3>Rencanakan Ekspedisi Baru</h3>
                    <p>Dapatkan perlengkapan pendakian dan outdoor kelas profesional untuk petualangan berikutnya.</p>
                    <a href="{{ route('catalog') }}" class="cp-btn cp-btn-primary">Buka Katalog Sewa</a>
                </div>
            @endif
        </section>

        {{-- ═══ DOKUMEN VERIFIKASI ═══ --}}
        <section class="cp-section cp-docs-section">
            <div class="cp-section-head">
                <div class="cp-section-title">
                    <span class="cp-eyebrow">VERIFIKASI</span>
                    <h2>Dokumen Verifikasi</h2>
                    <p>Dokumen identitas dan persetujuan untuk akun Anda</p>
                </div>
            </div>

            <div class="cp-docs">
                @if ($user->is_minor)
                    <div class="cp-doc-row">
                        @if ($user->ktp_orang_tua_url)
                            <img class="cp-doc-thumb" src="{{ $user->ktp_orang_tua_url }}" alt="KTP Orang Tua {{ $user->name }}">
                        @else
                            <span class="cp-doc-thumb cp-doc-thumb-empty">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>
                            </span>
                        @endif
                        <div class="cp-doc-info">
                            <span class="cp-doc-title">KTP ORANG TUA</span>
                            <span class="cp-doc-meta">{{ $user->ktp_orang_tua_url ? 'Foto KTP orang tua tersimpan di penyimpanan privat.' : 'KTP orang tua belum tersedia.' }}</span>
                        </div>
                        <span class="cp-doc-pill {{ $user->ktp_orang_tua_url ? 'available' : 'empty' }}">{{ $user->ktp_orang_tua_url ? 'Dokumen tersimpan' : 'Belum diunggah' }}</span>
                        @if ($user->ktp_orang_tua_url)
                            <div class="cp-doc-actions">
                                <button type="button" class="cp-btn cp-btn-sm cp-btn-ghost" onclick="openKtpModal('{{ $user->ktp_orang_tua_url }}')">Lihat</button>
                            </div>
                        @else
                            <div class="cp-doc-actions">
                                <a class="cp-btn cp-btn-sm cp-btn-ghost" href="{{ route('contact.admin') }}">Hubungi Admin</a>
                            </div>
                        @endif
                    </div>
                    <div class="cp-doc-row">
                        @if ($user->kartu_pelajar_url)
                            <img class="cp-doc-thumb" src="{{ $user->kartu_pelajar_url }}" alt="Kartu Pelajar {{ $user->name }}">
                        @else
                            <span class="cp-doc-thumb cp-doc-thumb-empty">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>
                            </span>
                        @endif
                        <div class="cp-doc-info">
                            <span class="cp-doc-title">KARTU PELAJAR</span>
                            <span class="cp-doc-meta">{{ $user->kartu_pelajar_url ? 'Foto kartu pelajar tersimpan di penyimpanan privat.' : 'Kartu pelajar belum tersedia.' }}</span>
                        </div>
                        <span class="cp-doc-pill {{ $user->kartu_pelajar_url ? 'available' : 'empty' }}">{{ $user->kartu_pelajar_url ? 'Dokumen tersimpan' : 'Belum diunggah' }}</span>
                        @if ($user->kartu_pelajar_url)
                            <div class="cp-doc-actions">
                                <button type="button" class="cp-btn cp-btn-sm cp-btn-ghost" onclick="openKtpModal('{{ $user->kartu_pelajar_url }}')">Lihat</button>
                            </div>
                        @else
                            <div class="cp-doc-actions">
                                <a class="cp-btn cp-btn-sm cp-btn-ghost" href="{{ route('contact.admin') }}">Hubungi Admin</a>
                            </div>
                        @endif
                    </div>
                    <p class="cp-doc-note">Dokumen identitas Anda (usia di bawah 17 tahun) diunggah saat pendaftaran dan tersimpan aman.</p>
                @elseif ($user->ktp_url)
                    <div class="cp-doc-row">
                        <img id="ktp-profile-img" class="cp-doc-thumb" src="{{ $user->ktp_url }}" alt="KTP {{ $user->name }}">
                        <div class="cp-doc-info">
                            <span class="cp-doc-title">KTP / Identitas</span>
                            <span class="cp-doc-meta">Foto KTP Anda diunggah saat pendaftaran dan tersimpan aman.</span>
                        </div>
                        <span class="cp-doc-pill available">Dokumen tersimpan</span>
                        <div class="cp-doc-actions">
                            <button type="button" class="cp-btn cp-btn-sm cp-btn-ghost" onclick="openKtpModal()">Lihat</button>
                        </div>
                    </div>
                @else
                    <div class="cp-doc-row">
                        <span class="cp-doc-thumb cp-doc-thumb-empty">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>
                        </span>
                        <div class="cp-doc-info">
                            <span class="cp-doc-title">KTP / Identitas</span>
                            <span class="cp-doc-meta">Belum ada dokumen KTP yang terunggah.</span>
                        </div>
                        <span class="cp-doc-pill empty">Belum diunggah</span>
                        <div class="cp-doc-actions">
                            <a class="cp-btn cp-btn-sm cp-btn-ghost" href="{{ route('contact.admin') }}">Hubungi Admin</a>
                        </div>
                    </div>
                @endif

                {{-- Surat persetujuan orang tua (khusus di bawah 17 tahun) --}}
                @php
                    $consentPillClass = match ($user->parent_consent_status) {
                        'verified' => 'available',
                        'rejected' => 'rejected',
                        'submitted' => 'submitted',
                        'pending' => 'pending',
                        default => 'empty',
                    };
                @endphp
                @if ($user->parent_consent_path || $user->is_minor)
                    <div class="cp-consent-card">
                        @if ($user->parent_consent_path)
                            <div class="cp-doc-row">
                                <span class="cp-doc-thumb cp-doc-thumb-pdf">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M9 15l2 2 4-4"></path></svg>
                                </span>
                                <div class="cp-doc-info">
                                    <span class="cp-doc-title">Surat Persetujuan Orang Tua</span>
                                    <span class="cp-doc-meta">{{ basename($user->parent_consent_path) }}</span>
                                    @if ($user->parent_consent_status === 'rejected' && $user->parent_consent_rejected_reason)
                                        <span class="cp-consent-reason"><strong>Alasan penolakan:</strong> {{ $user->parent_consent_rejected_reason }}</span>
                                    @endif
                                </div>
                                <span class="cp-doc-pill {{ $consentPillClass }}">{{ $user->parent_consent_status_label }}</span>
                                @if ($user->parent_consent_url)
                                    <div class="cp-doc-actions">
                                        <button type="button" class="cp-btn cp-btn-sm cp-btn-primary" data-pdf-open="{{ $user->parent_consent_url }}">
                                            Lihat PDF
                                        </button>
                                    </div>
                                @endif
                            </div>
                            <p class="cp-doc-note">Dokumen ini disimpan di penyimpanan privat dan hanya dapat dilihat oleh Anda atau tim Administrator Summit Station.</p>
                        @else
                            <div class="cp-doc-row">
                                <span class="cp-doc-thumb cp-doc-thumb-pdf">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M9 15l2 2 4-4"></path></svg>
                                </span>
                                <div class="cp-doc-info">
                                    <span class="cp-doc-title">Surat Persetujuan Orang Tua</span>
                                    <span class="cp-doc-meta">Surat persetujuan orang tua belum diunggah.</span>
                                </div>
                                <span class="cp-doc-pill empty">Belum diunggah</span>
                            </div>
                            <p class="cp-consent-help">Anda berusia di bawah 17 tahun sehingga dokumen ini diperlukan untuk verifikasi. Silakan hubungi <a class="cp-inline-link" href="{{ route('contact.admin') }}">Administrator Summit Station</a> untuk melengkapi berkas.</p>
                        @endif
                    </div>
                @endif
            </div>
        </section>

        {{-- ═══ AKSES CEPAT ═══ --}}
        <section class="cp-section cp-quick-section">
            <div class="cp-section-head">
                <div class="cp-section-title">
                    <span class="cp-eyebrow">NAVIGASI CEPAT</span>
                    <h2>Akses Cepat</h2>
                    <p>Navigasi pintas untuk aktivitas Anda</p>
                </div>
            </div>

            <nav class="cp-quick-row" aria-label="Akses cepat">
                <a class="cp-quick-btn" href="{{ route('catalog') }}">
                    <span class="cp-quick-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    </span>
                    Lihat Katalog
                </a>
                <a class="cp-quick-btn" href="{{ route('history') }}">
                    <span class="cp-quick-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                    </span>
                    Riwayat Penyewaan
                </a>
                <a class="cp-quick-btn" href="{{ route('history', ['status' => 'active']) }}">
                    <span class="cp-quick-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line><polyline points="9 15 11 17 15 13"></polyline></svg>
                    </span>
                    Booking Aktif
                </a>
                <a class="cp-quick-btn" href="{{ route('contact.admin') }}">
                    <span class="cp-quick-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                    </span>
                    Hubungi Admin
                </a>
            </nav>
        </section>

    </main>

    <!-- Footer -->
    @include('partials.footer', ['footerContext' => 'user'])

    {{-- ═══ MODAL EDIT PROFIL ═══ --}}
    <div id="editProfileModal" class="user-modal-overlay" onclick="closeEditProfileModal(event)">
        <div class="user-modal-card" onclick="event.stopPropagation()">
            <div class="user-modal-head">
                <h3>Edit Profil Pengguna</h3>
                <p>Perbarui data profil dan informasi kontak Anda.</p>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" data-loading-text="Menyimpan...">
                @csrf
                <div class="form-group">
                    <label for="um_name">Nama Lengkap</label>
                    <input id="um_name" type="text" name="name" value="{{ $user->name }}" required>
                </div>
                <div class="form-group">
                    <label for="um_username">Username</label>
                    <input id="um_username" type="text" name="username" value="{{ ltrim($user->username, '@') }}">
                </div>
                <div class="form-group">
                    <label for="um_phone">Nomor WhatsApp</label>
                    <input id="um_phone" type="text" name="phone" value="{{ $user->phone }}">
                </div>
                <div class="form-group">
                    <label for="um_domicile">Domisili</label>
                    <input id="um_domicile" type="text" name="domicile" value="{{ $user->domicile }}" readonly disabled title="Domisili hanya dapat diatur saat pendaftaran akun.">
                </div>
                <div class="form-group">
                    <label for="um_avatar">Upload Foto Profil (Opsional)</label>
                    <input id="um_avatar" type="file" name="avatar" accept="image/*">
                </div>
                <div class="user-modal-actions">
                    <button type="button" onclick="closeEditProfileModal(null, true)" class="um-btn um-btn-cancel">Batal</button>
                    <button type="submit" class="um-btn um-btn-submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ MODAL UBAH PASSWORD ═══ --}}
    <div id="passwordModal" class="user-modal-overlay" onclick="closePasswordModal(event)">
        <div class="user-modal-card" onclick="event.stopPropagation()">
            <div class="user-modal-head">
                <h3>Ubah Password</h3>
                <p>Pastikan akun Anda tetap aman dengan password yang kuat.</p>
            </div>

            <form method="POST" action="{{ route('profile.password') }}" data-loading-text="Menyimpan...">
                @csrf
                <div class="form-group">
                    <label for="pm_current">Password Saat Ini</label>
                    <input id="pm_current" type="password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="pm_new">Password Baru (Min. 8 Karakter)</label>
                    <input id="pm_new" type="password" name="password" minlength="8" required>
                </div>
                <div class="form-group">
                    <label for="pm_confirm">Konfirmasi Password Baru</label>
                    <input id="pm_confirm" type="password" name="password_confirmation" minlength="8" required>
                </div>
                <div class="user-modal-actions">
                    <button type="button" onclick="closePasswordModal(null, true)" class="um-btn um-btn-cancel">Batal</button>
                    <button type="submit" class="um-btn um-btn-submit">Perbarui Password</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ MODAL PRATINJAU PDF / SURAT PERSETUJUAN ═══ --}}
    <div id="pdfModal" class="user-modal-overlay" onclick="closePdfModal(event)">
        <div class="user-modal-pdf" onclick="event.stopPropagation()">
            <div class="user-modal-pdf-head">
                <div class="user-modal-pdf-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <path d="M9 15l2 2 4-4"></path>
                    </svg>
                    <div>
                        <h3>Surat Persetujuan Orang Tua</h3>
                        <p>Pratinjau berkas — gunakan kontrol pembesaran browser untuk memperbesar/memperkecil.</p>
                    </div>
                </div>
                <button type="button" class="user-modal-pdf-close" onclick="closePdfModal(null, true)" aria-label="Tutup pratinjau PDF" title="Tutup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="user-modal-pdf-body">
                <iframe id="pdfFrame" src="" title="Surat Persetujuan Orang Tua" loading="lazy"></iframe>
            </div>
            <div class="user-modal-pdf-actions">
                <button type="button" class="um-btn um-btn-cancel" onclick="closePdfModal(null, true)">Tutup</button>
            </div>
        </div>
    </div>

    {{-- ═══ MODAL PERBESAR KTP ═══ --}}
    <div id="ktpModal" class="user-modal-overlay" onclick="closeKtpModal(event)">
        <div class="user-modal-zoom" onclick="event.stopPropagation()">
            <img id="ktpModalImg" src="" alt="Foto KTP">
            <div class="user-modal-zoom-actions">
                <button type="button" onclick="closeKtpModal(null, true)" class="um-btn um-btn-cancel">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        function openEditProfileModal() {
            document.getElementById('editProfileModal').classList.add('show');
        }
        function closeEditProfileModal(e, force) {
            if (force || e?.target === document.getElementById('editProfileModal')) {
                document.getElementById('editProfileModal').classList.remove('show');
            }
        }

        function openPasswordModal() {
            document.getElementById('passwordModal').classList.add('show');
        }
        function closePasswordModal(e, force) {
            if (force || e?.target === document.getElementById('passwordModal')) {
                document.getElementById('passwordModal').classList.remove('show');
            }
        }

        function openKtpModal(src) {
            if (!src) {
                const source = document.getElementById('ktp-profile-img');
                if (source) src = source.src;
            }
            if (src) {
                document.getElementById('ktpModalImg').src = src;
                document.getElementById('ktpModal').classList.add('show');
            }
        }
        function closeKtpModal(e, force) {
            if (force || e?.target === document.getElementById('ktpModal')) {
                document.getElementById('ktpModal').classList.remove('show');
                document.getElementById('ktpModalImg').removeAttribute('src');
            }
        }

        function openPdfModal(url) {
            if (!url) { return; }
            document.getElementById('pdfModal').classList.add('show');
            document.body.classList.add('cp-modal-open');
            document.getElementById('pdfFrame').src = url;
        }
        function closePdfModal(e, force) {
            var modal = document.getElementById('pdfModal');
            if (force || e?.target === modal) {
                modal.classList.remove('show');
                document.getElementById('pdfFrame').removeAttribute('src');
                document.body.classList.remove('cp-modal-open');
            }
        }
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape') {
                var pdfModal = document.getElementById('pdfModal');
                if (pdfModal && pdfModal.classList.contains('show')) {
                    closePdfModal(null, true);
                }
            }
        });
        document.querySelectorAll('[data-pdf-open]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openPdfModal(btn.getAttribute('data-pdf-open'));
            });
        });

        document.querySelectorAll('form[data-loading-text]').forEach(function (form) {
            form.addEventListener('submit', function () {
                var btn = form.querySelector('button[type="submit"]');
                if (!btn) { return; }
                btn.disabled = true;
                btn.classList.add('is-loading');
                btn.innerHTML = btn.dataset.loadingText || 'Menyimpan...';
            });
        });
    </script>

    <script src="{{ asset('js/summit-navbar.js') }}"></script>
</body>
</html>