<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}">
    <title>Alat Tidak Ditemukan - Summit Station</title>
    <link rel="stylesheet" href="{{ asset('css/summit-catalog.css') . '?v=' . filemtime(public_path('css/summit-catalog.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-footer.css') . '?v=' . filemtime(public_path('css/summit-footer.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-navbar.css') . '?v=' . filemtime(public_path('css/summit-navbar.css')) }}">
    <style>
        .not-found-wrapper {
            min-height: 60vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px 20px;
        }
        .not-found-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #fee2e2;
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        .not-found-title {
            font-size: 26px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 8px;
        }
        .not-found-desc {
            font-size: 15px;
            color: #6b7280;
            max-width: 480px;
            margin-bottom: 28px;
            line-height: 1.5;
        }
        .btn-back-catalog {
            background: #185d31;
            color: #ffffff;
            font-weight: 700;
            font-size: 14px;
            padding: 12px 28px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }
        .btn-back-catalog:hover {
            background: #114223;
        }
    </style>
</head>
<body>
    @include('layouts.navbar')

    <main class="not-found-wrapper">
        <div class="not-found-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
        </div>
        <h1 class="not-found-title">Alat Tidak Ditemukan</h1>
        <p class="not-found-desc">
            Peralatan atau paket sewa yang Anda cari tidak ditemukan atau telah dinonaktifkan dari etalase. Silakan telusuri koleksi perlengkapan gunung kami lainnya.
        </p>
        <a href="{{ route('catalog') }}" class="btn-back-catalog">
            <span>&larr; Kembali ke Katalog</span>
        </a>
    </main>

    <!-- Footer -->
    @include('partials.footer', ['footerContext' => 'user'])

    <script src="{{ asset('js/summit-navbar.js') }}"></script>
</body>
</html>
