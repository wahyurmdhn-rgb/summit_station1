<?php

namespace App\Support;

/**
 * Pembantu cache-busting untuk asset publik.
 *
 * Versi aset diambil dari `filemtime()` file terkait agar URL hanya berubah
 * ketika file benar-benar berubah (bukan pada setiap request seperti `time()`).
 *
 * Bila file tidak ada (mis. asset belum di-deploy), fungsi ini mengembalikan
 * versi statis `'1'` alih-alih melempar error, sehingga halaman tetap dapat
 * dirender dan aset yang hilang ditangani oleh fallback gambar/CSS di browser.
 */
final class AssetVersion
{
    /**
     * @var array<string, string> Cache hasil agar tidak memanggil filemtime berulang.
     */
    private static array $cache = [];

    /**
     * Versi cache-busting untuk asset di folder public.
     */
    public static function of(string $relativePath): string
    {
        if (isset(self::$cache[$relativePath])) {
            return self::$cache[$relativePath];
        }

        $path = public_path($relativePath);

        if (is_file($path)) {
            $mtime = @filemtime($path);

            return self::$cache[$relativePath] = ($mtime !== false ? (string) $mtime : '1');
        }

        return self::$cache[$relativePath] = '1';
    }
}
