<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pelapor untuk kegagalan "lunak" (soft failure).
 *
 * Beberapa bagian aplikasi sengaja punya jalur cadangan agar halaman tetap
 * tampil walau tabel/kolom belum tersedia — mis. environment yang migrasinya
 * belum tuntas. Jalur cadangan tersebut TIDAK boleh menelan exception tanpa
 * jejak, karena itu kesalahan asli jadi mustahil didiagnosis.
 *
 * Class ini mencatat kegagalan tersebut tanpa membanjiri log:
 *  - error skema belum lengkap dianggap kondisi yang diharapkan, sehingga
 *    cukup dicatat sebagai warning satu kali;
 *  - pesan identik di dalam satu proses hanya dicatat sekali.
 */
final class ErrorReporter
{
    /**
     * @var array<string, bool> Pesan yang sudah dilaporkan dalam proses ini.
     */
    private static array $reported = [];

    /**
     * Catat kegagalan lunak beserta konteksnya.
     *
     * @param  array<string, mixed>  $context
     */
    public static function soft(Throwable $e, string $context, array $contextData = []): void
    {
        $key = $context.'|'.$e->getMessage();

        if (isset(self::$reported[$key])) {
            return;
        }

        self::$reported[$key] = true;

        $payload = $contextData + ['exception' => $e];

        if (self::isMissingSchemaError($e)) {
            Log::warning("Summit Station: {$context} — skema belum lengkap, memakai nilai cadangan.", $payload);

            return;
        }

        Log::error("Summit Station: {$context} — memakai nilai cadangan setelah error.", $payload);

        report($e);
    }

    /**
     * Apakah error ini karena tabel/kolom yang belum tersedia?
     */
    private static function isMissingSchemaError(Throwable $e): bool
    {
        $sqlState = method_exists($e, 'errorInfo') && isset($e->errorInfo[0])
            ? (string) $e->errorInfo[0]
            : '';

        if (in_array($sqlState, ['42S02', '42S22', '42703'], true)) {
            return true;
        }

        $message = strtolower($e->getMessage());

        foreach (['no such table', 'no such column', 'undefined table', 'undefined column', 'base table or view not found'] as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }
}
