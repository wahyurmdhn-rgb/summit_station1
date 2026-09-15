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

    private function createUser(string $name, string $email, string $ktpPath = null): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'username' => explode('@', $email)[0],
            'phone' => '081234567890',
            'domicile' => 'Jakarta',
            'ktp_path' => $ktpPath,
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
     * TEST 1 — Register dengan upload KTP: KTP tersimpan & terhubung dengan User ID.
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
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => '1',
            'ktp' => $photo,
        ]);

        $response->assertRedirect('/');

        $user = User::where('email', 'userktp@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->ktp_path);
        $this->assertStringStartsWith('ktp_uploads/', $user->ktp_path);

        // KTP terhubung via users.id (user_id) dan file benar-benar tersimpan.
        $this->assertDatabaseHas('users', ['id' => $user->id, 'ktp_path' => $user->ktp_path]);
        Storage::disk('public')->assertExists($user->ktp_path);
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
            'domicile' => 'Bandung',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => '1',
        ]);

        $response->assertRedirect('/');

        $user = User::where('email', 'tanpaktp@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->ktp_path);
        $this->assertNull($user->ktp_url);
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
        $response->assertSee('KTP belum tersedia.');
        $response->assertDontSee('asset(\'storage/');
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
     * TEST 4b — Customer biasa TIDAK dapat membuka halaman Data Pengguna admin
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