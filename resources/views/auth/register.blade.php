@extends('layouts.summit', ['title' => 'Daftar Akun User - Summit Station'])

@section('content')
<main class="register-page">
    <section class="promo-copy">
        <div class="promo-inner">
            <span class="eyebrow">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                    <polyline points="2 17 12 22 22 17"></polyline>
                    <polyline points="2 12 12 17 22 12"></polyline>
                </svg>
                EKSPEDISI &amp; RENTAL OUTDOOR
            </span>
            <h1>Persiapkan <em>pendakian hebat</em> Anda berikutnya.</h1>
            <p>Summit Station menyediakan perlengkapan outdoor kelas profesional untuk pendaki, pemanjat, dan petualang. Buat akun untuk mengelola penyewaan Anda dan mengakses unit peralatan terbaik.</p>

            <div class="promo-highlights">
                <div class="highlight-item">
                    <div class="highlight-icon">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            <polyline points="9 12 11 14 15 10"></polyline>
                        </svg>
                    </div>
                    <div>
                        <strong>Peralatan Pro-Grade</strong>
                        <span>Diperiksa keamanannya dan disanitasi ketat sebelum digunakan.</span>
                    </div>
                </div>
                <div class="highlight-item">
                    <div class="highlight-icon">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 14 14"></polyline>
                        </svg>
                    </div>
                    <div>
                        <strong>Booking Cepat &amp; Transparan</strong>
                        <span>Pantau ketersediaan stok peralatan secara real-time.</span>
                    </div>
                </div>
            </div>

            <div class="mountain-photo">
                <img src="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=900&q=80" alt="Summit Station Mountain Expedition" loading="lazy">
                <div class="mountain-photo-overlay">
                    <span class="photo-tag">TERUJI DI GUNUNG</span>
                    <p>Siap mendukung ekspedisi ke puncak tertinggi.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="register-card">
        <!-- Logo Summit Station -->
        <div class="reg-brand-header">
            <a href="{{ route('home') }}" class="reg-logo-link" title="Summit Station">
                <img src="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}" alt="Summit Station" class="reg-brand-logo">
            </a>
            <div class="service-pill">
                <span class="service-dot"></span> Wilayah Jabodetabek
            </div>
        </div>

        <div class="reg-card-header">
            <h2 class="reg-title">Buat Akun</h2>
            <p class="reg-subtitle">Daftarkan data Anda untuk memulai perjalanan petualangan outdoor.</p>
        </div>

        @if (isset($errors) && $errors->any() && !$errors->has('username') && !$errors->has('email') && !$errors->has('phone') && !$errors->has('password') && !$errors->has('terms'))
            <div class="reg-notice reg-notice-error">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="post" action="{{ route('register.store') }}" enctype="multipart/form-data" class="register-form" data-register-form autocomplete="off">
            @csrf
            {{-- Hidden input to sync name with username --}}
            <input type="hidden" name="name" id="reg_name" value="{{ old('name', old('username', 'Member Summit')) }}">

            <!-- 1. Username -->
            <div class="reg-form-group" style="--field-idx: 0;">
                <label for="reg_username" class="reg-label">Username</label>
                <div class="reg-input-wrap @error('username') has-error @enderror">
                    <span class="input-leading-icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </span>
                    <input type="text" id="reg_username" name="username" value="{{ old('username') }}" placeholder="Masukkan username" required autofocus>
                </div>
                @error('username')
                    <span class="reg-field-error">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        {{ $message }}
                    </span>
                @enderror
            </div>

            <!-- 2. Email -->
            <div class="reg-form-group" style="--field-idx: 1;">
                <label for="reg_email" class="reg-label">Email</label>
                <div class="reg-input-wrap @error('email') has-error @enderror">
                    <span class="input-leading-icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                    </span>
                    <input type="email" id="reg_email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required>
                </div>
                @error('email')
                    <span class="reg-field-error">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        {{ $message }}
                    </span>
                @enderror
            </div>

            <!-- 3. No. Telp (Di bawah Email dan di atas Password) -->
            <div class="reg-form-group" style="--field-idx: 2;">
                <label for="reg_phone" class="reg-label">No. Telp</label>
                <div class="reg-input-wrap @error('phone') has-error @enderror">
                    <span class="input-leading-icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                    </span>
                    <input type="tel" id="reg_phone" name="phone" value="{{ old('phone') }}" placeholder="Masukkan nomor telepon" required>
                </div>
                @error('phone')
                    <span class="reg-field-error">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        {{ $message }}
                    </span>
                @enderror
            </div>

            <!-- 4. Domisili (Wilayah Layanan Jabodetabek) -->
            <div class="reg-form-group" style="--field-idx: 3;">
                <label for="reg_domicile" class="reg-label">Domisili</label>
                <div class="reg-input-wrap reg-select-wrap @error('domicile') has-error @enderror">
                    <span class="input-leading-icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                    </span>
                    <select id="reg_domicile" name="domicile" required>
                        <option value="">Pilih Domisili (Wilayah Jabodetabek)</option>
                        @foreach (['Jakarta', 'Bogor', 'Depok', 'Tangerang', 'Bekasi'] as $city)
                            <option value="{{ $city }}" @selected(old('domicile') === $city)>{{ $city }}</option>
                        @endforeach
                    </select>
                    <span class="select-chevron-icon" aria-hidden="true">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </span>
                </div>
                @error('domicile')
                    <span class="reg-field-error">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        {{ $message }}
                    </span>
                @enderror
            </div>

            <!-- 5. Password -->
            <div class="reg-form-group" style="--field-idx: 4;">
                <label for="password" class="reg-label">Password</label>
                <div class="reg-input-wrap @error('password') has-error @enderror">
                    <span class="input-leading-icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </span>
                    <input type="password" name="password" id="password" placeholder="Minimal 8 karakter" required>
                    <button type="button" class="password-toggle" data-password-toggle="password" aria-label="Tampilkan atau sembunyikan password" tabindex="-1">
                        <svg class="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <svg class="eye-off-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                    </button>
                </div>
                <!-- Password Strength Meter: Lemah -> Sedang -> Kuat -->
                <div class="pwd-strength-container" aria-live="polite">
                    <div class="pwd-strength-bar-track">
                        <div class="pwd-strength-bar" data-pwd-strength-bar></div>
                    </div>
                    <span class="pwd-strength-label" data-pwd-strength-text></span>
                </div>
                @error('password')
                    <span class="reg-field-error">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        {{ $message }}
                    </span>
                @enderror
            </div>

            <!-- 6. Konfirmasi Password -->
            <div class="reg-form-group" style="--field-idx: 5;">
                <label for="password_confirmation" class="reg-label">Konfirmasi Password</label>
                <div class="reg-input-wrap">
                    <span class="input-leading-icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            <polyline points="9 12 11 14 15 10"></polyline>
                        </svg>
                    </span>
                    <input type="password" name="password_confirmation" id="password_confirmation" placeholder="Ulangi password Anda" required>
                    <button type="button" class="password-toggle" data-password-toggle="password_confirmation" aria-label="Tampilkan atau sembunyikan konfirmasi password" tabindex="-1">
                        <svg class="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <svg class="eye-off-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                    </button>
                </div>
                <div class="pwd-match-hint" data-pwd-match-hint></div>
            </div>

            <!-- Checkbox Terms -->
            <div class="reg-form-group terms-check-group" style="--field-idx: 6;">
                <label class="custom-terms-label">
                    <input type="checkbox" name="terms" value="1" required checked>
                    <span class="terms-custom-box">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </span>
                    <span class="terms-text">
                        Saya menyetujui <button type="button" class="btn-text-link" data-open-terms onclick="document.querySelector('[data-terms-modal]').hidden = false; document.body.style.overflow='hidden'; document.querySelector('[data-terms-modal]').classList.add('is-open');">Persyaratan Layanan</button> Summit Station.
                    </span>
                </label>
                @error('terms')
                    <span class="reg-field-error">{{ $message }}</span>
                @enderror
            </div>

            <!-- Tombol Register -->
            <button class="btn-register-submit" type="submit" data-create-account style="--field-idx: 7;">
                <span class="btn-text">Buat Akun</span>
                <svg class="btn-arrow-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
        </form>

        <p class="bottom-link">
            Sudah punya akun? <a href="{{ route('login') }}" class="link-to-login">Login</a>
        </p>
    </section>
