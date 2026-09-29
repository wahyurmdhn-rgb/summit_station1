<?php

use App\Support\AssetVersion;

if (! function_exists('asset_v')) {
    /**
     * Versi cache-busting yang stabil untuk asset di folder public.
     *
     * Berbeda dengan `time()` yang berubah setiap request, nilai ini hanya
     * berubah ketika file aset benar-benar dimodifikasi. Aman dipanggil
     * untuk file yang belum ada (mengembalikan '1'), sehingga halaman tidak
     * gagal dirender.
     */
    function asset_v(string $relativePath): string
    {
        return AssetVersion::of($relativePath);
    }
}
