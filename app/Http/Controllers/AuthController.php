<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function customerLoginForm(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('account_id')) {
            if ($request->session()->get('account_role') === 'admin') {
                return redirect()->route('admin.dashboard');
            }
            return redirect('/');
        }

        return view('auth.login', ['isAdmin' => false]);
    }

    public function adminLoginForm(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('account_id')) {
            if ($request->session()->get('account_role') === 'admin') {
                return redirect()->route('admin.dashboard');
            }
            return redirect('/');
        }

        return view('auth.login', ['isAdmin' => true]);
    }

    public function customerLogin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'redirect' => ['nullable', 'string'],
        ]);

        // /login hanya mengenali akun CUSTOMER (tabel users).
        $account = User::where('email', $data['email'])->first();

        if (! $account || ! Hash::check($data['password'], $account->password)) {
            return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
        }

        if ($account->status === 'suspended') {
            return back()->withErrors([
                'email' => 'Akun Anda telah ditangguhkan (SUSPENDED). Silakan hubungi Administrator Summit Station.'
            ])->onlyInput('email');
        }

        if ($account->status === 'inactive') {
            return back()->withErrors([
                'email' => 'Akun Anda berstatus nonaktif. Silakan hubungi Administrator Summit Station.'
            ])->onlyInput('email');
        }

        session()->regenerate();
        session([
            'account_id' => $account->getKey(),
            'account_name' => $account->name,
            'account_username' => $account->username,
            'account_role' => 'customer',
            'account_avatar' => $account->avatar_path ?? null,
        ]);

        $intendedUrl = $this->resolveIntendedUrl($data['redirect'] ?? null);
        return redirect($intendedUrl)->with('status', 'Login berhasil.');
    }

    public function adminLogin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // /admin/login hanya mengenali akun ADMIN (tabel admins).
        $admin = Admin::where('email', $data['email'])->first();

        if (! $admin || ! Hash::check($data['password'], $admin->password)) {
            return back()->withErrors(['email' => 'Email atau password admin salah.'])->onlyInput('email');
        }

        session()->regenerate();
        session([
            'account_id' => $admin->getKey(),
            'account_name' => $admin->name,
            'account_username' => $admin->name,
            'account_role' => 'admin',
            'account_avatar' => $admin->avatar_path ?? null,
        ]);

        session()->forget('checkout_intended');
        return redirect()->route('admin.dashboard')->with('status', 'Selamat datang, Admin ' . $admin->name);
    }

    public function registerForm(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\s\-]+$/'],
            'domicile' => ['required', 'string', 'max:120', 'in:Jakarta,Bogor,Depok,Tangerang,Bekasi'],
            'password' => ['required', 'confirmed', 'min:8'],
            'terms' => ['accepted'],
            'ktp' => ['nullable', 'file', 'mimes:jpeg,jpg,png', 'max:10240'],
        ]);

        $ktpPath = null;
        if ($request->hasFile('ktp')) {
            $ktpPath = $request->file('ktp')->store('ktp_uploads', 'public');
        }

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'domicile' => $data['domicile'],
            'ktp_path' => $ktpPath,
            'password' => $data['password'],
            'status' => 'active',
            'role' => 'customer',
        ]);

        session()->regenerate();
        session([
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_username' => $user->username,
            'account_role' => 'customer',
            'account_avatar' => $user->avatar_path ?? null,
        ]);

        $intendedUrl = $this->resolveIntendedUrl(null);
        return redirect($intendedUrl)->with('status', 'Akun berhasil dibuat.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $role = $request->session()->get('account_role');

        $request->session()->forget(['account_id', 'account_name', 'account_username', 'account_role', 'account_avatar', 'cart_items', 'checkout_intended']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($role === 'admin') {
            return redirect()->route('admin.login')->with('status', 'Anda telah berhasil keluar dari Admin Panel.');
        }

        return redirect()->route('login')->with('status', 'Anda telah berhasil keluar.');
    }

    /**
     * Tentukan URL tujuan setelah login. Hanya menerima URL lokal (relatif)
     * untuk mencegah open redirect. Prioritaskan redirect dari form,
     * lalu 'checkout_intended' yang tersimpan di session, terakhir fallback '/'.
     */
    private function resolveIntendedUrl(?string $redirect): string
    {
        if (! empty($redirect) && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            return $redirect;
        }

        $intended = session()->pull('checkout_intended', '/');
        if (is_string($intended) && $intended !== '' && str_starts_with($intended, '/')) {
            return $intended;
        }

        return '/';
    }
}
