<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Admin::firstOrCreate(
            ['email' => 'admin@summit.test'],
            [
                'name' => 'Admin Summit',
                'password' => Hash::make('password123'),
            ]
        );

        User::firstOrCreate(
            ['email' => 'customer@summit.test'],
            [
                'name' => 'Customer Summit',
                'username' => 'customer',
                'domicile' => 'Jakarta',
                'password' => Hash::make('password123'),
            ]
        );
    }

    public function test_admin_login_redirects_to_admin_dashboard(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@summit.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin');
        $this->assertEquals('admin', session('account_role'));
    }

    public function test_customer_login_redirects_to_homepage(): void
    {
        $response = $this->post('/login', [
            'email' => 'customer@summit.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/');
        $this->assertEquals('customer', session('account_role'));
    }

    public function test_guest_is_redirected_to_admin_login_when_accessing_admin_dashboard(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect(route('admin.login'));
    }

    public function test_customer_is_forbidden_from_accessing_admin_dashboard(): void
    {
        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Customer Summit',
            'account_role' => 'customer',
        ])->get('/admin');

        $response->assertStatus(403);
    }

    public function test_authenticated_admin_can_access_admin_dashboard(): void
    {
        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Admin Panel');
        $response->assertSee('Dashboard');
    }

    public function test_admin_visiting_login_page_is_redirected_to_admin_dashboard(): void
    {
        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/login');

        $response->assertRedirect('/admin');
    }

    public function test_admin_logout_clears_session_and_redirects_to_login(): void
    {
        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->post('/logout');

        $response->assertRedirect(route('admin.login'));
        $this->assertNull(session('account_role'));
        $this->assertNull(session('account_id'));
    }

    public function test_customer_logout_clears_session_and_redirects_to_login(): void
    {
        $response = $this->withSession([
            'account_id' => 2,
            'account_name' => 'Customer Summit',
            'account_role' => 'customer',
        ])->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertNull(session('account_role'));
        $this->assertNull(session('account_id'));
    }

    public function test_dashboard_is_protected_after_logout(): void
    {
        $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->post('/logout');

        // Trying to access admin dashboard after logout
        $response = $this->get('/admin');
        $response->assertRedirect(route('admin.login'));
    }

    public function test_register_creates_customer_and_redirects_to_customer_home(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Customer',
            'username' => 'newcustomer',
            'email' => 'newcustomer@example.com',
            'phone' => '081234567890',
            'domicile' => 'Jakarta',
            'date_of_birth' => '2000-01-01',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => '1',
        ]);

        $response->assertRedirect('/');
        $this->assertEquals('customer', session('account_role'));

        $user = User::where('email', 'newcustomer@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('customer', $user->role);
    }

    public function test_register_ignores_role_admin_submission(): void
    {
        $response = $this->post('/register', [
            'name' => 'Tricky User',
            'username' => 'trickyuser',
            'email' => 'tricky@example.com',
            'phone' => '081298765432',
            'domicile' => 'Bogor',
            'date_of_birth' => '2001-02-02',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => '1',
            'role' => 'admin',
        ]);

        // Backend must NEVER create an admin from public register.
        $this->assertNull(Admin::where('email', 'tricky@example.com')->first());

        $user = User::where('email', 'tricky@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('customer', $user->role);
        $this->assertEquals('customer', session('account_role'));
    }

    public function test_register_page_contains_no_admin_customer_role_selector(): void
    {
        $html = $this->get('/register')->getContent();

        // No role selector UI of any kind on the register page.
        $html = strtolower($html);
        $this->assertStringNotContainsString('name="role"', $html);
        $this->assertStringNotContainsString('name="role_id"', $html);
        $this->assertStringNotContainsString('role-tabs', $html);
        $this->assertStringNotContainsString('customer | admin', $html);
        $this->assertStringNotContainsString('pilih role', $html);
        $this->assertStringNotContainsString('jenis akun', $html);
        $this->assertStringNotContainsString('account type', $html);
        $this->assertStringNotContainsString('register as admin', $html);
        $this->assertStringNotContainsString('daftar sebagai admin', $html);
        // No role option elements.
        $this->assertStringNotContainsString('type="radio"', $html);
        $this->assertStringNotContainsString('<option value="admin"', $html);
        $this->assertStringNotContainsString('<option value="customer"', $html);

        // The only <select> is the customer domicile field.
        $this->assertStringContainsString('name="domicile"', $html);
        $this->assertStringNotContainsString('name="role">', $html);

        $response = $this->get('/register');
        $response->assertOk();
    }

    public function test_customer_cannot_escalate_role_via_profile_update(): void
    {
        // Create a verified customer
        $this->post('/register', [
            'name' => 'Profile User',
            'username' => 'profileuser',
            'email' => 'profileuser@example.com',
            'phone' => '0812345678',
            'domicile' => 'Jakarta',
            'date_of_birth' => '1998-03-03',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => '1',
        ]);
        $user = User::where('email', 'profileuser@example.com')->first();

        // Attempt to escalate role via profile update
        $response = $this->withSession([
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_username' => $user->username,
            'account_role' => 'customer',
        ])->post('/profile', [
            'name' => 'Profile User',
            'role' => 'admin',
            'role_id' => 'admin',
        ]);

        $response->assertRedirect();

        $fresh = $user->fresh();
        $this->assertEquals('customer', $fresh->role);
        $this->assertNull(Admin::find($user->id));
        $this->assertNotEquals('admin', session('account_role'));
    }

    public function test_customer_cannot_login_through_admin_login(): void
    {
        User::firstOrCreate(
            ['email' => 'plaincustomer@example.com'],
            [
                'name' => 'Plain Customer',
                'username' => 'plaincustomer',
                'domicile' => 'Jakarta',
                'role' => 'customer',
                'password' => Hash::make('password123'),
            ]
        );

        // Customer trying to log in through /admin/login is REJECTED:
        // no admin session is granted and no access to the admin dashboard.
        $response = $this->post('/admin/login', [
            'email' => 'plaincustomer@example.com',
            'password' => 'password123',
        ]);

        $this->assertNull(session('account_id'));
        $this->assertNull(session('account_role'));
        $response->assertSessionHasErrors('email');
        $response->assertRedirect();
    }

    public function test_admin_cannot_login_through_customer_login(): void
    {
        // Admin trying to log in through /login is REJECTED:
        // no admin session is granted via the customer login page.
        $response = $this->post('/login', [
            'email' => 'admin@summit.test',
            'password' => 'password123',
        ]);

        $this->assertNull(session('account_id'));
        $this->assertNull(session('account_role'));
        $response->assertSessionHasErrors('email');
        $response->assertRedirect();
    }

    public function test_customer_login_page_has_no_role_selector(): void
    {
        $response = $this->get('/login');
        $response->assertOk();

        $html = strtolower($response->getContent());
        $this->assertStringNotContainsString('name="role"', $html);
        $this->assertStringNotContainsString('name="role_id"', $html);
        $this->assertStringNotContainsString('role-tabs', $html);
        $this->assertStringNotContainsString('customer | admin', $html);
        $this->assertStringNotContainsString('login as customer', $html);
        $this->assertStringNotContainsString('login as admin', $html);
        $this->assertStringNotContainsString('pilih role', $html);
        $this->assertStringNotContainsString('select role', $html);
        $this->assertStringNotContainsString('jenis akun', $html);
        $this->assertStringNotContainsString('account type', $html);
        $this->assertStringNotContainsString('type="radio"', $html);
        $this->assertStringNotContainsString('<option value="admin"', $html);
        $this->assertStringNotContainsString('<option value="customer"', $html);

        // Customer page still points to register for new accounts.
        $response->assertSee('Daftar di sini');
    }

    public function test_admin_login_page_has_no_role_selector(): void
    {
        $response = $this->get('/admin/login');
        $response->assertOk();

        $html = strtolower($response->getContent());
        $this->assertStringNotContainsString('name="role"', $html);
        $this->assertStringNotContainsString('name="role_id"', $html);
        $this->assertStringNotContainsString('role-tabs', $html);
        $this->assertStringNotContainsString('customer | admin', $html);
        $this->assertStringNotContainsString('login as customer', $html);
        $this->assertStringNotContainsString('login as admin', $html);
        $this->assertStringNotContainsString('pilih role', $html);
        $this->assertStringNotContainsString('select role', $html);
        $this->assertStringNotContainsString('jenis akun', $html);
        $this->assertStringNotContainsString('account type', $html);
        $this->assertStringNotContainsString('type="radio"', $html);
        $this->assertStringNotContainsString('<option value="admin"', $html);
        $this->assertStringNotContainsString('<option value="customer"', $html);

        // Admin page uses the dedicated admin submit.
        $response->assertSee('MASUK SEBAGAI ADMIN');
    }
}
