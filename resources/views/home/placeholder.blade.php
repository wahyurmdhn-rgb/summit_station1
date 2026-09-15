<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}">
    <title>{{ $title }} - Summit Station</title>
    <link rel="stylesheet" href="{{ asset('css/summit-home.css') . '?v=' . filemtime(public_path('css/summit-home.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-footer.css') . '?v=' . filemtime(public_path('css/summit-footer.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/summit-navbar.css') . '?v=' . filemtime(public_path('css/summit-navbar.css')) }}">
</head>
<body>
    @include('layouts.navbar')

    <main style="min-height: 60vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 20px; text-align: center;">
        <h1 style="font-size: 36px; font-weight: 800; color: #174e2b; margin-bottom: 16px;">Halaman {{ $title }}</h1>
        <p style="font-size: 15px; color: #555; max-width: 460px; margin-bottom: 24px;">Fitur ini sedang dalam pengembangan tahap lanjut. Anda dapat kembali ke beranda untuk melihat penawaran peralatan sewa terbaru.</p>
        <a href="{{ url('/') }}" class="btn-primary" style="display: inline-flex; padding: 12px 24px; background: #175e30; color: #fff; border-radius: 8px; font-weight: 700;">&larr; Kembali ke Beranda</a>
    </main>

    <!-- Footer -->
    @include('partials.footer', ['footerContext' => 'user'])
    <script src="{{ asset('js/summit-navbar.js') }}"></script>
</body>
</html>
