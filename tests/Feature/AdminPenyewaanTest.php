<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPenyewaanTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();

        $user = User::firstOrCreate(
            ['email' => 'aris.h@example.com'],
            ['name' => 'Aris Hidayat', 'username' => 'arish', 'password' => 'password']
        );

        $category = Category::firstOrCreate(
            ['slug' => 'backpacks'],
            ['name' => 'Backpacks']
        );

        $product = Product::firstOrCreate(
            ['sku' => 'SS-BPK-102'],
            [
                'category_id' => $category->id,
                'name' => 'Osprey Aether 65L',
                'price_per_day' => 85000,
                'stock_total' => 12,
                'stock_available' => 9,
            ]
        );

        Order::firstOrCreate(
            ['code' => 'RS-1020-TEST'],
            [
                'user_id' => $user->id,
                'rent_start' => now()->startOfDay(),
                'rent_end' => now()->addDays(4)->endOfDay(),
                'subtotal' => 340000,
                'service_fee' => 20000,
                'discount' => 0,
                'total' => 360000,
                'status' => 'active',
            ]
        );
    }

    public function test_admin_can_access_penyewaan_page(): void
    {
        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin/penyewaan');

        $response->assertStatus(200);
        $response->assertSee('Penyewaan');
        $response->assertSee('Manajemen Inventaris Sewa');
        $response->assertSee('SEWA AKTIF');
        $response->assertSee('PERMINTAAN MENUNGGU');
        $response->assertSee('PERKIRAAN PENGEMBALIAN');
        $response->assertSee('PROYEKSI PENDAPATAN');
        $response->assertSee('Ekspor CSV');
        $response->assertSee('Aris Hidayat');
    }

    public function test_customer_cannot_access_penyewaan_page(): void
    {
        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Customer Summit',
            'account_role' => 'customer',
        ])->get('/admin/penyewaan');

        $response->assertStatus(403);
    }

    public function test_guest_is_redirected_when_accessing_penyewaan_page(): void
    {
        $response = $this->get('/admin/penyewaan');

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_confirm_pending_penyewaan(): void
    {
        $user = User::first();
        $order = Order::create([
            'code' => 'RS-CONFIRM-TEST',
            'user_id' => $user->id,
            'rent_start' => now()->startOfDay(),
            'rent_end' => now()->addDays(2)->endOfDay(),
            'subtotal' => 100000,
            'total' => 100000,
            'status' => 'pending',
        ]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->post('/admin/penyewaan/' . $order->id . '/confirm');

        $response->assertRedirect('/admin/penyewaan');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'active']);
    }

    public function test_admin_can_reject_pending_penyewaan(): void
    {
        $user = User::first();
        $order = Order::create([
            'code' => 'RS-REJECT-TEST',
            'user_id' => $user->id,
            'rent_start' => now()->startOfDay(),
            'rent_end' => now()->addDays(2)->endOfDay(),
            'subtotal' => 100000,
            'total' => 100000,
            'status' => 'pending',
        ]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->post('/admin/penyewaan/' . $order->id . '/reject', [
            'reason' => 'Stok alat sedang maintenance',
        ]);

        $response->assertRedirect('/admin/penyewaan');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
    }

    public function test_admin_can_complete_active_penyewaan(): void
    {
        $user = User::first();
        $order = Order::create([
            'code' => 'RS-COMPLETE-TEST',
            'user_id' => $user->id,
            'rent_start' => now()->startOfDay(),
            'rent_end' => now()->addDays(2)->endOfDay(),
            'subtotal' => 100000,
            'total' => 100000,
            'status' => 'active',
        ]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->post('/admin/penyewaan/' . $order->id . '/complete');

        $response->assertRedirect('/admin/penyewaan');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
    }

    public function test_admin_can_export_csv(): void
    {
        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin/penyewaan/export');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
