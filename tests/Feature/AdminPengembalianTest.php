<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ReturnRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPengembalianTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;
    protected Order $activeOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create([
            'name' => 'Backpacks',
            'slug' => 'backpacks',
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'sku' => 'TEST-BPK-01',
            'name' => 'Osprey Atmos 65L',
            'subtitle' => 'Expedition Pack',
            'description' => 'Test pack description',
            'price_per_day' => 85000,
            'stock_total' => 10,
            'stock_available' => 5,
            'rating' => 4.8,
            'reviews_count' => 10,
        ]);

        $this->user = User::create([
            'name' => 'Alex Thompson',
            'email' => 'alex.t@example.com',
            'username' => 'alexth',
            'password' => 'password',
            'domicile' => 'Jakarta',
        ]);

        $this->activeOrder = Order::create([
            'code' => 'ORD-9921-X',
            'user_id' => $this->user->id,
            'rent_start' => today()->subDays(3),
            'rent_end' => today(),
            'subtotal' => 255000,
            'service_fee' => 25000,
            'discount' => 0,
            'total' => 280000,
            'status' => 'active',
            'paid_at' => today()->subDays(3),
        ]);

        OrderItem::create([
            'order_id' => $this->activeOrder->id,
            'product_id' => $this->product->id,
            'name' => $this->product->name,
            'image' => null,
            'quantity' => 1,
            'days' => 3,
            'unit_price' => 85000,
            'subtotal' => 255000,
        ]);
    }

    public function test_guest_cannot_access_pengembalian_page(): void
    {
        $response = $this->get('/admin/pengembalian');
        $response->assertRedirect('/admin/login');
    }

    public function test_customer_cannot_access_pengembalian_page(): void
    {
        $response = $this->withSession([
            'account_id' => $this->user->id,
            'account_name' => $this->user->name,
            'account_role' => 'customer',
        ])->get('/admin/pengembalian');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_pengembalian_page(): void
    {
        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin/pengembalian');

        $response->assertStatus(200);
        $response->assertSee('Pengembalian');
        $response->assertSee('Pengembalian Alat');
        $response->assertSee('IKHTISAR LOGISTIK');
        $response->assertSee('PENGEMBALIAN HARI INI');
        $response->assertSee('MENUNGGU PEMERIKSAAN');
        $response->assertSee('TERLAMBAT');
        $response->assertSee('Alex Thompson');
        $response->assertSee('ORD-9921-X');
        $response->assertSee('Proses pengembalian alat');
    }

    public function test_admin_can_filter_returns(): void
    {
        // Add damaged return
        $damagedOrder = Order::create([
            'code' => 'ORD-DMG-01',
            'user_id' => $this->user->id,
            'rent_start' => today()->subDays(2),
            'rent_end' => today(),
            'subtotal' => 100000,
            'total' => 125000,
            'status' => 'active',
        ]);

        ReturnRecord::create([
            'order_id' => $damagedOrder->id,
            'status' => 'approved',
            'condition' => 'minor_damage',
            'damage_description' => 'Tali putus',
            'damage_cost' => 20000,
        ]);

        // Filter: damaged
        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin/pengembalian?filter=damaged');

        $response->assertStatus(200);
        $response->assertSee('ORD-DMG-01');
    }

    public function test_admin_can_record_inspection(): void
    {
        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->post("/admin/pengembalian/{$this->activeOrder->id}/record", [
            'condition' => 'excellent',
            'inspection_note' => 'Kondisi mulus dan bersih',
            'damage_description' => null,
            'damage_cost' => 0,
        ]);

        $response->assertRedirect('/admin/pengembalian');
        $this->assertDatabaseHas('return_records', [
            'order_id' => $this->activeOrder->id,
            'condition' => 'excellent',
            'status' => 'approved',
        ]);
    }

    public function test_admin_can_complete_return_and_restore_stock(): void
    {
        // First record inspection
        ReturnRecord::create([
            'order_id' => $this->activeOrder->id,
            'status' => 'approved',
            'condition' => 'excellent',
            'returned_at' => now(),
        ]);

        $initialStock = $this->product->fresh()->stock_available;

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->post("/admin/pengembalian/{$this->activeOrder->id}/complete");

        $response->assertRedirect('/admin/pengembalian');
        
        // Assert order status changed to completed
        $this->assertEquals('completed', $this->activeOrder->fresh()->status);

        // Assert stock returned
        $this->assertEquals($initialStock + 1, $this->product->fresh()->stock_available);
    }
}
