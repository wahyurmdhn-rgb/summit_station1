<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\ReturnNotificationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sinkronkan notifikasi tenggat pengembalian saat user membuka halaman.
 *
 * Inti: waktu SEPENUHNYA dihitung dari waktu server terhadap rent_end yang
 * tersimpan — bukan timer JavaScript. Dengan begitu, meskipun browser ditutup,
 * user logout, atau komputer dimatikan, begitu user kembali mengakses website
 * sistem langsung mengevaluasi apakah deadline sudah dekat / tiba / terlewat.
 *
 * Ini adalah jaring pengaman (safety net) dari scheduler
 * `return-deadline:notify` yang berjalan per jam. Keduanya sengaja dipertahankan:
 * notifikasi tidak boleh hilang hanya karena cron belum dijalankan.
 *
 * Agar tidak menambah query database di SETIAP request, pemeriksaan ini
 * di-throttle lewat cache berumur pendek per user. Satu user yang membuka
 * banyak halaman hanya memicu satu evaluasi per jendela waktu, bukan satu
 * per request.
 *
 * Aman dipanggil berulang kali: ReturnNotificationService mencegah duplikasi
 * (satu notifikasi per type + kode booking).
 */
class SyncReturnDeadlines
{
    /**
     * Jendela throttle per user (detik).
     */
    private const THROTTLE_SECONDS = 300;

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('account_id')
            && $request->session()->get('account_role') === 'customer') {
            $userId = (int) $request->session()->get('account_id');
            $cacheKey = "return-deadline:sync:{$userId}";

            if (! Cache::has($cacheKey)) {
                $user = User::find($userId);

                if ($user && $user->status === 'active') {
                    ReturnNotificationService::dispatchForUser($user);
                }

                // Cache ditulis baik pada user aktif maupun tidak, supaya
                // user yang tidak aktif tidak menyalakan query tiap request.
                Cache::put($cacheKey, 1, self::THROTTLE_SECONDS);
            }
        }

        return $next($request);
    }
}
