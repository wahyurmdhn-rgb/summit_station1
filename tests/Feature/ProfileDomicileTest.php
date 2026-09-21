<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileDomicileTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_saves_domicile_and_profile_edit_cannot_change_it(): void
    {
        // TEST 1: Buat akun baru dengan domisili 'Bogor' via Register. Pastikan tersimpan.
        $unique = time() . random_int(100, 999);
        $this->post('/register', [
            'name' => 'Domisili Member',
            'username' => 'domisili_member_' . $unique,
            'email' => 'domisili.member.' . $unique . '@summit.id',
            'phone' => '081234567890',
            'domicile' => 'Bogor',
            'date_of_birth' => '2000-04-04',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => '1',
        ])->assertRedirect('/');

        $user = User::where('email', 'domisili.member.' . $unique . '@summit.id')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Bogor', $user->domicile, 'Domisili harus tersimpan saat register.');

        // TEST 2 & 5: Edit Profil menampilkan domisili 'Bogor' dan readonly/disabled.
        $profile = $this->withSession([
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_role' => 'customer',
        ])->get('/profile');

        $profile->assertStatus(200);
        $profile->assertSee($user->name);
        $profile->assertSee('value="Bogor"', false);
        $profile->assertSee('readonly', false);
        $profile->assertSee('disabled', false);

        // TEST 3: Ubah Nama/Username/Phone. Simpan. Domisili harus tetap 'Bogor'.
        $update = $this->withSession([
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_role' => 'customer',
        ])->post('/profile', [
            'name' => 'Nama Baru',
            'username' => 'domisili_member_' . $unique . '_baru',
            'phone' => '081111111111',
            'domicile' => 'Bogor',
        ]);

        $update->assertRedirect(route('profile'));

        $user->refresh();
        $this->assertEquals('Nama Baru', $user->name, 'Nama Lengkap harus diperbarui.');
        $this->assertEquals('domisili_member_' . $unique . '_baru', $user->username, 'Username harus diperbarui.');
        $this->assertEquals('081111111111', $user->phone, 'Nomor WhatsApp harus diperbarui.');
        $this->assertEquals('Bogor', $user->domicile, 'Domisili tidak boleh berubah saat edit profil.');

        // TEST 4: Manipulasi request dengan domicile='Jakarta'. Backend harus mengabaikannya.
        $this->withSession([
            'account_id' => $user->id,
            'account_name' => 'Nama Baru',
            'account_role' => 'customer',
        ])->post('/profile', [
            'name' => 'Nama Baru',
            'username' => 'domisili_member_' . $unique . '_baru',
            'phone' => '081111111111',
            'domicile' => 'Jakarta',
        ])->assertRedirect(route('profile'));

        $this->assertEquals('Bogor', $user->fresh()->domicile, 'Domisili di database tidak boleh berubah meski request membawa nilai lain.');

        // TEST 5: Refresh halaman, domisili tetap 'Bogor'.
        $reload = $this->withSession([
            'account_id' => $user->id,
            'account_name' => 'Nama Baru',
            'account_role' => 'customer',
        ])->get('/profile');

        $reload->assertStatus(200);
        $reload->assertSee('value="Bogor"', false);
    }
}
