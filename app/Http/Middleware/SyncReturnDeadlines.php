<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\ReturnNotificationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sinkronkan notifikasi tenggat pengembalian saat user membuka halaman.
 *
 * Inti: waktu SEPENUHNYA dihitung dari waktu server terhadap rent_end yang
 * tersimpan — bukan timer JavaScript. Dengan begitu, meskipun browser ditutup,
 * user logout, atau komputer dimatikan, begitu user kembali mengakses website
 * sistem langsung mengevaluasi apakah deadline sudah dekat / tiba / terlewat.
 *
 * Aman dipanggil berulang kali: ReturnNotificationService mencegah duplikasi
 * (satu notifikasi per type + kode booking).
 */
class SyncReturnDeadlines
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('account_id')
            && $request->session()->get('account_role') === 'customer') {
            $user = User::find($request->session()->get('account_id'));

            if ($user && $user->status === 'active') {
                ReturnNotificationService::dispatchForUser($user);
            }
        }

        return $next($request);
    }
}