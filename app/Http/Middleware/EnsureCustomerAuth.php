<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerAuth
{
    /**
     * Handle an incoming request.
     * Memastikan user sudah login sebagai customer sebelum melakukan booking/payment.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isLoggedIn = $request->session()->has('account_id')
            && in_array($request->session()->get('account_role'), ['customer', 'admin']);

        if (! $isLoggedIn) {
            // Simpan URL tujuan agar setelah login bisa kembali ke halaman semula.
            // Gunakan key session khusus (bukan flash) agar tidak terhapus saat login page di-render.
            if ($request->isMethod('GET')) {
                $intended = $request->getPathInfo()
                    . ($request->getQueryString() ? '?' . $request->getQueryString() : '');
            } else {
                $referer = $request->headers->get('referer');
                if ($referer) {
                    $parsed = parse_url($referer);
                    $path = $parsed['path'] ?? '/';
                    $basePath = $request->getBasePath();
                    if ($basePath !== '' && str_starts_with($path, $basePath)) {
                        $path = substr($path, strlen($basePath)) ?: '/';
                    }
                    $intended = $path . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
                } else {
                    $intended = '/catalog';
                }
            }
            // Pastikan selalu URL relatif (hindari open redirect)
            if (! is_string($intended) || $intended === '' || str_starts_with($intended, '//') || ! str_starts_with($intended, '/')) {
                $intended = '/catalog';
            }
            $request->session()->put('checkout_intended', $intended);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Silakan login terlebih dahulu untuk melakukan booking.',
                ], 401);
            }

            return redirect()->route('login')
                ->withErrors(['email' => 'Silakan login terlebih dahulu untuk melakukan booking.']);
        }

        return $next($request);
    }
}
