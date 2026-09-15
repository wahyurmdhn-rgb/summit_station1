<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLogoutTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@summit.id',
            'password' => 'password123',
        ]);

        $this->user = User::create([
            'name' => 'Fajar Pratama',
            'username' => 'fajar_peaks',
            'email' => 'fajar@summit.id',
            'password' => 'password123',
            'status' => 'active',
        ]);
    }

    public function test_admin_login_dashboard_and_logout_without_419(): void
    {
        // 1. Login Admin
        $loginResponse = $this->post('/admin/login', [
            'email' => 'admin@summit.id',
            'password' => 'password123',
        ]);
        $loginResponse->assertRedirect(route('admin.dashboard'));
        $this->assertEquals($this->admin->getKey(), session('account_id'));
        $this->assertEquals('admin', session('account_role'));

        // 2. Open Admin Dashboard
        $dashboardResponse = $this->get('/admin');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('ADMIN');
        $dashboardResponse->assertSee('Keluar');

        // 3. Logout via POST /logout with CSRF
        $logoutResponse = $this->post('/logout');
        $logoutResponse->assertStatus(302);
        $logoutResponse->assertRedirect(route('admin.login'));

        // 4. Verify Session is completely destroyed
        $this->assertNull(session('account_id'));
        $this->assertNull(session('account_role'));

        // 5. Try accessing Admin Dashboard after logout -> Must redirect to login
        $protectedResponse = $this->get('/admin');
        $protectedResponse->assertRedirect(route('admin.login'));
    }

    public function test_admin_logout_from_all_admin_subpages(): void
    {
        $pages = [
            '/admin/alat',
            '/admin/penyewaan',
            '/admin/pembayaran',
            '/admin/pengembalian',
            '/admin/users',
            '/admin/website',
            '/admin/laporan',
        ];

        foreach ($pages as $page) {
            // Login as Admin
            $this->post('/admin/login', [
                'email' => 'admin@summit.id',
                'password' => 'password123',
            ]);

            // Open Page
            $res = $this->get($page);
            $res->assertStatus(200);
            $res->assertSee('Keluar');

            // Perform Logout
            $logoutRes = $this->post('/logout');
            $logoutRes->assertStatus(302);
            $logoutRes->assertRedirect(route('admin.login'));
            $this->assertNull(session('account_id'));

            // Verify access is blocked
            $this->get($page)->assertRedirect(route('admin.login'));
        }
    }

    public function test_customer_logout_flow(): void
    {
        // 1. Customer Login
        $this->post('/login', [
            'email' => 'fajar@summit.id',
            'password' => 'password123',
        ])->assertRedirect('/');

        // 2. Customer Logout
        $logoutRes = $this->post('/logout');
        $logoutRes->assertStatus(302);
        $logoutRes->assertRedirect(route('login'));
        $this->assertNull(session('account_id'));

        // 3. Verify Customer Profile is protected
        $this->get('/profile')->assertRedirect(route('login'));
    }
}