</main>

<section class="expedition-overlay" data-terms-modal hidden>
    <div class="expedition-shell terms-shell">
        <header class="modal-topbar">
            <div class="brand-badge-topbar">
                <span class="topbar-logo-icon">▲</span>
                <strong>SUMMIT STATION</strong>
            </div>
            <nav>
                <span class="topbar-badge">LEGAL &amp; KESELAMATAN</span>
                <span class="topbar-badge">RENTAL OUTDOOR</span>
            </nav>
        </header>
        <article class="terms-modal-card pasted-terms-card">
            <div class="terms-card-head">
                <div class="terms-head-meta">
                    <div class="terms-eyebrow">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                        <span>LEGAL &amp; KESELAMATAN</span>
                    </div>
                    <button class="round-close" type="button" data-close-terms aria-label="Tutup Syarat &amp; Ketentuan">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <h2>Syarat &amp; Ketentuan</h2>
                <p class="terms-head-desc">Harap baca seluruh ketentuan sewa perlengkapan outdoor Summit Station sebelum menyetujui.</p>
            </div>

            <div class="terms-progress-wrapper" aria-hidden="true">
                <div class="terms-progress-meta">
                    <span class="terms-progress-title">Progress Membaca</span>
                    <span class="terms-percent-badge" data-terms-percent>0%</span>
                </div>
                <div class="terms-progress">
                    <span data-terms-progress-bar></span>
                </div>
            </div>

            <div class="terms-scroll pasted-terms-scroll" data-terms-scroll>
                <!-- Section 1 -->
                <section class="terms-section-card">
                    <div class="terms-section-header">
                        <div class="terms-sec-badge">01</div>
                        <div class="terms-sec-icon">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <h3>1. Persyaratan Penyewaan</h3>
                    </div>
                    <ul class="terms-list">
                        <li>
                            <svg class="item-bullet-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Penyewa wajib mengisi data diri dengan benar dan lengkap.</span>
                        </li>
                        <li>
                            <svg class="item-bullet-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Penyewa wajib memberikan identitas yang masih berlaku apabila diperlukan.</span>
                        </li>
                        <li>
                            <svg class="item-bullet-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Penyewa harus berusia sesuai ketentuan yang ditetapkan oleh pihak rental.</span>
                        </li>
                        <li>
                            <svg class="item-bullet-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Data yang diberikan harus dapat dipertanggungjawabkan.</span>
                        </li>
                    </ul>
                </section>

                <!-- Section 2 -->
                <section class="terms-section-card">
                    <div class="terms-section-header">
                        <div class="terms-sec-badge">02</div>
                        <div class="terms-sec-icon">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                        <h3>2. Pemesanan</h3>
                    </div>
                    <ul class="terms-list">
                        <li>
                            <svg class="item-bullet-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Penyewa wajib memilih alat, jumlah, serta tanggal pengambilan dan pengembalian dengan benar.</span>
                        </li>
                        <li>
                            <svg class="item-bullet-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Pesanan dianggap berhasil setelah mendapatkan konfirmasi dari pihak rental.</span>
                        </li>
                        <li>
                            <svg class="item-bullet-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Ketersediaan alat dapat berubah sebelum pesanan dikonfirmasi.</span>
                        </li>
                    </ul>
                </section>

                <!-- Section 3 -->
                <section class="terms-section-card">
                    <div class="terms-section-header">
                        <div class="terms-sec-badge">03</div>
                        <div class="terms-sec-icon">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                        </div>
                        <h3>3. Pembayaran</h3>
                    </div>
                    <ul class="terms-list">
                        <li>
                            <svg class="item-bullet-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Penyewa wajib melakukan pembayaran sesuai batas waktu yang telah ditentukan.</span>
                        </li>
                        <li>
                            <svg class="item-bullet-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Pesanan yang belum dibayar sesuai batas waktu dapat dibatalkan.</span>
                        </li>
                    </ul>
                </section>

                <!-- Section 4 -->
                <section class="terms-section-card">
                    <div class="terms-section-header">
                        <div class="terms-sec-badge">04</div>
                        <div class="terms-sec-icon">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line>
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                <line x1="12" y1="22.08" x2="12" y2="12"></line>
                            </svg>
                        </div>
                        <h3>4. Pengambilan Alat</h3>
                    </div>
                    <ul class="terms-list">
                        <li>
                            <svg class="item-bullet-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Penyewa wajib mengambil alat sesuai jadwal yang telah ditentukan.</span>
                        </li>
                        <li>
                            <svg class="item-bullet-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Penyewa wajib memeriksa kondisi dan kelengkapan alat sebelum dibawa.</span>
                        </li>
                        <li>
                            <svg class="item-bullet-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Setelah alat diterima, penyewa bertanggung jawab atas alat selama masa penyewaan.</span>
                        </li>
                    </ul>
                </section>

                <!-- Section 5 -->
                <section class="terms-section-card">
                    <div class="terms-section-header">
                        <div class="terms-sec-badge">05</div>
                        <div class="terms-sec-icon">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="1 4 1 10 7 10"></polyline>
                                <polyline points="23 20 23 14 17 14"></polyline>
                                <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path>
                            </svg>
                        </div>
                        <h3>5. Pengembalian Alat</h3>
                    </div>
                    <ul class="terms-list">
                        <li>
                            <svg class="item-bullet-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Alat wajib dikembalikan sesuai tanggal dan waktu yang telah disepakati.</span>
                        </li>
                        <li>
                            <svg class="item-bullet-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Semua alat harus dikembalikan dalam kondisi dan jumlah yang sesuai saat diterima.</span>
                        </li>
                        <li>
                            <svg class="item-bullet-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Keterlambatan pengembalian dapat dikenakan biaya tambahan sesuai ketentuan rental.</span>
                        </li>
                    </ul>
                </section>

                <!-- Section 6 -->
                <section class="terms-section-card">
                    <div class="terms-section-header">
                        <div class="terms-sec-badge">06</div>
                        <div class="terms-sec-icon">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                        </div>
                        <h3>6. Kerusakan dan Kehilangan</h3>
                    </div>
                    <ul class="terms-list">
                        <li>
                            <svg class="item-bullet-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Kerusakan akibat penggunaan yang tidak sesuai dapat menjadi tanggung jawab penyewa.</span>
                        </li>
                        <li>
                            <svg class="item-bullet-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Kehilangan alat atau bagian dari alat dapat dikenakan biaya penggantian.</span>
                        </li>
                        <li>
                            <svg class="item-bullet-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Besarnya biaya kerusakan atau kehilangan ditentukan berdasarkan jenis dan kondisi alat.</span>
                        </li>
                    </ul>
                </section>

                <!-- Completion Banner (Appears when scrolled to bottom) -->
                <div class="terms-completion-banner" data-terms-completion-banner>
                    <div class="completion-icon-badge">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>
                    <div class="completion-text">
                        <strong>✓ Terima kasih sudah membaca Syarat &amp; Ketentuan</strong>
                        <span>Silakan centang persetujuan di bawah untuk melanjutkan pendaftaran.</span>
                    </div>
                </div>

                <div class="terms-end-mark">▲</div>
            </div>

            <footer class="terms-action">
                <label class="custom-accept" data-accept-row>
                    <input type="checkbox" data-accept-expedition>
                    <span class="fake-check">
                        <svg class="check-svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </span>
                    <span class="accept-label-text">Saya menyetujui Syarat &amp; Ketentuan Summit Station</span>
                </label>
                <button type="button" class="btn-terms-proceed" data-proceed-ktp disabled>
                    <span>LANJUTKAN</span>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </footer>
            <small class="terms-hint" data-terms-hint>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
                Gulir ke bawah untuk membaca seluruh poin
            </small>
        </article>
        <small class="modal-copy">© 2026 SUMMIT STATION EXPEDITION GEAR. HAK CIPTA DILINDUNGI.</small>
    </div>
