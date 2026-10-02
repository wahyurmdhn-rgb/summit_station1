<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Jika belum login sama sekali, arahkan ke login admin
        if (! $request->session()->has('account_id')) {
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Silakan masuk sebagai admin terlebih dahulu.']);
        }

        // Jika login tapi bukan admin (misal customer biasa)
        if ($request->session()->get('account_role') !== 'admin') {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin administrator.');
        }

        // Session admin tidak cukup dipercaya: record admin divalidasi ulang
        // ke tabel `admin` supaya admin yang dinonaktifkan/dihapus setelah
        // login langsung kehilangan akses, dan agar session yang dipalsukan
        // tidak bisa menembus panel admin.
        $admin = Admin::find($request->session()->get('account_id'));
        if (! $admin) {
            $request->session()->forget(['account_id', 'account_name', 'account_username', 'account_role', 'account_avatar']);

            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Akun administrator tidak ditemukan atau sudah tidak aktif.']);
        }

        // Status harus benar-benar 'active'. Nilai NULL/kosong dianggap tidak
        // aktif (fail-closed), bukan lolos begitu saja.
        if ($admin->status !== 'active') {
            $request->session()->forget(['account_id', 'account_name', 'account_username', 'account_role', 'account_avatar']);

            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Akun administrator Anda sedang dinonaktifkan atau dibekukan.']);
        }

        return $next($request);
    }
}
