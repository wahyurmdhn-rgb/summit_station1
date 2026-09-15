<?php

namespace App\Http\Middleware;

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

        return $next($request);
    }
}
