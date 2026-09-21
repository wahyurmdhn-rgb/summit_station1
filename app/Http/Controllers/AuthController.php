<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Batas umur minimal penyewa. Di bawah nilai ini wajib persetujuan orang tua.
     * Gunakan satu sumber kebenaran yang sama untuk seluruh sistem.
     */
    public const MIN_RENTAL_AGE = 17;

    /**
     * Extension & ukuran maksimal bukti persetujuan orang tua yang diterima.
     */
    private const CONSENT_EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf'];
    private const CONSENT_MAX_KB = 5120;
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
            'date_of_birth' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'password' => ['required', 'confirmed', 'min:8'],
            'terms' => ['accepted'],
            'ktp_user' => ['nullable', 'file', 'mimes:jpeg,jpg,png', 'max:10240'],
        ], [
            'date_of_birth.required' => 'Tanggal lahir wajib diisi.',
            'date_of_birth.date' => 'Format tanggal lahir tidak valid.',
            'date_of_birth.before' => 'Tanggal lahir tidak boleh di masa depan.',
            'date_of_birth.after' => 'Tanggal lahir tidak valid (terlalu lama).',
        ]);

        // Umur SELALU dihitung ulang di server dari tanggal lahir.
        // Validasi frontend/JavaScript tidak pernah dipercaya.
        $age = Carbon::parse($data['date_of_birth'])->age;
        $needsParentConsent = $age < self::MIN_RENTAL_AGE;

        $parentData = [];
        if ($needsParentConsent) {
            $parentData = $request->validate([
                'parent_name' => ['required', 'string', 'max:255'],
                'parent_relation' => ['required', 'string', 'max:120'],
                'parent_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\s\-]+$/'],
                'parent_consent_accepted' => ['accepted'],
                'parent_consent_proof' => ['required', 'file', 'mimes:' . implode(',', self::CONSENT_EXTENSIONS), 'max:' . self::CONSENT_MAX_KB],
                // User di bawah 17 tahun wajib menyertakan KTP orang tua/wali
                // dan kartu pelajar sebagai dokumen identitas & jaminan.
                'ktp_orang_tua' => ['required', 'file', 'mimes:jpeg,jpg,png', 'max:10240'],
                'kartu_pelajar' => ['required', 'file', 'mimes:jpeg,jpg,png', 'max:10240'],
            ], [
                'parent_name.required' => 'Nama orang tua/wali wajib diisi.',
                'parent_relation.required' => 'Hubungan dengan user wajib diisi.',
                'parent_phone.required' => 'Nomor kontak orang tua/wali wajib diisi.',
                'parent_consent_accepted.accepted' => 'Anda harus menyetujui persetujuan orang tua untuk melanjutkan.',
                'parent_consent_proof.required' => 'Bukti persetujuan orang tua wajib diunggah.',
                'parent_consent_proof.mimes' => 'Bukti persetujuan harus berupa JPG, JPEG, PNG, atau PDF.',
                'parent_consent_proof.max' => 'Ukuran bukti persetujuan maksimal 5MB.',
                'ktp_orang_tua.required' => 'KTP orang tua/wali wajib diunggah.',
                'ktp_orang_tua.mimes' => 'KTP orang tua harus berupa JPG, JPEG, atau PNG.',
                'ktp_orang_tua.max' => 'Ukuran KTP orang tua maksimal 10MB.',
                'kartu_pelajar.required' => 'Kartu pelajar wajib diunggah.',
                'kartu_pelajar.mimes' => 'Kartu pelajar harus berupa JPG, JPEG, atau PNG.',
                'kartu_pelajar.max' => 'Ukuran kartu pelajar maksimal 10MB.',
            ]);
        }

        // KTP user sendiri hanya digunakan untuk user >= 17 tahun.
        // Untuk user di bawah 17 tahun, KTP yang diunggah adalah milik
        // orang tua/wali (ktp_orang_tua) — field ini tidak boleh terisi.
        $ktpUserPath = null;
        if (! $needsParentConsent && $request->hasFile('ktp_user')) {
            $ktpUserPath = $request->file('ktp_user')->store('ktp_uploads', 'public');
        }

        // Dokumen sensitive user di bawah 17 tahun disimpan pada disk PRIVAT
        // (bukan public) dengan nama file acak agar tidak bisa ditebak atau
        // diakses publik. Disajikan lewat route berizin (admin / pemilik).
        $ktpOrtuPath = null;
        $kartuPelajarPath = null;
        if ($needsParentConsent) {
            if ($request->hasFile('ktp_orang_tua')) {
                $file = $request->file('ktp_orang_tua');
                $filename = 'ktp_ortu_' . now()->format('Ymd_His') . '_' . uniqid() . '.' . strtolower($file->getClientOriginalExtension());
                $ktpOrtuPath = $file->storeAs('ktp_orang_tua', $filename);
            }
            if ($request->hasFile('kartu_pelajar')) {
                $file = $request->file('kartu_pelajar');
                $filename = 'kartu_pelajar_' . now()->format('Ymd_His') . '_' . uniqid() . '.' . strtolower($file->getClientOriginalExtension());
                $kartuPelajarPath = $file->storeAs('kartu_pelajar', $filename);
            }
        }

        // Bukti persetujuan orang tua disimpan pada disk PRIVAT (bukan public)
        // dengan nama file acak agar tidak bisa ditebak maupun diakses publik.
        $consentPath = null;
        if ($needsParentConsent && $request->hasFile('parent_consent_proof')) {
            $file = $request->file('parent_consent_proof');
            $filename = 'consent_' . now()->format('Ymd_His') . '_' . uniqid() . '.' . strtolower($file->getClientOriginalExtension());
            $consentPath = $file->storeAs('parent_consents', $filename);
        }

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'domicile' => $data['domicile'],
            'date_of_birth' => $data['date_of_birth'],
            'ktp_user_path' => $ktpUserPath,
            'ktp_orang_tua_path' => $ktpOrtuPath,
            'kartu_pelajar_path' => $kartuPelajarPath,
            'password' => $data['password'],
            'status' => 'active',
            'role' => 'customer',
            'parent_consent_status' => $needsParentConsent ? 'submitted' : 'not_required',
            'parent_name' => $parentData['parent_name'] ?? null,
            'parent_relation' => $parentData['parent_relation'] ?? null,
            'parent_phone' => $parentData['parent_phone'] ?? null,
            'parent_consent_path' => $consentPath,
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
