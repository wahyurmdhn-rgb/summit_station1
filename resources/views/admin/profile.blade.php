<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}">
    <title>Profil Admin - Summit Station</title>
    <link rel="stylesheet" href="{{ asset('css/summit-admin.css') . '?v=' . time() }}">
    <link rel="stylesheet" href="{{ asset('css/summit-footer.css') . '?v=' . filemtime(public_path('css/summit-footer.css')) }}">
</head>
<body>

    <!-- Top Accent Line -->
    <div class="top-banner-line"></div>

    <div class="admin-layout">
        <!-- ─── Sidebar ─── -->
        @include('admin.partials.sidebar', ['activeMenu' => 'profile'])

        <!-- ─── Main Content Wrapper ─── -->
        <div class="admin-main">
            <!-- Top Header -->
            @include('admin.partials.header', [
                'adminPageTitle' => 'Profil Admin',
                'adminPageSubtitle' => 'Kelola informasi akun dan profil administrator',
            ])

            <!-- Content -->
            <main class="admin-content">

                @if (session('status'))
                    <div class="admin-flash-status">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="profile-error-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @php
                    $initials = Str::upper(
                        collect(explode(' ', $admin->name ?? ''))
                            ->filter()
                            ->take(2)
                            ->map(fn ($word) => mb_substr($word, 0, 1))
                            ->join('')
                    ) ?: 'A';
                    $adminId = '#' . str_pad((string) $admin->id_admin, 4, '0', STR_PAD_LEFT);
                @endphp

                <div class="profile-layout">

                    {{-- ═══ Profil Ringkas ═══ --}}
                    <aside class="profile-summary">
                        <div class="profile-card profile-summary-card">
                            <div class="profile-avatar" aria-hidden="true">{{ $initials }}</div>
                            <h2 class="profile-name">{{ $admin->name }}</h2>
                            <span class="profile-role-badge">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                </svg>
                                Administrator
                            </span>
                            <span class="profile-status"><span class="profile-status-dot"></span>Akun Aktif</span>

                            <div class="profile-divider"></div>

                            <div class="profile-meta">
                                <div class="profile-meta-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                        <polyline points="22,6 12,13 2,6"></polyline>
                                    </svg>
                                    <div>
                                        <span class="profile-meta-label">Email</span>
                                        <span class="profile-meta-value">{{ $admin->email }}</span>
                                    </div>
                                </div>
                                <div class="profile-meta-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    <div>
                                        <span class="profile-meta-label">Akun Dibuat</span>
                                        <span class="profile-meta-value">{{ $admin->created_at ? $admin->created_at->format('d M Y') : '—' }}</span>
                                    </div>
                                </div>
                                <div class="profile-meta-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                        <circle cx="9" cy="10" r="2"></circle>
                                        <path d="M5 18c.5-2.5 2-4 4.5-4h2.5c2 0 3.5 1.5 4 4"></path>
                                    </svg>
                                    <div>
                                        <span class="profile-meta-label">ID Admin</span>
                                        <span class="profile-meta-value">{{ $adminId }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </aside>

                    {{-- ═══ Kolom Utama ═══ --}}
                    <div class="profile-stack">

                        {{-- ❖ Informasi Akun --}}
                        <section class="profile-card">
                            <div class="profile-card-head">
                                <div class="profile-card-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="profile-card-title">Informasi Akun</h3>
                                    <p class="profile-card-subtitle">Data akun administrator yang sedang masuk</p>
                                </div>
                            </div>

                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Nama Lengkap</span>
                                    <span class="info-value">{{ $admin->name }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Email</span>
                                    <span class="info-value">{{ $admin->email }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Role</span>
                                    <span class="info-value"><span class="profile-role-chip">Administrator</span></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">ID Admin</span>
                                    <span class="info-value">{{ $adminId }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Akun Dibuat</span>
                                    <span class="info-value">{{ $admin->created_at ? $admin->created_at->format('d M Y') : '—' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Status Akun</span>
                                    <span class="info-value"><span class="profile-status-chip">Aktif</span></span>
                                </div>
                            </div>
                        </section>

                        {{-- ❖ Edit Profil --}}
                        <section class="profile-card">
                            <div class="profile-card-head">
                                <div class="profile-card-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="profile-card-title">Edit Profil</h3>
                                    <p class="profile-card-subtitle">Perbarui nama dan email akun administrator</p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('admin.profile.update') }}" data-loading-text="Menyimpan...">
                                @csrf
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="form-label" for="pf_name">Nama</label>
                                        <input id="pf_name" type="text" name="name" class="form-input {{ $errors->has('name') ? 'is-invalid' : '' }}" value="{{ old('name', $admin->name) }}" required>
                                        @error('name')
                                            <span class="form-error">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="pf_email">Email</label>
                                        <input id="pf_email" type="email" name="email" class="form-input {{ $errors->has('email') ? 'is-invalid' : '' }}" value="{{ old('email', $admin->email) }}" required>
                                        @error('email')
                                            <span class="form-error">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn-save">Simpan Perubahan</button>
                                </div>
                            </form>
                        </section>

                        {{-- ❖ Keamanan Akun --}}
                        <section class="profile-card">
                            <div class="profile-card-head">
                                <div class="profile-card-icon ic-shield">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="profile-card-title">Keamanan Akun</h3>
                                    <p class="profile-card-subtitle">Ganti kata sandi akun administrator</p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('admin.profile.password') }}" data-loading-text="Menyimpan...">
                                @csrf
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="form-label" for="pf_current_password">Password Saat Ini</label>
                                        <input id="pf_current_password" type="password" name="current_password" class="form-input {{ $errors->has('current_password') ? 'is-invalid' : '' }}" required autocomplete="current-password">
                                        @error('current_password')
                                            <span class="form-error">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="pf_new_password">Password Baru</label>
                                        <input id="pf_new_password" type="password" name="new_password" class="form-input {{ $errors->has('new_password') ? 'is-invalid' : '' }}" required minlength="8" autocomplete="new-password">
                                        @error('new_password')
                                            <span class="form-error">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="pf_new_password_confirmation">Konfirmasi Password Baru</label>
                                        <input id="pf_new_password_confirmation" type="password" name="new_password_confirmation" class="form-input {{ $errors->has('new_password_confirmation') ? 'is-invalid' : '' }}" required minlength="8" autocomplete="new-password">
                                        @error('new_password_confirmation')
                                            <span class="form-error">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="profile-card-foot">
                                    <p class="form-hint">Password minimal 8 karakter. Pastikan password lama benar sebelum menyimpan.</p>
                                    <button type="submit" class="btn-save">Ubah Password</button>
                                </div>
                            </form>
                        </section>

                    </div>
                </div>
            </main>
            @include('partials.footer', ['footerContext' => 'admin'])
        </div>
    </div>

    <script>
        (function () {
            var forms = document.querySelectorAll('form[data-loading-text]');
            Array.prototype.forEach.call(forms, function (form) {
                form.addEventListener('submit', function () {
                    var btn = form.querySelector('button[type="submit"]');
                    if (!btn) { return; }
                    btn.disabled = true;
                    btn.classList.add('is-loading');
                    btn.innerHTML = btn.dataset.loadingText || 'Menyimpan...';
                });
            });
        })();
    </script>
</body>
</html>