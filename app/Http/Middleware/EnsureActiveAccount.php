<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    /**
     * Handle an incoming request.
     * Memastikan user yang sedang login memiliki status akun yang aktif (tidak SUSPENDED/INACTIVE).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('account_id') && $request->session()->get('account_role') === 'customer') {
            if ($request->is('admin*') && ! $request->is('admin/login')) {
                abort(403, 'Akses ditolak. Anda tidak memiliki izin administrator.');
            }

            $user = User::find($request->session()->get('account_id'));

            // 1. Jika akun user tidak ditemukan di database
            if (! $user) {
                $request->session()->forget(['account_id', 'account_name', 'account_username', 'account_role', 'account_avatar']);
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Akun tidak ditemukan di sistem.',
                    ], 401);
                }
                return redirect()->route('login')->withErrors(['email' => 'Sesi pengguna tidak valid.']);
            }

            // 2. Jika akun user berstatus SUSPENDED
            if ($user->status === 'suspended') {
                $request->session()->forget(['account_id', 'account_name', 'account_username', 'account_role', 'account_avatar']);

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Akun Anda telah ditangguhkan (SUSPENDED). Akses diblokir.',
                        'status' => 'suspended',
                    ], 403);
                }

                return redirect()->route('login')
                    ->withErrors(['email' => 'Akun Anda telah ditangguhkan (SUSPENDED). Silakan hubungi Administrator.']);
            }

            // 3. Jika akun user berstatus INACTIVE
            if ($user->status === 'inactive') {
                $request->session()->forget(['account_id', 'account_name', 'account_username', 'account_role', 'account_avatar']);

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Akun Anda berstatus nonaktif.',
                        'status' => 'inactive',
                    ], 403);
                }

                return redirect()->route('login')
                    ->withErrors(['email' => 'Akun Anda nonaktif. Silakan hubungi Administrator.']);
            }
        }

        return $next($request);
    }
}
