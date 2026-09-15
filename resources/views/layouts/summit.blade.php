<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') . '?v=' . filemtime(public_path('images/logo.png')) }}">
    <title>{{ $title ?? 'Summit Station' }}</title>
    <link rel="stylesheet" href="{{ asset('css/summit-auth.css') . '?v=' . filemtime(public_path('css/summit-auth.css')) }}">
</head>
<body>
    @yield('content')
    <script src="{{ asset('js/register-flow.js') }}"></script>
</body>
</html>
