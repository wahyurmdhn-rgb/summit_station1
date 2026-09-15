<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}">
    <title>Profil Saya - Summit Station</title>
    <link rel="stylesheet" href="{{ asset('css/summit-navbar.css') . '?v=' . filemtime(public_path('css/summit-navbar.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-profile.css') . '?v=' . filemtime(public_path('css/summit-profile.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-footer.css') . '?v=' . filemtime(public_path('css/summit-footer.css')) }}">
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

    <main class="cp-page">

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

        {{-- ═══ PROFILE HERO ═══ --}}
        <section class="cp-hero">
            <div class="cp-hero-decor" aria-hidden="true"></div>

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
                <button type="button" class="cp-avatar-edit" title="Ubah foto &amp; profil" onclick="openEditProfileModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </button>
            </div>

            <div class="cp-hero-info">
                <span class="cp-role-badge">Customer</span>
                <h1 class="cp-name">{{ $user->name }}</h1>
                <p class="cp-username">{{ '@' . $handle }}</p>

                <div class="cp-hero-meta">
                    <span class="cp-chip">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        {{ $user->email }}
                    </span>
                    @if ($user->phone)
                        <span class="cp-chip">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7l.5 2.5a2 2 0 0 1-.5 1.8L8 9a16 16 0 0 0 7 7l1-1.1a2 2 0 0 1 1.8-.5l2.5.5a2 2 0 0 1 1.7 2z"></path>
                            </svg>
                            {{ $user->phone }}
                        </span>
                    @endif
                    <span class="cp-status-pill {{ $statusClass }}"><span class="cp-status-dot"></span>{{ $user->status_label }}</span>
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
                <button type="button" class="cp-btn cp-btn-ghost cp-btn-logout" data-logout-open title="Keluar dari akun">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Keluar
                </button>
            </div>
        </section>

        {{-- ═══ AKTIVITAS + DOKUMEN ═══ --}}
        <div class="cp-main-grid">

            <section class="cp-card">
                <div class="cp-card-head">
                    <div class="cp-card-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline>
                            <path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="cp-card-title">Aktivitas Saya</h2>
                        <p class="cp-card-subtitle">Ringkasan aktivitas rental Anda</p>
                    </div>
                </div>

                <div class="cp-stats">
                    <div class="cp-stat">
                        <div class="cp-stat-icon sc-green">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            </svg>
                        </div>
                        <strong>{{ number_format($totalOrdersCount, 0, ',', '.') }}</strong>
                        <span>Total Penyewaan</span>
                    </div>
                    <div class="cp-stat">
                        <div class="cp-stat-icon sc-blue">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                        <strong>{{ number_format($activeOrdersCount, 0, ',', '.') }}</strong>
                        <span>Booking Aktif</span>
                    </div>
                    <div class="cp-stat">
                        <div class="cp-stat-icon sc-violet">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                                <polyline points="3 3 3 8 8 8"></polyline>
                            </svg>
                        </div>
                        <strong>{{ number_format($returnsCount, 0, ',', '.') }}</strong>
                        <span>Pengembalian</span>
                    </div>
                    <div class="cp-stat">
                        <div class="cp-stat-icon sc-amber">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <strong>{{ number_format($completedOrdersCount, 0, ',', '.') }}</strong>
                        <span>Pesanan Selesai</span>
                    </div>
                </div>
            </section>

            <section class="cp-card cp-ktp-card">
                <div class="cp-card-head">
                    <div class="cp-card-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>
                    </div>
                    <div>
                        <h2 class="cp-card-title">Dokumen Verifikasi</h2>
                        <p class="cp-card-subtitle">Dokumen identitas akun Anda</p>
                    </div>
                    @if ($user->ktp_url)
                        <span class="cp-doc-pill available">● Dokumen Tersimpan</span>
                    @else
                        <span class="cp-doc-pill empty">Belum diunggah</span>
                    @endif
                </div>

                @if ($user->ktp_url)
                    <div class="cp-ktp-preview" title="Klik untuk memperbesar" onclick="openKtpModal()">
                        <img id="ktp-profile-img" src="{{ $user->ktp_url }}" alt="KTP {{ $user->name }}">
                        <span class="cp-ktp-zoom">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                <line x1="11" y1="8" x2="11" y2="14"></line>
                                <line x1="8" y1="11" x2="14" y2="11"></line>
                            </svg>
                        </span>
                    </div>
                    <p class="cp-ktp-note">Foto KTP Anda telah diunggah saat pendaftaran dan tersimpan aman.</p>
                @else
                    <div class="cp-ktp-empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                            <polyline points="13 2 13 9 20 9"></polyline>
                        </svg>
                        <p>Belum ada dokumen KTP yang terunggah.</p>
                    </div>
                @endif
            </section>
        </div>

        {{-- ═══ INFORMASI PRIBADI ═══ --}}
        <section class="cp-card">
            <div class="cp-card-head">
                <div class="cp-card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div>
                    <h2 class="cp-card-title">Informasi Pribadi</h2>
                    <p class="cp-card-subtitle">Data pribadi akun Anda</p>
                </div>
            </div>

            <div class="cp-info-grid">
                <div class="cp-info-item">
                    <div class="cp-info-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></div>
                    <div>
                        <span class="cp-info-label">Nama</span>
                        <span class="cp-info-value">{{ $user->name }}</span>
                    </div>
                </div>
                <div class="cp-info-item">
                    <div class="cp-info-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></div>
                    <div>
                        <span class="cp-info-label">Username</span>
                        <span class="cp-info-value">{{ $handle !== '' ? '@' . $handle : '-' }}</span>
                    </div>
                </div>
                <div class="cp-info-item">
                    <div class="cp-info-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg></div>
                    <div>
                        <span class="cp-info-label">Email</span>
                        <span class="cp-info-value">{{ $user->email }}</span>
                    </div>
                </div>
                <div class="cp-info-item">
                    <div class="cp-info-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7l.5 2.5a2 2 0 0 1-.5 1.8L8 9a16 16 0 0 0 7 7l1-1.1a2 2 0 0 1 1.8-.5l2.5.5a2 2 0 0 1 1.7 2z"></path></svg></div>
                    <div>
                        <span class="cp-info-label">No. WhatsApp</span>
                        <span class="cp-info-value">{{ $user->phone ?: '-' }}</span>
                    </div>
                </div>
                <div class="cp-info-item">
                    <div class="cp-info-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12z"></path><circle cx="12" cy="9" r="2"></circle></svg></div>
                    <div>
                        <span class="cp-info-label">Domisili</span>
                        <span class="cp-info-value">{{ $user->domicile ?: '-' }}</span>
                    </div>
                </div>
                <div class="cp-info-item">
                    <div class="cp-info-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></div>
                    <div>
                        <span class="cp-info-label">Anggota Sejak</span>
                        <span class="cp-info-value">{{ $user->created_at ? $user->created_at->format('M Y') : '-' }}</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- ═══ AKSI CEPAT ═══ --}}
        <section class="cp-quick-grid">
            <a href="{{ route('catalog') }}" class="cp-quick">
                <div class="cp-quick-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2l1.5 4H20l-3 8H8L5 2z"></path><circle cx="9" cy="20" r="1"></circle><circle cx="17" cy="20" r="1"></circle></svg></div>
                <div>
                    <strong>Lihat Katalog</strong>
                    <span>Jelajahi perlengkapan sewa</span>
                </div>
                <svg class="cp-quick-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>
            <a href="{{ route('history') }}" class="cp-quick">
                <div class="cp-quick-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><polyline points="3 3 3 8 8 8"></polyline></svg></div>
                <div>
                    <strong>Riwayat Penyewaan</strong>
                    <span>Lihat seluruh transaksi Anda</span>
                </div>
                <svg class="cp-quick-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>
            <a href="{{ route('history', ['status' => 'active']) }}" class="cp-quick">
                <div class="cp-quick-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></div>
                <div>
                    <strong>Booking Aktif</strong>
                    <span>Penyewaan yang sedang berjalan</span>
                </div>
                <svg class="cp-quick-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>
            <a href="{{ route('contact.admin') }}" class="cp-quick">
                <div class="cp-quick-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></div>
                <div>
                    <strong>Hubungi Admin</strong>
                    <span>Butuh bantuan? Chat admin</span>
                </div>
                <svg class="cp-quick-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>
        </section>

        {{-- ═══ PENYEWAAN TERAKHIR ═══ --}}
        <section class="cp-card cp-recent">
            <div class="cp-card-head">
                <div class="cp-card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                </div>
                <div>
                    <h2 class="cp-card-title">Penyewaan Terakhir</h2>
                    <p class="cp-card-subtitle">Pesanan paling terbaru Anda</p>
                </div>
                <a href="{{ route('history') }}" class="cp-link-all">Lihat Semua
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

        function openKtpModal() {
            const source = document.getElementById('ktp-profile-img');
            if (source) {
                document.getElementById('ktpModalImg').src = source.src;
                document.getElementById('ktpModal').classList.add('show');
            }
        }
        function closeKtpModal(e, force) {
            if (force || e?.target === document.getElementById('ktpModal')) {
                document.getElementById('ktpModal').classList.remove('show');
                document.getElementById('ktpModalImg').removeAttribute('src');
            }
        }

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