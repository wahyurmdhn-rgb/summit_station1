<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_users_page(): void
    {
        $response = $this->get('/admin/users');
        $response->assertRedirect('/admin/login');
    }

    public function test_customer_cannot_access_users_page(): void
    {
        $response = $this->withSession([
            'account_id' => 99,
            'account_name' => 'John Customer',
            'account_role' => 'customer',
        ])->get('/admin/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_users_page(): void
    {
        $user = User::create([
            'name' => 'Aris Setiawan',
            'username' => 'aris_mountain',
            'email' => 'aris.s@trekking.com',
            'domicile' => 'Malang, Jawa Timur',
            'status' => 'active',
            'password' => 'password',
        ]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin/users');

        $response->assertStatus(200);
        $response->assertSee('OPERASI');
        $response->assertSee('Manajemen Pengguna');
        $response->assertSee('TOTAL MEMBER');
        $response->assertSee('AKTIF SEKARANG');
        $response->assertSee('PENDAFTARAN BARU');
        $response->assertSee('VERIFIKASI MENUNGGU');
        $response->assertSee('Aris Setiawan');
        $response->assertSee('aris_mountain');
        $response->assertSee('Malang, Jawa Timur');
        $response->assertSee('AKTIF');
        $response->assertSee('Export CSV');
        $response->assertSee('Add New User');
    }

    public function test_admin_can_search_users(): void
    {
        User::create([
            'name' => 'Aris Setiawan',
            'username' => 'aris_mountain',
            'email' => 'aris.s@trekking.com',
            'domicile' => 'Malang',
            'status' => 'active',
            'password' => 'password',
        ]);

        User::create([
            'name' => 'Bambang Kurnia',
            'username' => 'bambang_k',
            'email' => 'bkurnia@web.com',
            'domicile' => 'Jakarta',
            'status' => 'active',
            'password' => 'password',
        ]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin/users?search=Aris');

        $response->assertStatus(200);
        $response->assertSee('Aris Setiawan');
        $response->assertDontSee('Bambang Kurnia');
    }

    public function test_admin_can_filter_users_by_status(): void
    {
        User::create([
            'name' => 'Aris Setiawan',
            'email' => 'aris.s@trekking.com',
            'status' => 'active',
            'password' => 'password',
        ]);

        User::create([
            'name' => 'Rina Melati',
            'email' => 'rina.mel@hike.co',
            'status' => 'suspended',
            'password' => 'password',
        ]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin/users?status=suspended');

        $response->assertStatus(200);
        $response->assertSee('Rina Melati');
        $response->assertDontSee('Aris Setiawan');
    }

    public function test_admin_can_store_new_user(): void
    {
        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->post('/admin/users', [
            'name' => 'Explorer Baru',
            'email' => 'explorer.baru@summit.id',
            'username' => 'explorer_baru',
            'phone' => '08123456789',
            'domicile' => 'Surabaya, Jawa Timur',
            'status' => 'active',
            'role' => 'user',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/admin/users');
        $this->assertDatabaseHas('users', [
            'email' => 'explorer.baru@summit.id',
            'username' => 'explorer_baru',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_update_user(): void
    {
        $user = User::create([
            'name' => 'Nama Lama',
            'email' => 'lama@summit.id',
            'username' => 'user_lama',
            'domicile' => 'Bandung',
            'status' => 'active',
            'password' => 'password',
        ]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->put('/admin/users/' . $user->id, [
            'name' => 'Nama Baru Update',
            'email' => 'lama@summit.id',
            'username' => 'user_baru',
            'domicile' => 'Bandung, Jawa Barat',
            'status' => 'inactive',
        ]);

        $response->assertRedirect('/admin/users');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nama Baru Update',
            'status' => 'inactive',
        ]);
    }

    public function test_admin_can_change_user_status(): void
    {
        $user = User::create([
            'name' => 'Test Status User',
            'email' => 'status@summit.id',
            'status' => 'active',
            'password' => 'password',
        ]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->patch('/admin/users/' . $user->id . '/status', [
            'status' => 'suspended',
        ]);

        $response->assertRedirect('/admin/users');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => 'suspended',
        ]);
    }

    public function test_admin_can_soft_delete_user(): void
    {
        $user = User::create([
            'name' => 'User To Delete',
            'email' => 'delete@summit.id',
            'status' => 'active',
            'password' => 'password',
        ]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->delete('/admin/users/' . $user->id);

        $response->assertRedirect('/admin/users');
        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);
    }

    public function test_admin_can_export_csv_users(): void
    {
        User::create([
            'name' => 'Export Explorer',
            'email' => 'export@summit.id',
            'username' => 'export_exp',
            'domicile' => 'Bali',
            'status' => 'active',
            'password' => 'password',
        ]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin/users/export');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }
}
