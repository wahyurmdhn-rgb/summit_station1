<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}">
    <title>Manajemen Pengguna - Summit Station Admin</title>
    <link rel="stylesheet" href="{{ asset('css/summit-admin.css') . '?v=' . time() }}">
    <link rel="stylesheet" href="{{ asset('css/summit-footer.css') . '?v=' . filemtime(public_path('css/summit-footer.css')) }}">
</head>
<body>

    <!-- Blue Top Accent Line -->
    <div class="top-banner-line"></div>

    <div class="admin-layout">
        <!-- ─── 1. Sidebar Admin ─── -->
        @include('admin.partials.sidebar', ['activeMenu' => 'users'])

        <!-- ─── 2. Main Content ─── -->
        <div class="admin-main">
            <!-- Header -->
            @include('admin.partials.header', [
                'adminPageTitle' => 'Data Pengguna',
                'adminPageSubtitle' => 'Kelola akun pelanggan',
            ])

            <!-- Main Body -->
            <main class="admin-content">
                <!-- Flash Alerts -->
                @if (session('success'))
                    <div style="background: #dcfce7; border: 1px solid #86efac; color: #15803d; padding: 14px 20px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; font-size: 13px; display: flex; align-items: center; justify-content: space-between;">
                        <span>{{ session('success') }}</span>
                        <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; font-size: 16px; cursor: pointer; color: #15803d;">&times;</button>
                    </div>
                @endif
                @if (session('error'))
                    <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c; padding: 14px 20px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; font-size: 13px; display: flex; align-items: center; justify-content: space-between;">
                        <span>{{ session('error') }}</span>
                        <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; font-size: 16px; cursor: pointer; color: #b91c1c;">&times;</button>
                    </div>
                @endif

                @if (isset($errors) && $errors->any())
                    <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c; padding: 14px 20px; border-radius: 12px; margin-bottom: 20px; font-size: 13px;">
                        <strong>Terjadi kesalahan input:</strong>
                        <ul style="margin: 6px 0 0 16px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- ─── 3. Page Header: OPERATIONS & User Management ─── -->
                <div class="user-page-header-row">
                    <div class="user-page-header-left">
                        <div class="user-operations-badge">OPERASI</div>
                        <h1 class="user-main-heading">Manajemen Pengguna</h1>
                        <p class="user-main-subtitle">
                            Pantau dan kelola komunitas penjelajah Anda. Atur tingkat akses, verifikasi identitas, dan kelola status akun di seluruh platform.
                        </p>
                    </div>

                    <!-- Action Buttons: Export CSV & Add New User -->
                    <div class="user-page-actions">
                        <a href="{{ route('admin.users.export', request()->query()) }}" class="btn-export-csv">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            <span>Ekspor CSV</span>
                        </a>

                        <button type="button" class="btn-add-user" onclick="openAddModal()">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <line x1="19" y1="8" x2="19" y2="14"></line>
                                <line x1="22" y1="11" x2="16" y2="11"></line>
                            </svg>
                            <span>Tambah User Baru</span>
                        </button>
                    </div>
                </div>

                <!-- ─── 4. 4 Statistic Cards ─── -->
                <div class="user-stats-grid">
                    <!-- TOTAL MEMBERS -->
                    <div class="user-stat-card">
                        <div class="user-stat-label">TOTAL MEMBER</div>
                        <div class="user-stat-val-row">
                            <span class="user-stat-number">{{ number_format($totalMembers) }}</span>
                            <span class="user-stat-growth">Total</span>
                        </div>
                        <div class="user-stat-progress-track">
                            <div class="user-stat-progress-fill" style="width: 42%;"></div>
                        </div>
                    </div>

                    <!-- AKTIF SEKARANG -->
                    <div class="user-stat-card">
                        <div class="user-stat-label">AKTIF SEKARANG</div>
                        <div class="user-stat-val-row">
                            <span class="user-stat-number">{{ number_format($activeNow) }}</span>
                            <span class="user-stat-dot active"></span>
                        </div>
                        <div class="user-stat-subtext">Keterlibatan waktu nyata</div>
                    </div>

                    <!-- PENDAFTARAN BARU -->
                    <div class="user-stat-card">
                        <div class="user-stat-label">PENDAFTARAN BARU</div>
                        <div class="user-stat-val-row">
                            <span class="user-stat-number">{{ number_format($newRegistrations) }}</span>
                        </div>
                        <div class="user-stat-subtext">24 jam terakhir</div>
                    </div>

                    <!-- VERIFIKASI MENUNGGU -->
                    <div class="user-stat-card">
                        <div class="user-stat-label">VERIFIKASI MENUNGGU</div>
                        <div class="user-stat-val-row">
                            <span class="user-stat-number red">{{ number_format($pendingVerification) }}</span>
                        </div>
                        <div class="user-stat-subtext">
                            {{ isset($pendingConsent) && $pendingConsent > 0 ? number_format($pendingConsent) . ' persetujuan orang tua' : 'Perlu perhatian segera' }}
                        </div>
                    </div>
                </div>

                <!-- ─── 5 & 6. User Table Container ─── -->
                <div class="user-table-card">
                    <!-- Top Controls / Filter Bar -->
                    <div class="user-filter-bar">
                        <div class="user-filter-left">
                            <!-- Filter: All Status -->
                            <form method="GET" action="{{ route('admin.users') }}" id="filterStatusForm" class="filter-dropdown-form">
                                @if ($domicileFilter !== 'everywhere')
                                    <input type="hidden" name="domicile" value="{{ $domicileFilter }}">
                                @endif
                                @if ($consentFilter !== 'all')
                                    <input type="hidden" name="consent" value="{{ $consentFilter }}">
                                @endif
                                @if ($search)
                                    <input type="hidden" name="search" value="{{ $search }}">
                                @endif
                                <div class="filter-pill-dropdown">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="4" y1="6" x2="20" y2="6"></line>
                                        <line x1="7" y1="12" x2="17" y2="12"></line>
                                        <line x1="10" y1="18" x2="14" y2="18"></line>
                                    </svg>
                                    <select name="status" class="filter-select-pill" onchange="this.form.submit()">
                                        <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>Semua Status</option>
                                        <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Aktif</option>
                                        <option value="inactive" {{ $statusFilter === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                                        <option value="suspended" {{ $statusFilter === 'suspended' ? 'selected' : '' }}>Diblokir</option>
                                        <option value="pending" {{ in_array($statusFilter, ['pending', 'pending_verification']) ? 'selected' : '' }}>Verifikasi Menunggu</option>
                                    </select>
                                </div>
                            </form>

                            <!-- Filter: Everywhere (Domisili) -->
                            <form method="GET" action="{{ route('admin.users') }}" id="filterDomicileForm" class="filter-dropdown-form">
                                @if ($statusFilter !== 'all')
                                    <input type="hidden" name="status" value="{{ $statusFilter }}">
                                @endif
                                @if ($consentFilter !== 'all')
                                    <input type="hidden" name="consent" value="{{ $consentFilter }}">
                                @endif
                                @if ($search)
                                    <input type="hidden" name="search" value="{{ $search }}">
                                @endif
                                <div class="filter-pill-dropdown">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                    <select name="domicile" class="filter-select-pill" onchange="this.form.submit()">
                                        <option value="everywhere" {{ $domicileFilter === 'everywhere' ? 'selected' : '' }}>Semua Wilayah</option>
                                        @foreach ($domiciles as $dom)
                                            <option value="{{ $dom }}" {{ $domicileFilter === $dom ? 'selected' : '' }}>{{ $dom }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </form>

                            <!-- Filter: Persetujuan Orang Tua -->
                            <form method="GET" action="{{ route('admin.users') }}" id="filterConsentForm" class="filter-dropdown-form">
                                @if ($statusFilter !== 'all')
                                    <input type="hidden" name="status" value="{{ $statusFilter }}">
                                @endif
                                @if ($domicileFilter !== 'everywhere')
                                    <input type="hidden" name="domicile" value="{{ $domicileFilter }}">
                                @endif
                                @if ($search)
                                    <input type="hidden" name="search" value="{{ $search }}">
                                @endif
                                <div class="filter-pill-dropdown">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                        <path d="M9 12l2 2 4-4"></path>
                                    </svg>
                                    <select name="consent" class="filter-select-pill" onchange="this.form.submit()">
                                        <option value="all" {{ $consentFilter === 'all' ? 'selected' : '' }}>Persetujuan: Semua</option>
                                        <option value="submitted" {{ $consentFilter === 'submitted' ? 'selected' : '' }}>PO: Menunggu Verifikasi</option>
                                        <option value="verified" {{ $consentFilter === 'verified' ? 'selected' : '' }}>PO: Terverifikasi</option>
                                        <option value="rejected" {{ $consentFilter === 'rejected' ? 'selected' : '' }}>PO: Ditolak</option>
                                        <option value="not_required" {{ $consentFilter === 'not_required' ? 'selected' : '' }}>PO: Tidak Diperlukan</option>
                                    </select>
                                </div>
                            </form>

                            <!-- Inline Search Input -->
                            <form method="GET" action="{{ route('admin.users') }}" class="user-inline-search">
                                @if ($statusFilter !== 'all')
                                    <input type="hidden" name="status" value="{{ $statusFilter }}">
                                @endif
                                @if ($domicileFilter !== 'everywhere')
                                    <input type="hidden" name="domicile" value="{{ $domicileFilter }}">
                                @endif
                                @if ($consentFilter !== 'all')
                                    <input type="hidden" name="consent" value="{{ $consentFilter }}">
                                @endif
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                <input type="text"
                                    name="search"
                                    value="{{ $search }}"
                                    class="user-search-field"
                                    placeholder="Cari explorer...">
                                @if ($search)
                                    <a href="{{ route('admin.users', ['status' => $statusFilter, 'domicile' => $domicileFilter]) }}" class="clear-search-btn">&times;</a>
                                @endif
                            </form>
                        </div>

                        <!-- Right Info: Showing 1-10 of 2,842 -->
                        <div class="user-filter-right">
                            <span class="showing-count-text">
                                Menampilkan {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} dari {{ number_format($users->total()) }}
                            </span>
                            <div class="mini-chevron-nav">
                                @if ($users->onFirstPage())
                                    <span class="mini-chevron-btn disabled">&lsaquo;</span>
                                @else
                                    <a href="{{ $users->previousPageUrl() }}" class="mini-chevron-btn">&lsaquo;</a>
                                @endif

                                @if ($users->hasMorePages())
                                    <a href="{{ $users->nextPageUrl() }}" class="mini-chevron-btn">&rsaquo;</a>
                                @else
                                    <span class="mini-chevron-btn disabled">&rsaquo;</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- ─── 6. User Table ─── -->
                    <div class="user-table-scroll-container">
                        <table class="user-custom-table">
                            <thead>
                                <tr>
                                    <th style="width: 28%;">PROFIL USER</th>
                                    <th style="width: 18%;">USERNAME</th>
                                    <th style="width: 22%;">DOMISILI</th>
                                    <th style="width: 18%;">STATUS AKUN</th>
                                    <th style="width: 14%; text-align: right;">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $user)
                                    @php
                                        $userData = [
                                            'id' => $user->id,
                                            'name' => $user->name,
                                            'email' => $user->email,
                                            'username' => $user->username ? ltrim($user->username, '@') : '',
                                            'phone' => $user->phone ?? '',
                                            'domicile' => $user->domicile ?? '',
                                            'status' => $user->status ?? 'active',
                                            'status_label' => $user->status_label,
                                            'role' => $user->role ?? 'user',
                                            'orders_count' => $user->orders_count ?? 0,
                                            'avatar_path' => $user->avatar_path ?? $user->avatar,
                                            'ktp_url' => $user->ktp_url,
                                            'ktp_orang_tua_url' => $user->ktp_orang_tua_url,
                                            'kartu_pelajar_url' => $user->kartu_pelajar_url,
                                            'is_minor' => $user->is_minor,
                                            'created_at' => $user->created_at ? $user->created_at->format('M d, Y') : '-',
                                            'consent_status' => $user->parent_consent_status ?? 'not_required',
                                            'consent_label' => $user->parent_consent_status_label,
                                            'consent_badge_class' => $user->parent_consent_badge_class,
                                            'consent_proof_url' => $user->parent_consent_url,
                                            'parent_name' => $user->parent_name ?? '',
                                            'parent_relation' => $user->parent_relation ?? '',
                                            'parent_phone' => $user->parent_phone ?? '',
                                            'rejection_reason' => $user->parent_consent_rejected_reason ?? '',
                                            'age' => $user->age,
                                            'dob' => $user->date_of_birth_formatted,
                                        ];
                                    @endphp
                                    <tr class="user-table-row">
                                        <!-- 7. USER PROFILE -->
                                        <td>
                                            <div class="user-profile-cell" onclick="openDetailModal({{ json_encode($userData) }})" style="cursor: pointer;" title="Lihat Detail User">
                                                @if ($user->avatar_path || $user->avatar)
                                                    <img src="{{ $user->avatar_path ?? $user->avatar }}"
                                                        alt="{{ $user->name }}"
                                                        class="user-avatar-img">
                                                @else
                                                    <div class="user-avatar-initials">
                                                        {{ $user->initials }}
                                                    </div>
                                                @endif
                                                <div class="user-profile-text">
                                                    <div class="user-full-name">{{ $user->name }}</div>
                                                    <div class="user-email">{{ $user->email }}</div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- 8. USERNAME -->
                                        <td>
                                            @if ($user->username)
                                                <span class="user-username-badge">
                                                    @<span>{{ ltrim($user->username, '@') }}</span>
                                                </span>
                                            @else
                                                <span style="color: #9ca3af; font-size: 12px;">-</span>
                                            @endif
                                        </td>

                                        <!-- 9. DOMISILI -->
                                        <td>
                                            <div class="user-domicile-cell">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="domicile-icon">
                                                    <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                                                    <line x1="8" y1="2" x2="8" y2="18"></line>
                                                    <line x1="16" y1="6" x2="16" y2="22"></line>
                                                </svg>
                                                <span>{{ $user->domicile ?: '-' }}</span>
                                            </div>
                                        </td>

                                        <!-- 10. STATUS AKUN -->
                                        <td>
                                            <div class="user-status-cell">
                                                <span class="user-status-badge {{ $user->status_badge_class }}">
                                                    <span class="badge-dot"></span>
                                                    <span>{{ $user->status_label }}</span>
                                                </span>
                                                @if (($user->parent_consent_status ?? 'not_required') !== 'not_required')
                                                    <span
                                                        class="user-status-badge {{ $user->parent_consent_badge_class }}"
                                                        style="margin-top: 5px;"
                                                        title="Persetujuan orang tua: {{ $user->parent_consent_status_label }}">
                                                        <span class="badge-dot"></span>
                                                        <span>PO: {{ $user->parent_consent_status_label }}</span>
                                                    </span>
                                                @endif
                                            </div>
                                        </td>

                                        <!-- 11. ACTIONS -->
                                        <td>
                                            <div class="user-action-buttons">
                                                @if (in_array($user->parent_consent_status ?? '', ['pending', 'submitted']))
                                                    <!-- Verifikasi Persetujuan Orang Tua -->
                                                    <button type="button"
                                                            class="user-action-btn consent"
                                                            title="Verifikasi Persetujuan Orang Tua"
                                                            onclick="openConsentModal({{ json_encode($userData) }})">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                                            <path d="M9 12l2 2 4-4"></path>
                                                        </svg>
                                                    </button>
                                                @endif
                                                <!-- Edit Button (Pencil) -->
                                                <button type="button"
                                                        class="user-action-btn edit"
                                                        title="Edit User"
                                                        onclick="openEditModal({{ json_encode($userData) }})">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                    </svg>
                                                </button>

                                                <!-- Change Status Button (Slash Circle) -->
                                                <button type="button"
                                                        class="user-action-btn status"
                                                        title="Ubah Status Akun"
                                                        onclick="openStatusModal({{ json_encode($userData) }})">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <circle cx="12" cy="12" r="10"></circle>
                                                        <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                                                    </svg>
                                                </button>

                                                <!-- Delete Button (Trash Red) -->
                                                <button type="button"
                                                        class="user-action-btn delete"
                                                        title="Hapus User"
                                                        onclick="openDeleteModal({{ json_encode($userData) }})">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <!-- 24. Empty State -->
                                    <tr>
                                        <td colspan="5">
                                            <div class="user-empty-state">
                                                <div class="empty-icon-circle">
                                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                                        <circle cx="9" cy="7" r="4"></circle>
                                                    </svg>
                                                </div>
                                                <h3 class="empty-state-title">
                                                    @if ($search || $statusFilter !== 'all' || $domicileFilter !== 'everywhere' || $consentFilter !== 'all')
                                                        Tidak ada user yang sesuai dengan filter
                                                    @else
                                                        Belum ada user
                                                    @endif
                                                </h3>
                                                <p class="empty-state-desc">
                                                    @if ($search || $statusFilter !== 'all' || $domicileFilter !== 'everywhere' || $consentFilter !== 'all')
                                                        Coba ubah kata kunci pencarian atau sesuaikan status dan domisili.
                                                    @else
                                                        User yang mendaftar di Summit Station akan ditampilkan di halaman ini.
                                                    @endif
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- ─── 18. Pagination Footer ─── -->
                    <div class="user-pagination-footer">
                        <div class="pagination-items-per-page">
                            <span>Item per halaman:</span>
                            <form method="GET" action="{{ route('admin.users') }}" class="items-per-page-form">
                                @if ($statusFilter !== 'all')
                                    <input type="hidden" name="status" value="{{ $statusFilter }}">
                                @endif
                                @if ($domicileFilter !== 'everywhere')
                                    <input type="hidden" name="domicile" value="{{ $domicileFilter }}">
                                @endif
                                @if ($consentFilter !== 'all')
                                    <input type="hidden" name="consent" value="{{ $consentFilter }}">
                                @endif
                                @if ($search)
                                    <input type="hidden" name="search" value="{{ $search }}">
                                @endif
                                <select name="per_page" class="items-per-page-select" onchange="this.form.submit()">
                                    <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                                    <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                                </select>
                            </form>
                        </div>

                        <!-- Numeric Pagination Buttons -->
                        @if ($users->hasPages())
                            <div class="user-pagination-nav">
                                {{-- Previous Page Link --}}
                                @if ($users->onFirstPage())
                                    <span class="page-nav-btn disabled">Sebelumnya</span>
                                @else
                                    <a href="{{ $users->previousPageUrl() }}" class="page-nav-btn">Sebelumnya</a>
                                @endif

                                {{-- Pagination Elements --}}
                                @foreach ($users->getUrlRange(max(1, $users->currentPage() - 2), min($users->lastPage(), $users->currentPage() + 2)) as $page => $url)
                                    @if ($page == $users->currentPage())
                                        <span class="page-num-btn active">{{ $page }}</span>
                                    @else
                                        <a href="{{ $url }}" class="page-num-btn">{{ $page }}</a>
                                    @endif
                                @endforeach

                                @if ($users->lastPage() > $users->currentPage() + 2)
                                    <span class="page-num-ellipsis">...</span>
                                    <a href="{{ $users->url($users->lastPage()) }}" class="page-num-btn">{{ $users->lastPage() }}</a>
                                @endif

                                {{-- Next Page Link --}}
                                @if ($users->hasMorePages())
                                    <a href="{{ $users->nextPageUrl() }}" class="page-nav-btn">Berikutnya</a>
                                @else
                                    <span class="page-nav-btn disabled">Berikutnya</span>
                                @endif
                            </div>
                        @else
                            <div class="user-pagination-nav">
                                <span class="page-nav-btn disabled">Sebelumnya</span>
                                <span class="page-num-btn active">1</span>
                                <span class="page-nav-btn disabled">Berikutnya</span>
                            </div>
                        @endif
                    </div>
                </div>
            </main>

            <!-- ─── 28. Footer ─── -->
            @include('partials.footer', ['footerContext' => 'admin'])
        </div>
    </div>

    <!-- ─── 13. Add New User Modal ─── -->
    <div id="addUserModal" class="user-modal-overlay" onclick="closeAddModal(event)">
        <div class="user-modal-card" onclick="event.stopPropagation()">
            <div class="user-modal-header">
                <div>
                    <h3 class="user-modal-title">Tambah User Baru</h3>
                    <p class="user-modal-subtitle">Daftarkan akun explorer baru ke dalam platform</p>
                </div>
                <button type="button" class="user-modal-close-btn" onclick="hideAddModal()">&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                <div class="user-form-grid">
                    <div class="user-form-group full-width">
                        <label class="user-form-label">Nama Lengkap <span style="color: #dc2626;">*</span></label>
                        <input type="text" name="name" class="user-form-input" placeholder="Contoh: Nama Lengkap Explorer" required>
                    </div>

                    <div class="user-form-group">
                        <label class="user-form-label">Email <span style="color: #dc2626;">*</span></label>
                        <input type="email" name="email" class="user-form-input" placeholder="explorer@summit.id" required>
                    </div>

                    <div class="user-form-group">
                        <label class="user-form-label">Username</label>
                        <input type="text" name="username" class="user-form-input" placeholder="username_explorer">
                    </div>

                    <div class="user-form-group">
                        <label class="user-form-label">No. Telepon / WhatsApp</label>
                        <input type="text" name="phone" class="user-form-input" placeholder="081234567890">
                    </div>

                    <div class="user-form-group">
                        <label class="user-form-label">Domisili</label>
                        <input type="text" name="domicile" class="user-form-input" placeholder="Contoh: Jakarta">
                    </div>

                    <div class="user-form-group">
                        <label class="user-form-label">Status Akun <span style="color: #dc2626;">*</span></label>
                        <select name="status" class="user-form-select" required>
                            <option value="active" selected>Aktif</option>
                            <option value="inactive">Nonaktif</option>
                            <option value="pending_verification">Verifikasi Menunggu</option>
                            <option value="suspended">Diblokir</option>
                        </select>
                    </div>

                    <div class="user-form-group">
                        <label class="user-form-label">Password <span style="color: #dc2626;">*</span></label>
                        <input type="password" name="password" class="user-form-input" placeholder="Minimal 6 karakter" required minlength="6">
                    </div>
                </div>

                <div class="user-modal-actions">
                    <button type="button" class="btn-cancel-modal" onclick="hideAddModal()">Batal</button>
                    <button type="submit" class="btn-submit-modal">Simpan User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── 12. Edit User Modal ─── -->
    <div id="editUserModal" class="user-modal-overlay" onclick="closeEditModal(event)">
        <div class="user-modal-card" onclick="event.stopPropagation()">
            <div class="user-modal-header">
                <div>
                    <h3 class="user-modal-title">Edit Profil User</h3>
                    <p class="user-modal-subtitle">Perbarui data profil dan hak akses explorer</p>
                </div>
                <button type="button" class="user-modal-close-btn" onclick="hideEditModal()">&times;</button>
            </div>

            <form id="editUserForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="user-form-grid">
                    <div class="user-form-group full-width">
                        <label class="user-form-label">Nama Lengkap <span style="color: #dc2626;">*</span></label>
                        <input type="text" name="name" id="editName" class="user-form-input" required>
                    </div>

                    <div class="user-form-group">
                        <label class="user-form-label">Email <span style="color: #dc2626;">*</span></label>
                        <input type="email" name="email" id="editEmail" class="user-form-input" required>
                    </div>

                    <div class="user-form-group">
                        <label class="user-form-label">Username</label>
                        <input type="text" name="username" id="editUsername" class="user-form-input">
                    </div>

                    <div class="user-form-group">
                        <label class="user-form-label">No. Telepon / WhatsApp</label>
                        <input type="text" name="phone" id="editPhone" class="user-form-input">
                    </div>

                    <div class="user-form-group">
                        <label class="user-form-label">Domisili</label>
                        <input type="text" name="domicile" id="editDomicile" class="user-form-input">
                    </div>

                    <div class="user-form-group">
                        <label class="user-form-label">Status Akun <span style="color: #dc2626;">*</span></label>
                        <select name="status" id="editStatus" class="user-form-select" required>
                            <option value="active">Aktif</option>
                            <option value="inactive">Nonaktif</option>
                            <option value="pending_verification">Verifikasi Menunggu</option>
                            <option value="suspended">Diblokir</option>
                        </select>
                    </div>

                    <div class="user-form-group">
                        <label class="user-form-label">Password Baru (Opsional)</label>
                        <input type="password" name="password" id="editPassword" class="user-form-input" placeholder="Kosongkan jika tidak diubah" minlength="6">
                    </div>
                </div>

                <div class="user-modal-actions">
                    <button type="button" class="btn-cancel-modal" onclick="hideEditModal()">Batal</button>
                    <button type="submit" class="btn-submit-modal">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── 14. Change Status Modal ─── -->
    <div id="statusUserModal" class="user-modal-overlay" onclick="closeStatusModal(event)">
        <div class="user-modal-card user-status-modal" onclick="event.stopPropagation()">
            <div class="user-modal-header">
                <div>
                    <h3 class="user-modal-title">Ubah Status Akun</h3>
                    <p class="user-modal-subtitle user-status-subtitle" id="statusModalUserName">User Explorer</p>
                </div>
                <button type="button" class="user-modal-close-btn" onclick="hideStatusModal()">&times;</button>
            </div>

            <form id="statusUserForm" method="POST" action="">
                @csrf
                @method('PATCH')

                <div class="user-status-current">
                    <span class="user-status-current-label">Status saat ini</span>
                    <span id="statusCurrentBadge" class="user-status-badge user-badge-active">
                        <span class="badge-dot"></span>
                        <span id="statusCurrentText">Aktif</span>
                    </span>
                </div>

                <div class="user-form-group user-status-select-group">
                    <label class="user-form-label">Pilih Status Baru <span style="color: #dc2626;">*</span></label>
                    <select name="status" id="changeStatusSelect" class="user-form-select" required>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                        <option value="suspended">Diblokir</option>
                        <option value="pending_verification">Verifikasi Menunggu</option>
                    </select>
                </div>

                <div class="user-status-legend" aria-hidden="true">
                    <div class="user-status-legend-item">
                        <span class="user-status-legend-dot active"></span>
                        <span><strong>Aktif</strong> — akses normal dan dapat menyewa alat</span>
                    </div>
                    <div class="user-status-legend-item">
                        <span class="user-status-legend-dot inactive"></span>
                        <span><strong>Nonaktif</strong> — akun tidak aktif sementara</span>
                    </div>
                    <div class="user-status-legend-item">
                        <span class="user-status-legend-dot suspended"></span>
                        <span><strong>Diblokir</strong> — akses diblokir oleh admin</span>
                    </div>
                    <div class="user-status-legend-item">
                        <span class="user-status-legend-dot pending"></span>
                        <span><strong>Verifikasi Menunggu</strong> — memerlukan verifikasi KTP/identitas</span>
                    </div>
                </div>

                <div class="user-modal-actions">
                    <button type="button" class="btn-cancel-modal" onclick="hideStatusModal()">Batal</button>
                    <button type="submit" class="btn-submit-modal">Perbarui Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── 15. Delete User Confirmation Modal ─── -->
    <div id="deleteUserModal" class="user-modal-overlay" onclick="closeDeleteModal(event)">
        <div class="user-modal-card" style="max-width: 440px;" onclick="event.stopPropagation()">
            <div class="user-modal-header">
                <div>
                    <h3 class="user-modal-title" style="color: #dc2626;">Hapus User</h3>
                    <p class="user-modal-subtitle">Konfirmasi penghapusan akun pengguna</p>
                </div>
                <button type="button" class="user-modal-close-btn" onclick="hideDeleteModal()">&times;</button>
            </div>

            <p style="font-size: 13px; color: #374151; line-height: 1.5; margin-bottom: 14px;">
                Apakah Anda yakin ingin menghapus user <strong id="deleteUserName" style="color: #111827;">Nama User</strong>?
            </p>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; font-size: 12px; color: #64748b; margin-bottom: 20px; line-height: 1.5;">
                <span style="font-weight: 700; color: #334155;">Informasi Keamanan Data:</span><br>
                Sistem menggunakan <em>Soft Delete</em> sehingga riwayat penyewaan, bukti pembayaran, dan audit finansial user tetap tersimpan secara aman.
            </div>

            <form id="deleteUserForm" method="POST" action="">
                @csrf
                @method('DELETE')
                <div class="user-modal-actions">
                    <button type="button" class="btn-cancel-modal" onclick="hideDeleteModal()">Batal</button>
                    <button type="submit" class="btn-delete-confirm">Ya, Hapus User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── 15b. Verifikasi Persetujuan Orang Tua Modal ─── -->
    <div id="consentUserModal" class="user-modal-overlay" onclick="closeConsentModal(event)">
        <div class="user-modal-card user-status-modal" style="max-width: 460px;" onclick="event.stopPropagation()">
            <div class="user-modal-header">
                <div>
                    <h3 class="user-modal-title">Verifikasi Persetujuan Orang Tua</h3>
                    <p class="user-modal-subtitle user-status-subtitle" id="consentModalUserName">User Explorer</p>
                </div>
                <button type="button" class="user-modal-close-btn" onclick="hideConsentModal()">&times;</button>
            </div>

            <form id="consentUserForm" method="POST" action="">
                @csrf
                @method('POST')

                <div class="user-status-current">
                    <span class="user-status-current-label">Status persetujuan</span>
                    <span id="consentCurrentBadge" class="user-status-badge user-badge-pending">
                        <span class="badge-dot"></span>
                        <span id="consentCurrentText">MENUNGGU VERIFIKASI</span>
                    </span>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px 16px; font-size: 12.5px; margin: 12px 0 16px;">
                    <div>
                        <div style="color: #94a3b8; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 2px;">Tanggal Lahir</div>
                        <div id="consentDob" style="font-weight: 600; color: #1f2937;">-</div>
                    </div>
                    <div>
                        <div style="color: #94a3b8; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 2px;">Umur</div>
                        <div id="consentAge" style="font-weight: 600; color: #1f2937;">-</div>
                    </div>
                    <div style="grid-column: span 2;">
                        <div style="color: #94a3b8; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 2px;">Orang Tua / Wali</div>
                        <div id="consentParent" style="font-weight: 600; color: #1f2937;">-</div>
                    </div>
                </div>

                <p style="font-size: 13px; color: #374151; line-height: 1.5; margin-bottom: 14px;">
                    Telah memeriksa bukti persetujuan? Anda dapat
                    <a id="consentProofLink" href="#" target="_blank" rel="noopener" style="color: #166534; font-weight: 700;">membuka dokumen</a>
                    lalu memutuskan statusnya di bawah.
                </p>

                <div class="user-form-group user-status-select-group">
                    <label class="user-form-label">Keputusan <span style="color: #dc2626;">*</span></label>
                    <select name="parent_consent_status" id="consentDecisionSelect" class="user-form-select" required>
                        <option value="verified">Terverifikasi</option>
                        <option value="rejected">Ditolak</option>
                    </select>
                </div>

                <div class="user-form-group" id="consentReasonGroup" style="display: none;">
                    <label class="user-form-label">Alasan Penolakan <span style="color: #dc2626;">*</span></label>
                    <textarea name="rejection_reason" id="consentRejectionReason" class="user-form-input" rows="3" style="resize: vertical; height: auto; min-height: 72px;" placeholder="Jelaskan alasan mengapa bukti tidak diterima."></textarea>
                </div>

                <div class="user-modal-actions">
                    <button type="button" class="btn-cancel-modal" onclick="hideConsentModal()">Batal</button>
                    <button type="submit" class="btn-submit-modal">Simpan Keputusan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── 16. User Detail Modal ─── -->
    <div id="detailUserModal" class="user-modal-overlay" onclick="closeDetailModal(event)">
        <div class="user-modal-card" style="max-width: 500px;" onclick="event.stopPropagation()">
            <div class="user-modal-header">
                <div>
                    <h3 class="user-modal-title">Detail Explorer</h3>
                    <p class="user-modal-subtitle">Informasi lengkap profil dan riwayat aktivitas</p>
                </div>
                <button type="button" class="user-modal-close-btn" onclick="hideDetailModal()">&times;</button>
            </div>

            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9;">
                <div id="detailAvatarWrapper">
                    <img id="detailAvatarImg" src="" alt="Avatar" class="user-avatar-img" style="width: 56px; height: 56px; display: none;">
                    <div id="detailAvatarInitials" class="user-avatar-initials" style="width: 56px; height: 56px; font-size: 18px; display: flex;">U</div>
                </div>
                <div>
                    <h4 id="detailName" style="font-size: 16px; font-weight: 800; color: #111827; margin: 0 0 4px 0;">User Name</h4>
                    <div id="detailEmail" style="font-size: 13px; color: #6b7280; margin-bottom: 4px;">email@domain.com</div>
                    <span id="detailStatusBadge" class="user-status-badge user-badge-active">
                        <span class="badge-dot"></span>
                        <span id="detailStatusText">AKTIF</span>
                    </span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px 16px; font-size: 13px; margin-bottom: 24px;">
                <div>
                    <div style="color: #94a3b8; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 2px;">Username</div>
                    <div id="detailUsername" style="font-weight: 600; color: #1f2937; font-family: monospace;">@username</div>
                </div>
                <div>
                    <div style="color: #94a3b8; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 2px;">Nomor Telepon</div>
                    <div id="detailPhone" style="font-weight: 600; color: #1f2937;">-</div>
                </div>
                <div>
                    <div style="color: #94a3b8; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 2px;">Domisili</div>
                    <div id="detailDomicile" style="font-weight: 600; color: #1f2937;">-</div>
                </div>
                <div>
                    <div style="color: #94a3b8; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 2px;">Total Penyewaan</div>
                    <div id="detailOrdersCount" style="font-weight: 700; color: #185d31;">0 Pesanan</div>
                </div>
                <div style="grid-column: span 2;">
                    <div style="color: #94a3b8; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 2px;">Terdaftar Sejak</div>
                    <div id="detailCreatedAt" style="font-weight: 600; color: #1f2937;">-</div>
                </div>
            </div>

            <div style="border-top: 1px solid #f1f5f9; padding-top: 16px;">
                <div style="color: #94a3b8; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 10px;">Dokumen Verifikasi</div>

                {{-- User >= 17 tahun: KTP user sendiri --}}
                <div id="detailDocKtpUser">
                    <div style="font-size: 12px; font-weight: 700; color: #1f2937; margin-bottom: 6px;">KTP User</div>
                    <div id="detailKtpEmpty" style="font-size: 12.5px; color: #94a3b8;">KTP belum tersedia.</div>
                    <img id="detailKtpImg"
                        src=""
                        alt="Foto KTP User"
                        title="Klik untuk memperbesar"
                        onclick="openKtpViewer(this.src)"
                        style="display: none; max-width: 100%; max-height: 240px; object-fit: contain; border-radius: 10px; border: 1px solid #e5e7eb; background: #f8fafc; cursor: zoom-in;">
                </div>

                {{-- User < 17 tahun: KTP orang tua/wali --}}
                <div id="detailDocKtpOrtu" style="display: none;">
                    <div style="font-size: 12px; font-weight: 700; color: #1f2937; margin-bottom: 6px;">KTP Orang Tua / Wali</div>
                    <div id="detailKtpOrtuEmpty" style="font-size: 12.5px; color: #94a3b8;">KTP orang tua belum tersedia.</div>
                    <img id="detailKtpOrtuImg"
                        src=""
                        alt="Foto KTP Orang Tua"
                        title="Klik untuk memperbesar"
                        onclick="openKtpViewer(this.src)"
                        style="display: none; max-width: 100%; max-height: 240px; object-fit: contain; border-radius: 10px; border: 1px solid #e5e7eb; background: #f8fafc; cursor: zoom-in;">
                </div>

                {{-- User < 17 tahun: kartu pelajar --}}
                <div id="detailDocKartuPelajar" style="display: none;">
                    <div style="font-size: 12px; font-weight: 700; color: #1f2937; margin-bottom: 6px;">Kartu Pelajar</div>
                    <div id="detailKartuEmpty" style="font-size: 12.5px; color: #94a3b8;">Kartu pelajar belum tersedia.</div>
                    <img id="detailKartuImg"
                        src=""
                        alt="Foto Kartu Pelajar"
                        title="Klik untuk memperbesar"
                        onclick="openKtpViewer(this.src)"
                        style="display: none; max-width: 100%; max-height: 240px; object-fit: contain; border-radius: 10px; border: 1px solid #e5e7eb; background: #f8fafc; cursor: zoom-in;">
                </div>
                <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Klik foto untuk melihat ukuran lebih besar.</div>
            </div>

            <div style="border-top: 1px solid #f1f5f9; padding-top: 16px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <div style="color: #94a3b8; font-size: 11px; font-weight: 700; text-transform: uppercase;">Persetujuan Orang Tua</div>
                    <span id="detailConsentBadge" class="user-status-badge user-badge-active">
                        <span class="badge-dot"></span>
                        <span id="detailConsentText">TIDAK DIPERLUKAN</span>
                    </span>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px 16px; font-size: 13px;">
                    <div>
                        <div style="color: #94a3b8; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 2px;">Tanggal Lahir</div>
                        <div id="detailConsentDob" style="font-weight: 600; color: #1f2937;">-</div>
                    </div>
                    <div>
                        <div style="color: #94a3b8; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 2px;">Umur</div>
                        <div id="detailConsentAge" style="font-weight: 600; color: #1f2937;">-</div>
                    </div>
                    <div>
                        <div style="color: #94a3b8; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 2px;">Nama Orang Tua / Wali</div>
                        <div id="detailConsentParentName" style="font-weight: 600; color: #1f2937;">-</div>
                    </div>
                    <div>
                        <div style="color: #94a3b8; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 2px;">Hubungan</div>
                        <div id="detailConsentRelation" style="font-weight: 600; color: #1f2937;">-</div>
                    </div>
                </div>

                <div style="margin-top: 10px;">
                    <div style="color: #94a3b8; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Bukti Persetujuan</div>
                    <a id="detailConsentProofLink" href="#" target="_blank" rel="noopener" style="display: none; align-items: center; gap: 8px; background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; text-decoration: none; padding: 10px 14px; border-radius: 10px; font-size: 12.5px; font-weight: 700;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M9 15l2 2 4-4"></path></svg>
                        Lihat dokumen persetujuan
                    </a>
                    <div id="detailConsentProofEmpty" style="font-size: 12.5px; color: #94a3b8;">Tidak ada bukti yang diunggah.</div>
                    <div id="detailConsentRejected" style="display: none; margin-top: 8px; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 10px 14px; border-radius: 10px; font-size: 12px; line-height: 1.5;">
                        <strong>Alasan penolakan:</strong> <span id="detailConsentRejectedText"></span>
                    </div>
                </div>
            </div>

            <div class="user-modal-actions">
                <button type="button" class="btn-cancel-modal" onclick="hideDetailModal()">Tutup</button>
            </div>
        </div>
    </div>

    <!-- ─── KTP Viewer (Perbesar Foto KTP User) ─── -->
    <div id="ktpViewer" class="user-modal-overlay" onclick="closeKtpViewer()">
        <div style="background: #fff; border-radius: 16px; padding: 16px; max-width: min(92vw, 560px);" onclick="event.stopPropagation()">
            <img id="ktpViewerImg" src="" alt="Foto KTP User" style="display: block; max-width: 100%; max-height: 80vh; object-fit: contain; border-radius: 10px;">
            <div style="text-align: right; margin-top: 12px;">
                <button type="button" class="btn-cancel-modal" onclick="closeKtpViewer()">Tutup</button>
            </div>
        </div>
    </div>

    <!-- ─── JavaScript Modal Controls ─── -->
    <script>
        function openAddModal() {
            document.getElementById('addUserModal').classList.add('show');
        }
        function hideAddModal() {
            document.getElementById('addUserModal').classList.remove('show');
        }
        function closeAddModal(e) {
            if (e.target === document.getElementById('addUserModal')) hideAddModal();
        }

        function openEditModal(data) {
            document.getElementById('editUserForm').action = "/admin/users/" + data.id;
            document.getElementById('editName').value = data.name;
            document.getElementById('editEmail').value = data.email;
            document.getElementById('editUsername').value = data.username || '';
            document.getElementById('editPhone').value = data.phone || '';
            document.getElementById('editDomicile').value = data.domicile || '';
            document.getElementById('editStatus').value = (data.status === 'pending') ? 'pending_verification' : data.status;
            document.getElementById('editPassword').value = '';
            document.getElementById('editUserModal').classList.add('show');
        }
        function hideEditModal() {
            document.getElementById('editUserModal').classList.remove('show');
        }
        function closeEditModal(e) {
            if (e.target === document.getElementById('editUserModal')) hideEditModal();
        }

        function openStatusModal(data) {
            document.getElementById('statusUserForm').action = "/admin/users/" + data.id + "/status";
            document.getElementById('statusModalUserName').textContent = data.name + ' (' + data.email + ')';
            document.getElementById('changeStatusSelect').value = (data.status === 'pending') ? 'pending_verification' : data.status;

            const badge = document.getElementById('statusCurrentBadge');
            badge.className = 'user-status-badge';
            if (data.status === 'active') badge.classList.add('user-badge-active');
            else if (data.status === 'inactive') badge.classList.add('user-badge-inactive');
            else if (data.status === 'suspended') badge.classList.add('user-badge-suspended');
            else badge.classList.add('user-badge-pending');
            document.getElementById('statusCurrentText').textContent = data.status_label;

            document.getElementById('statusUserModal').classList.add('show');
        }
        function hideStatusModal() {
            document.getElementById('statusUserModal').classList.remove('show');
        }
        function closeStatusModal(e) {
            if (e.target === document.getElementById('statusUserModal')) hideStatusModal();
        }

        function openDeleteModal(data) {
            document.getElementById('deleteUserForm').action = "/admin/users/" + data.id;
            document.getElementById('deleteUserName').textContent = data.name;
            document.getElementById('deleteUserModal').classList.add('show');
        }
        function hideDeleteModal() {
            document.getElementById('deleteUserModal').classList.remove('show');
        }
        function closeDeleteModal(e) {
            if (e.target === document.getElementById('deleteUserModal')) hideDeleteModal();
        }

        function openConsentModal(data) {
            document.getElementById('consentUserForm').action = "/admin/users/" + data.id + "/parent-consent";
            document.getElementById('consentModalUserName').textContent = data.name + ' (' + data.email + ')';
            document.getElementById('consentDob').textContent = data.dob || '-';
            document.getElementById('consentAge').textContent = (data.age != null ? data.age + ' tahun' : '-');
            document.getElementById('consentParent').textContent = data.parent_name ? (data.parent_name + ' — ' + data.parent_relation) : '-';

            const proofLink = document.getElementById('consentProofLink');
            if (data.consent_proof_url) {
                proofLink.href = data.consent_proof_url;
                proofLink.style.display = 'inline';
            } else {
                proofLink.style.display = 'none';
            }

            const badge = document.getElementById('consentCurrentBadge');
            badge.className = 'user-status-badge';
            if (data.consent_status === 'verified') badge.classList.add('user-badge-success');
            else if (data.consent_status === 'rejected') badge.classList.add('user-badge-suspended');
            else badge.classList.add('user-badge-pending');
            document.getElementById('consentCurrentText').textContent = data.consent_label || 'MENUNGGU VERIFIKASI';

            document.getElementById('consentDecisionSelect').value = 'verified';
            document.getElementById('consentReasonGroup').style.display = 'none';
            document.getElementById('consentRejectionReason').value = '';

            document.getElementById('consentUserModal').classList.add('show');
        }
        function hideConsentModal() {
            document.getElementById('consentUserModal').classList.remove('show');
        }
        function closeConsentModal(e) {
            if (e.target === document.getElementById('consentUserModal')) hideConsentModal();
        }

        document.getElementById('consentDecisionSelect')?.addEventListener('change', function () {
            const showReason = this.value === 'rejected';
            document.getElementById('consentReasonGroup').style.display = showReason ? 'block' : 'none';
            document.getElementById('consentRejectionReason').required = showReason;
        });

        function openDetailModal(data) {
            document.getElementById('detailName').textContent = data.name;
            document.getElementById('detailEmail').textContent = data.email;
            document.getElementById('detailUsername').textContent = data.username ? '@' + data.username : '-';
            document.getElementById('detailPhone').textContent = data.phone || '-';
            document.getElementById('detailDomicile').textContent = data.domicile || '-';
            document.getElementById('detailOrdersCount').textContent = (data.orders_count || 0) + ' Pesanan';
            document.getElementById('detailCreatedAt').textContent = data.created_at || '-';
            document.getElementById('detailStatusText').textContent = data.status_label;

            const badge = document.getElementById('detailStatusBadge');
            badge.className = 'user-status-badge';
            if (data.status === 'active') badge.classList.add('user-badge-active');
            else if (data.status === 'inactive') badge.classList.add('user-badge-inactive');
            else if (data.status === 'suspended') badge.classList.add('user-badge-suspended');
            else badge.classList.add('user-badge-pending');

            const img = document.getElementById('detailAvatarImg');
            const initials = document.getElementById('detailAvatarInitials');
            if (data.avatar_path) {
                img.src = data.avatar_path;
                img.style.display = 'block';
                initials.style.display = 'none';
            } else {
                img.style.display = 'none';
                initials.textContent = data.name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) || 'U';
                initials.style.display = 'flex';
            }

            const ktpImg = document.getElementById('detailKtpImg');
            const ktpEmpty = document.getElementById('detailKtpEmpty');
            if (data.ktp_url) {
                ktpImg.src = data.ktp_url;
                ktpImg.style.display = 'block';
                ktpEmpty.style.display = 'none';
            } else {
                ktpImg.style.display = 'none';
                ktpEmpty.style.display = 'block';
            }

            if (data.is_minor) {
                document.getElementById('detailDocKtpUser').style.display = 'none';
                document.getElementById('detailDocKtpOrtu').style.display = 'block';
                document.getElementById('detailDocKartuPelajar').style.display = 'block';
            } else {
                document.getElementById('detailDocKtpUser').style.display = 'block';
                document.getElementById('detailDocKtpOrtu').style.display = 'none';
                document.getElementById('detailDocKartuPelajar').style.display = 'none';
            }

            const ktpOrtuImg = document.getElementById('detailKtpOrtuImg');
            const ktpOrtuEmpty = document.getElementById('detailKtpOrtuEmpty');
            if (data.ktp_orang_tua_url) {
                ktpOrtuImg.src = data.ktp_orang_tua_url;
                ktpOrtuImg.style.display = 'block';
                ktpOrtuEmpty.style.display = 'none';
            } else {
                ktpOrtuImg.style.display = 'none';
                ktpOrtuEmpty.style.display = 'block';
            }

            const kartuImg = document.getElementById('detailKartuImg');
            const kartuEmpty = document.getElementById('detailKartuEmpty');
            if (data.kartu_pelajar_url) {
                kartuImg.src = data.kartu_pelajar_url;
                kartuImg.style.display = 'block';
                kartuEmpty.style.display = 'none';
            } else {
                kartuImg.style.display = 'none';
                kartuEmpty.style.display = 'block';
            }

            // Persetujuan Orang Tua
            const consentBadge = document.getElementById('detailConsentBadge');
            consentBadge.className = 'user-status-badge';
            if (data.consent_status === 'verified') consentBadge.classList.add('user-badge-success');
            else if (data.consent_status === 'rejected') consentBadge.classList.add('user-badge-suspended');
            else if (data.consent_status === 'pending' || data.consent_status === 'submitted') consentBadge.classList.add('user-badge-pending');
            else consentBadge.classList.add('user-badge-active');
            document.getElementById('detailConsentText').textContent = data.consent_label || 'TIDAK DIPERLUKAN';

            document.getElementById('detailConsentDob').textContent = data.dob || '-';
            document.getElementById('detailConsentAge').textContent = (data.age != null ? data.age + ' tahun' : '-');
            document.getElementById('detailConsentParentName').textContent = data.parent_name || '-';
            document.getElementById('detailConsentRelation').textContent = data.parent_relation || '-';

            const proofLink = document.getElementById('detailConsentProofLink');
            const proofEmpty = document.getElementById('detailConsentProofEmpty');
            if (data.consent_proof_url) {
                proofLink.href = data.consent_proof_url;
                proofLink.style.display = 'inline-flex';
                proofEmpty.style.display = 'none';
            } else {
                proofLink.style.display = 'none';
                proofEmpty.style.display = 'block';
            }

            const rejectedBox = document.getElementById('detailConsentRejected');
            if (data.consent_status === 'rejected' && data.rejection_reason) {
                document.getElementById('detailConsentRejectedText').textContent = data.rejection_reason;
                rejectedBox.style.display = 'block';
            } else {
                rejectedBox.style.display = 'none';
            }

            document.getElementById('detailUserModal').classList.add('show');
        }
        function hideDetailModal() {
            document.getElementById('detailUserModal').classList.remove('show');
        }
        function closeDetailModal(e) {
            if (e.target === document.getElementById('detailUserModal')) hideDetailModal();
        }

        function openKtpViewer(src) {
            document.getElementById('ktpViewerImg').src = src;
            document.getElementById('ktpViewer').classList.add('show');
        }

        function closeKtpViewer() {
            document.getElementById('ktpViewer').classList.remove('show');
            document.getElementById('ktpViewerImg').removeAttribute('src');
        }
    </script>
</body>
</html>