</section>

<section class="expedition-overlay" data-ktp-modal hidden>
    <div class="expedition-shell ktp-shell">
        <header class="modal-topbar">
            <div class="brand-logo-modal">
                <div class="logo-badge-modal">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                        <polyline points="2 17 12 22 22 17"></polyline>
                        <polyline points="2 12 12 17 22 12"></polyline>
                    </svg>
                </div>
                <span>SUMMIT STATION</span>
            </div>
        </header>
        <article class="ktp-card">
            <aside class="ktp-photo">
                <img src="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=800&q=80" alt="Mountain">
                <div class="ktp-photo-overlay">
                    <div class="avatar-circle">
                        <img src="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=200&q=80" alt="Profile">
                    </div>
                    <span class="yellow-line"></span>
                    <h2>Langkah 2 dari 2</h2>
                    <p>Persiapkan diri untuk mendaki. Perjalanan Anda dimulai dari basecamp.</p>
                </div>
            </aside>
            <section class="ktp-form">
                <h2>UPLOAD KTP</h2>
                <p class="subtitle">SEBAGAI JAMINAN</p>
                <label class="upload-box">
                    <input type="file" name="ktp" accept=".jpg,.jpeg,.png" data-ktp-file>
                    <div class="camera-icon-circle">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#444" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                            <circle cx="12" cy="13" r="4"></circle>
                            <line x1="19" y1="10" x2="19" y2="14"></line>
                            <line x1="17" y1="12" x2="21" y2="12"></line>
                        </svg>
                    </div>
                    <div class="upload-btn-pill">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <span>UNGGAH DARI PERANGKAT</span>
                    </div>
                    <span class="drag-text">atau seret dan lepas di sini</span>
                    <small class="file-hint">JPEG, PNG hingga 10MB</small>
                </label>
                <div class="ktp-preview" data-ktp-preview hidden>
                    <img src="" alt="Preview KTP" data-ktp-preview-img>
                    <p class="ktp-preview-hint">Pastikan foto KTP terlihat jelas, tidak buram, dan seluruh bagian KTP terlihat.</p>
                    <span class="ktp-preview-name" data-ktp-preview-name></span>
                    <button type="button" class="ktp-preview-remove" data-ktp-preview-remove>&times;</button>
                </div>
                <div class="upload-file" data-upload-file></div>
                <div class="form-divider"></div>
                <button class="complete-btn" type="button" data-complete-register disabled>
                    <span>SELESAI</span>
                    <span>&rarr;</span>
                </button>
            </section>
        </article>
        <small class="modal-copy">&copy; 2026 SUMMIT STATION EXPEDITION GEAR. HAK CIPTA DILINDUNGI.</small>
    </div>
</section>
@endsection
