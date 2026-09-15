<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Layanan terpusat untuk konfigurasi website (Admin → Website).
 *
 * Nilai disimpan sebagai key/value pada tabel `settings` dan dibaca
 * dari cache agar tidak query DB pada setiap render halaman publik.
 */
class SiteSettingsService
{
    /**
     * Default nilai pengaturan (dipakai bila belum disimpan di database).
     */
    public const DEFAULTS = [
        'site_name'        => 'Summit Station',
        'hotline'          => '+62 811-2345-6789',
        'email'            => 'support@summitstation.id',
        'address'          => 'SMK Negeri 1 Gunung Putri, Jl. Barokah No.06, Wanaherang, Kec. Gn. Putri, Kabupaten Bogor, Jawa Barat 16965, Indonesia',
        'operating_hours'  => 'Senin - Minggu: 07.00 - 21.00 WIB',
        'hero_title'       => 'EXPEDITION-GRADE OUTDOOR EQUIPMENT',
        'hero_subtitle'    => 'Sewa peralatan mendaki gunung dan outdoor kualitas pro-grade dengan jaminan kebersihan, keamanan, dan sanitasi prima.',
    ];

    private const CACHE_KEY = 'site_settings_map';

    public static function all(): array
    {
        try {
            /** @var \Illuminate\Support\Collection $rows */
            $rows = Cache::remember(self::CACHE_KEY, 3600, function () {
                return Setting::all(['key', 'value']);
            });

            $map = $rows->pluck('value', 'key')->toArray();
        } catch (\Throwable $e) {
            // Tabel `settings` belum tersedia (mis. saat migrasi belum berjalan).
            // Gunakan nilai default agar halaman publik tetap berfungsi.
            return self::DEFAULTS;
        }

        return array_merge(self::DEFAULTS, $map);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => is_scalar($value) ? (string) $value : $value]);
        Cache::forget(self::CACHE_KEY);
    }

    public static function saveMany(array $data): void
    {
        foreach ($data as $key => $value) {
            if (array_key_exists($key, self::DEFAULTS)) {
                self::set($key, $value);
            }
        }
    }
}
