<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuspendedAccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Admin $admin;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@summit.id',
            'password' => 'password123',
        ]);

        $category = Category::create([
            'name' => 'Tents & Shelters',
            'slug' => 'tents-shelters',
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'sku' => 'TENT-001',
            'name' => 'Apex Ultralight V2',
            'subtitle' => '4-Season Expedition Tent',
            'price_per_day' => 250000,
            'stock_total' => 10,
            'stock_available' => 5,
            'is_active' => true,
        ]);

        $this->user = User::create([
            'name' => 'Randi Pratama',
            'username' => 'randi_peaks',
            'email' => 'randi@summit.id',
            'phone' => '08123456789',
            'domicile' => 'Bandung, Jawa Barat',
            'password' => 'secret123',
            'status' => 'active',
        ]);
    }

    public function test_active_user_can_login_and_book(): void
    {
        $loginRes = $this->post('/login', [
            'role' => 'customer',
            'email' => 'randi@summit.id',
            'password' => 'secret123',
        ]);
        $loginRes->assertRedirect('/');

        $cartRes = $this->post('/cart/add', [
            'product_id' => $this->product->id,
            'days' => 3,
            'quantity' => 1,
        ]);
        $cartRes->assertRedirect('/cart');
    }

    public function test_suspended_user_cannot_login(): void
    {
        $this->user->update(['status' => 'suspended']);

        $res = $this->post('/login', [
            'role' => 'customer',
            'email' => 'randi@summit.id',
            'password' => 'secret123',
        ]);
        $res->assertSessionHasErrors('email');
        $this->assertNull(session('account_id'));
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->user->update(['status' => 'inactive']);

        $res = $this->post('/login', [
            'role' => 'customer',
            'email' => 'randi@summit.id',
            'password' => 'secret123',
        ]);
        $res->assertSessionHasErrors('email');
        $this->assertNull(session('account_id'));
    }

    public function test_logged_in_user_who_gets_suspended_is_blocked_from_profile_and_history(): void
    {
        $this->user->update(['status' => 'suspended']);

        $profileRes = $this->withSession([
            'account_id' => $this->user->id,
            'account_name' => $this->user->name,
            'account_role' => 'customer',
        ])->get('/profile');
        $profileRes->assertRedirect('/login');
        $this->assertNull(session('account_id'));

        $historyRes = $this->withSession([
            'account_id' => $this->user->id,
            'account_name' => $this->user->name,
            'account_role' => 'customer',
        ])->get('/history');
        $historyRes->assertRedirect('/login');
    }

    public function test_suspended_user_cannot_add_to_cart_or_bundle(): void
    {
        $this->user->update(['status' => 'suspended']);

        $cartRes = $this->withSession([
            'account_id' => $this->user->id,
            'account_name' => $this->user->name,
            'account_role' => 'customer',
        ])->post('/cart/add', [
            'product_id' => $this->product->id,
            'days' => 2,
            'quantity' => 1,
        ]);
        $cartRes->assertRedirect('/login');
        $this->assertNull(session('cart_items'));
    }

    public function test_suspended_user_cannot_access_checkout_or_payment(): void
    {
        $this->user->update(['status' => 'suspended']);

        $paymentRes = $this->withSession([
            'account_id' => $this->user->id,
            'account_name' => $this->user->name,
            'account_role' => 'customer',
            'cart_items' => [
                $this->product->id => [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'days' => 3,
                    'quantity' => 1,
                    'price_per_day' => 250000,
                    'subtotal' => 750000,
                    'image' => 'tent.jpg',
                ]
            ]
        ])->get('/payment');
        $paymentRes->assertRedirect('/login');
    }

    public function test_suspended_user_cannot_submit_payment_or_create_order(): void
    {
        $this->user->update(['status' => 'suspended']);
        $initialOrderCount = Order::count();

        $submitRes = $this->withSession([
            'account_id' => $this->user->id,
            'account_name' => $this->user->name,
            'account_role' => 'customer',
            'cart_items' => [
                $this->product->id => [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'days' => 3,
                    'quantity' => 1,
                    'price_per_day' => 250000,
                    'subtotal' => 750000,
                    'image' => 'tent.jpg',
                ]
            ]
        ])->post('/payment/process', [
            'payment_method' => 'qris',
        ]);
        $submitRes->assertRedirect('/login');
        $this->assertEquals($initialOrderCount, Order::count());
    }

    public function test_suspended_user_api_request_returns_403(): void
    {
        $this->user->update(['status' => 'suspended']);

        $apiRes = $this->withSession([
            'account_id' => $this->user->id,
            'account_name' => $this->user->name,
            'account_role' => 'customer',
        ])->postJson('/cart/update/' . $this->product->id, [
            'days' => 5,
        ]);
        $apiRes->assertStatus(403);
        $apiRes->assertJson([
            'success' => false,
            'status' => 'suspended',
        ]);
    }

    public function test_admin_can_suspend_and_unsuspend_user(): void
    {
        // Login as Admin
        $this->post('/admin/login', [
            'email' => 'admin@summit.id',
            'password' => 'password123',
        ])->assertRedirect(route('admin.dashboard'));

        // Suspend User
        $this->patch('/admin/users/' . $this->user->id . '/status', [
            'status' => 'suspended',
        ])->assertRedirect('/admin/users');

        $this->user->refresh();
        $this->assertEquals('suspended', $this->user->status);

        // Unsuspend / Activate User
        $this->patch('/admin/users/' . $this->user->id . '/status', [
            'status' => 'active',
        ])->assertRedirect('/admin/users');

        $this->user->refresh();
        $this->assertEquals('active', $this->user->status);
    }

    public function test_unsuspended_user_can_login_again_and_use_features(): void
    {
        // 1. Set suspended
        $this->user->update(['status' => 'suspended']);

        // Cannot login
        $this->post('/login', [
            'role' => 'customer',
            'email' => 'randi@summit.id',
            'password' => 'secret123',
        ])->assertSessionHasErrors('email');

        // 2. Reactivate to active
        $this->user->update(['status' => 'active']);

        // Can login now
        $this->post('/login', [
            'role' => 'customer',
            'email' => 'randi@summit.id',
            'password' => 'secret123',
        ])->assertRedirect('/');

        // Can access profile
        $this->withSession([
            'account_id' => $this->user->id,
            'account_name' => $this->user->name,
            'account_role' => 'customer',
        ])->get('/profile')->assertStatus(200);

        // Can add to cart
        $this->withSession([
            'account_id' => $this->user->id,
            'account_name' => $this->user->name,
            'account_role' => 'customer',
        ])->post('/cart/add', [
            'product_id' => $this->product->id,
            'days' => 2,
            'quantity' => 1,
        ])->assertRedirect('/cart');
    }

    public function test_existing_orders_and_history_are_preserved_when_suspended(): void
    {
        $order = Order::create([
            'code' => 'RS-1122-XYZ',
            'user_id' => $this->user->id,
            'rent_start' => now(),
            'rent_end' => now()->addDays(3),
            'subtotal' => 750000,
            'service_fee' => 25000,
            'discount' => 0,
            'total' => 775000,
            'status' => 'completed',
        ]);

        $this->user->update(['status' => 'suspended']);

        // Order still exists in database
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $this->user->id,
            'code' => 'RS-1122-XYZ',
        ]);
    }
}
