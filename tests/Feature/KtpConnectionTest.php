<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KtpConnectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Membuat file gambar PNG 1x1 yang valid tanpa bergantung pada ekstensi GD.
     */
    private function makePng(string $name): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');

        $path = tempnam(sys_get_temp_dir(), 'ktp_');
        file_put_contents($path, $png);

        return new UploadedFile($path, $name, 'image/png', null, true);
    }

    private function createUser(string $name, string $email, string $ktpPath = null, string $dob = null): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'username' => explode('@', $email)[0],
            'phone' => '081234567890',
            'domicile' => 'Jakarta',
            'ktp_user_path' => $ktpPath,
            'date_of_birth' => $dob ?? '1995-01-01',
            'password' => 'password123',
            'status' => 'active',
            'role' => 'customer',
        ]);
    }

    private function storeKtpFile(string $filename): string
    {
        Storage::disk('public')->put($filename, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
        return $filename;
    }

    private function customerSession(User $user): array
    {
        return [
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_role' => 'customer',
        ];
    }

    private function adminSession(): array
    {
        return [
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ];
    }

    /**
     * TEST 1 — Register user >= 17 dengan upload KTP: KTP tersimpan & terhubung dengan User ID.
     */
    public function test_register_stores_ktp_and_links_to_user(): void
    {
        Storage::fake('public');

        $photo = $this->makePng('ktp_register.png');

        $response = $this->post('/register', [
            'name' => 'User Register KTP',
            'username' => 'userktp',
            'email' => 'userktp@example.com',
            'phone' => '081234567890',
            'domicile' => 'Jakarta',
            'date_of_birth' => '1998-01-01',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => '1',
            'ktp_user' => $photo,
        ]);

        $response->assertRedirect('/');

        $user = User::where('email', 'userktp@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->ktp_user_path);
        $this->assertStringStartsWith('ktp_uploads/', $user->ktp_user_path);
        $this->assertNull($user->ktp_orang_tua_path);
        $this->assertNull($user->kartu_pelajar_path);

        // KTP terhubung via users.id (user_id) dan file benar-benar tersimpan.
        $this->assertDatabaseHas('users', ['id' => $user->id, 'ktp_user_path' => $user->ktp_user_path]);
        Storage::disk('public')->assertExists($user->ktp_user_path);
    }

    /**
     * TEST 1b — Register tanpa mengupload KTP tetap berhasil (KTP nullable).
     */
    public function test_register_without_ktp_succeeds(): void
    {
        $response = $this->post('/register', [
            'name' => 'User Tanpa KTP',
            'username' => 'tanpaktp',
            'email' => 'tanpaktp@example.com',
            'phone' => '081234567890',
            'domicile' => 'Bogor',
            'date_of_birth' => '2000-06-15',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => '1',
        ]);

        $response->assertRedirect('/');

        $user = User::where('email', 'tanpaktp@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->ktp_user_path);
        $this->assertNull($user->ktp_url);
    }

    /**
     * TEST 1c — User < 17 wajib upload KTP orang tua + kartu pelajar.
     * Kedua dokumen tersimpan di disk privat, bukan field KTP user.
     */
    public function test_minor_register_requires_guardian_docs(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $ktpOrtu = $this->makePng('ktp_ortu.png');
        $kartu = $this->makePng('kartu_pelajar.png');
        $proof = $this->makePng('bukti_persetujuan.png');

        $response = $this->post('/register', [
            'name' => 'User Minor',
            'username' => 'userminor',
            'email' => 'userminor@example.com',
            'phone' => '081299887766',
            'domicile' => 'Depok',
            'date_of_birth' => '2010-03-12',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => '1',
            'parent_name' => 'Orang Tua Contoh',
            'parent_relation' => 'Ayah',
            'parent_phone' => '081299887700',
            'parent_consent_accepted' => '1',
            'parent_consent_proof' => $proof,
            'ktp_orang_tua' => $ktpOrtu,
            'kartu_pelajar' => $kartu,
        ]);

        $response->assertRedirect('/');

        $user = User::where('email', 'userminor@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->is_minor);
        $this->assertNull($user->ktp_user_path);
        $this->assertStringStartsWith('ktp_orang_tua/', $user->ktp_orang_tua_path);
        $this->assertStringStartsWith('kartu_pelajar/', $user->kartu_pelajar_path);

        Storage::disk('local')->assertExists($user->ktp_orang_tua_path);
        Storage::disk('local')->assertExists($user->kartu_pelajar_path);
    }

    /**
     * TEST 1d — User < 17 tanpa dokumen ditolak oleh validasi server.
     */
    public function test_minor_register_rejected_without_guardian_docs(): void
    {
        $response = $this->post('/register', [
            'name' => 'User Minor No Docs',
            'username' => 'minornodocs',
            'email' => 'minornodocs@example.com',
            'phone' => '081299887766',
            'domicile' => 'Depok',
            'date_of_birth' => '2010-03-12',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => '1',
            'parent_name' => 'Orang Tua Contoh',
            'parent_relation' => 'Ayah',
            'parent_phone' => '081299887700',
            'parent_consent_accepted' => '1',
        ]);

        $response->assertSessionHasErrors(['parent_consent_proof', 'ktp_orang_tua', 'kartu_pelajar']);
        $this->assertNull(User::where('email', 'minornodocs@example.com')->first());
    }

    /**
     * TEST 2 — Profil User menampilkan foto KTP milik User yang sedang login.
     */
    public function test_profile_shows_logged_in_users_ktp(): void
    {
        Storage::fake('public');
        $ktpPath = $this->storeKtpFile('ktp_uploads/profil-user.png');
        $user = $this->createUser('Profile KTP User', 'profilktp@example.com', $ktpPath);

        $response = $this->withSession($this->customerSession($user))->get('/profile');

        $response->assertStatus(200);
        $response->assertSee(asset('storage/' . $ktpPath), false);
        $response->assertSee('KTP');
    }

    /**
     * TEST 2b — Profil menampilkan "KTP belum tersedia." bila User tidak punya KTP.
     */
    public function test_profile_shows_ktp_unavailable_when_missing(): void
    {
        Storage::fake('public');
        $user = $this->createUser('Profile Tanpa KTP', 'profilnoktp@example.com', null);

        $response = $this->withSession($this->customerSession($user))->get('/profile');

        $response->assertStatus(200);
        $response->assertSee('Belum diunggah');
        $response->assertDontSee('storage/ktp_uploads');
    }

    /**
     * TEST 2c — Profil user < 17 menampilkan label dokumen KTP orang tua & kartu pelajar.
     */
    public function test_profile_shows_minor_documents(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('ktp_orang_tua/profil-minor.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
        Storage::disk('local')->put('kartu_pelajar/profil-minor.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));

        $user = User::create([
            'name' => 'Profil Minor',
            'email' => 'profilminor@example.com',
            'username' => 'profilminor',
            'phone' => '081234567890',
            'domicile' => 'Jakarta',
            'date_of_birth' => '2011-01-01',
            'ktp_orang_tua_path' => 'ktp_orang_tua/profil-minor.png',
            'kartu_pelajar_path' => 'kartu_pelajar/profil-minor.png',
            'password' => 'password123',
            'status' => 'active',
            'role' => 'customer',
        ]);

        $response = $this->withSession($this->customerSession($user))->get('/profile');

        $response->assertStatus(200);
        $response->assertSee('KTP ORANG TUA', false);
        $response->assertSee('KARTU PELAJAR', false);
        $response->assertSee(route('file.ktp-guardian', $user->id), false);
        $response->assertSee(route('file.student-card', $user->id), false);
    }

    /**
     * TEST 3 — Admin melihat foto KTP User pada halaman Data Pengguna.
     */
    public function test_admin_sees_ktp_on_users_page(): void
    {
        Storage::fake('public');
        $ktpPath = $this->storeKtpFile('ktp_uploads/admin-view.png');
        $user = $this->createUser('KTP Admin View', 'adminview@example.com', $ktpPath);

        $response = $this->withSession($this->adminSession())->get('/admin/users');

        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee(str_replace('/', '\\/', asset('storage/' . $ktpPath)), false);
    }

    /**
     * TEST 3b — Admin melihat dokumen verifikasi user < 17 dengan label yang benar.
     */
    public function test_admin_sees_minor_documents(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('ktp_orang_tua/admin-minor.png', 'x');
        Storage::disk('local')->put('kartu_pelajar/admin-minor.png', 'x');

        $user = User::create([
            'name' => 'Admin Minor View',
            'email' => 'adminminorview@example.com',
            'username' => 'adminminorview',
            'phone' => '081234567890',
            'domicile' => 'Jakarta',
            'date_of_birth' => '2010-01-01',
            'ktp_orang_tua_path' => 'ktp_orang_tua/admin-minor.png',
            'kartu_pelajar_path' => 'kartu_pelajar/admin-minor.png',
            'password' => 'password123',
            'status' => 'active',
            'role' => 'customer',
        ]);

        $response = $this->withSession($this->adminSession())->get('/admin/users');

        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee('KTP Orang Tua / Wali');
        $response->assertSee('Kartu Pelajar');
        $response->assertSee('Dokumen Verifikasi');
    }

    /**
     * TEST 4 — User A tidak dapat melihat KTP User B pada profilnya.
     */
    public function test_user_a_cannot_see_user_b_ktp(): void
    {
        Storage::fake('public');
        $ktpA = $this->storeKtpFile('ktp_uploads/user-a.png');
        $ktpB = $this->storeKtpFile('ktp_uploads/user-b.png');
        $userA = $this->createUser('User A', 'usera@example.com', $ktpA);
        $this->createUser('User B', 'userb@example.com', $ktpB);

        $response = $this->withSession($this->customerSession($userA))->get('/profile');

        $response->assertStatus(200);
        $response->assertSee(asset('storage/' . $ktpA), false);
        $response->assertDontSee(asset('storage/' . $ktpB), false);
    }

    /**
     * TEST 4b — Dokumen sensitif user < 17: user lain TIDAK bisa membuka
     * KTP orang tua / kartu pelajar milik user lain (authorization backend).
     */
    public function test_user_cannot_open_other_users_minor_documents(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('ktp_orang_tua/user-a.png', 'x');
        Storage::disk('local')->put('kartu_pelajar/user-a.png', 'x');
        Storage::disk('local')->put('ktp_orang_tua/user-b.png', 'y');

        $userA = User::create([
            'name' => 'User Minor A',
            'email' => 'minor-a@example.com',
            'username' => 'minora',
            'phone' => '081234567890',
            'domicile' => 'Jakarta',
            'date_of_birth' => '2010-01-01',
            'ktp_orang_tua_path' => 'ktp_orang_tua/user-a.png',
            'kartu_pelajar_path' => 'kartu_pelajar/user-a.png',
            'password' => 'password123',
            'status' => 'active',
            'role' => 'customer',
        ]);
        $userB = User::create([
            'name' => 'User Minor B',
            'email' => 'minor-b@example.com',
            'username' => 'minorb',
            'phone' => '081234567890',
            'domicile' => 'Jakarta',
            'date_of_birth' => '2010-01-01',
            'ktp_orang_tua_path' => 'ktp_orang_tua/user-b.png',
            'password' => 'password123',
            'status' => 'active',
            'role' => 'customer',
        ]);

        // Pemilik mendapat file miliknya.
        $this->withSession($this->customerSession($userA))
            ->get(route('file.ktp-guardian', $userA->id))
            ->assertOk();
        $this->withSession($this->customerSession($userA))
            ->get(route('file.student-card', $userA->id))
            ->assertOk();

        // User lain TIDAK mendapat izin.
        $this->withSession($this->customerSession($userA))
            ->get(route('file.ktp-guardian', $userB->id))
            ->assertForbidden();
        $this->withSession($this->customerSession($userA))
            ->get(route('file.student-card', $userB->id))
            ->assertForbidden();

        // Admin boleh membuka dokumen semua user.
        $this->withSession($this->adminSession())
            ->get(route('file.ktp-guardian', $userB->id))
            ->assertOk();
    }

    /**
     * TEST 4c — Customer biasa TIDAK dapat membuka halaman Data Pengguna admin
     * (authorization backend, bukan sekadar menyembunyikan tombol).
     */
    public function test_regular_customer_cannot_access_admin_users_page(): void
    {
        Storage::fake('public');
        $user = $this->createUser('Customer Biasa', 'customerb@example.com', null);

        $response = $this->withSession($this->customerSession($user))->get('/admin/users');

        $response->assertForbidden();
    }

    /**
     * TEST 5 — Dua User dengan KTP berbeda: Admin melihat KTP yang sesuai
     * masing-masing User (bukan satu foto untuk semua).
     */
    public function test_admin_sees_distinct_ktp_per_user(): void
    {
        Storage::fake('public');
        $ktpA = $this->storeKtpFile('ktp_uploads/user-alpha.png');
        $ktpB = $this->storeKtpFile('ktp_uploads/user-beta.png');

        $userA = $this->createUser('Alpha User', 'alpha@example.com', $ktpA);
        $userB = $this->createUser('Beta User', 'beta@example.com', $ktpB);

        $urlA = asset('storage/' . $ktpA);
        $urlB = asset('storage/' . $ktpB);
        $this->assertNotSame($urlA, $urlB);

        $response = $this->withSession($this->adminSession())->get('/admin/users');

        $response->assertStatus(200);
        $response->assertSee($userA->name);
        $response->assertSee($userB->name);
        $response->assertSee(str_replace('/', '\\/', $urlA), false);
        $response->assertSee(str_replace('/', '\\/', $urlB), false);
    }
}