@extends('layouts.summit', ['title' => $isAdmin ? 'Login Admin - Summit Station' : 'Masuk - Summit Station'])

@section('content')
<main class="auth-page">
    <section class="login-card">
        <img src="{{ asset('images/logo.png') }}" alt="Summit Station" class="brand-logo {{ $isAdmin ? 'brand-logo--admin' : '' }}">
        @if ($isAdmin)
            <h1>Login Admin</h1>
            <p>Masuk ke panel administrasi Summit Station.</p>
        @else
            <h1>Selamat Datang</h1>
            <p>Masuk untuk memulai petualangan Anda berikutnya bersama Summit Station.</p>
        @endif

        @if (session('status'))
            <div class="notice success">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="notice">{{ $errors->first() }}</div>
        @endif

        <form method="post" action="{{ $isAdmin ? route('admin.login.store') : route('login.store') }}" class="stack-form">
            @csrf
            @if (!$isAdmin && ($redirect = session('checkout_intended', request('redirect'))))
                <input type="hidden" name="redirect" value="{{ $redirect }}">
            @endif

            <label>
                <span>{{ $isAdmin ? 'Email Admin' : 'Email' }}</span>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required>
            </label>

            <label>
                <span class="label-row">Password @if (!$isAdmin) <a href="{{ route('contact.admin') }}">Lupa Kata Sandi?</a> @endif</span>
                <div class="password-field">
                    <input type="password" name="password" id="password" placeholder="••••••••" required>
                    <button type="button" class="password-toggle" data-password-toggle="password" aria-label="Tampilkan password" aria-pressed="false" tabindex="-1">
                        <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <svg class="eye-off-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18"></path><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path><path d="M9.9 4.2A10.8 10.8 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-3.2 4.4M6.6 6.6C3.2 8.8 1 12 1 12s4 8 11 8a10.8 10.8 0 0 0 3.1-.5"></path></svg>
                    </button>
                </div>
            </label>

            <button type="submit">{{ $isAdmin ? 'MASUK SEBAGAI ADMIN' : 'MASUK' }}</button>
        </form>

        @if (!$isAdmin)
        <p class="bottom-link">Belum punya akun? <a href="{{ route('register') }}">Daftar di sini</a></p>
        @endif
    </section>

    <div class="trust-row">
        <span>▦ Aman & Terenkripsi</span>
        <span>◎ Peralatan Pro-Grade</span>
    </div>
</main>
@endsection
