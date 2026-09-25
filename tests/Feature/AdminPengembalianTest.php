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
        $response->assertSee('PROSES PENGEMBALIAN');
        $response->assertSee('Total Pengembalian');
        $response->assertSee('Menunggu Inspeksi');
        $response->assertSee('Terlambat');
        $response->assertSee('Alex Thompson');
        $response->assertSee('ORD-9921-X');
        $response->assertSee('Kelola seluruh proses pengembalian alat');
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
        $returnRecord = ReturnRecord::create([
            'order_id' => $this->activeOrder->id,
            'status' => 'approved',
            'condition' => 'excellent',
            'returned_at' => now(),
        ]);

        $initialStock = $this->product->fresh()->stock_available;
        $adminSession = [
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ];

        $response = $this->withSession($adminSession)->post(
            "/admin/pengembalian/{$this->activeOrder->id}/complete",
            ['return_record_id' => $returnRecord->id]
        );

        $response->assertRedirect(route('admin.pengembalian'))
            ->assertSessionHas('success', "Pengembalian order #{$this->activeOrder->code} berhasil diselesaikan.");
        $this->assertDatabaseHas('orders', [
            'id' => $this->activeOrder->id,
            'status' => 'completed',
        ]);
        $this->assertEquals($initialStock + 1, $this->product->fresh()->stock_available);

        $repeatResponse = $this->withSession($adminSession)->post(
            "/admin/pengembalian/{$this->activeOrder->id}/complete",
            ['return_record_id' => $returnRecord->id]
        );

        $repeatResponse->assertRedirect(route('admin.pengembalian'))
            ->assertSessionHas('info', "Pengembalian order #{$this->activeOrder->code} sudah selesai.");
        $this->assertEquals($initialStock + 1, $this->product->fresh()->stock_available);

        $this->withSession($adminSession)
            ->get('/admin/pengembalian')
            ->assertOk()
            ->assertDontSee('class="rc-order-code" title="ID Transaksi">#'.$this->activeOrder->code.'</div>', false);
        $this->withSession($adminSession)
            ->get('/admin/pengembalian?filter=selesai')
            ->assertOk()
            ->assertSee('class="rc-order-code" title="ID Transaksi">#'.$this->activeOrder->code.'</div>', false)
            ->assertSee('class="rc-btn-done"', false);
    }

    public function test_completion_rejects_return_record_from_another_order(): void
    {
        $otherOrder = Order::create([
            'code' => 'ORD-OTHER-01',
            'user_id' => $this->user->id,
            'rent_start' => today()->subDays(2),
            'rent_end' => today(),
            'subtotal' => 100000,
            'total' => 100000,
            'status' => 'active',
        ]);
        $otherReturn = ReturnRecord::create([
            'order_id' => $otherOrder->id,
            'status' => 'approved',
            'condition' => 'good',
            'returned_at' => now(),
        ]);
        $initialStock = $this->product->fresh()->stock_available;

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->post("/admin/pengembalian/{$this->activeOrder->id}/complete", [
            'return_record_id' => $otherReturn->id,
        ]);

        $response->assertRedirect(route('admin.pengembalian'))
            ->assertSessionHas('error');
        $this->assertEquals('active', $this->activeOrder->fresh()->status);
        $this->assertEquals($initialStock, $this->product->fresh()->stock_available);
    }

    public function test_completion_keeps_unpaid_damage_fine_intact(): void
    {
        $returnRecord = ReturnRecord::create([
            'order_id' => $this->activeOrder->id,
            'status' => 'approved',
            'condition' => 'minor_damage',
            'damage_description' => 'Goresan pada bagian samping',
            'damage_cost' => 50000,
            'returned_at' => now(),
        ]);
        $initialStock = $this->product->fresh()->stock_available;

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->post("/admin/pengembalian/{$this->activeOrder->id}/complete", [
            'return_record_id' => $returnRecord->id,
        ]);

        $response->assertRedirect(route('admin.pengembalian'))
            ->assertSessionHas('error');
        $this->assertEquals('active', $this->activeOrder->fresh()->status);
        $this->assertEquals($initialStock, $this->product->fresh()->stock_available);
        $this->assertDatabaseHas('return_records', [
            'id' => $returnRecord->id,
            'damage_cost' => 50000,
            'damage_paid_at' => null,
        ]);
    }

    public function test_major_damage_return_completes_without_restoring_stock(): void
    {
        $returnRecord = ReturnRecord::create([
            'order_id' => $this->activeOrder->id,
            'status' => 'approved',
            'condition' => 'major_damage',
            'damage_description' => 'Rangka patah dan perlu perbaikan',
            'returned_at' => now(),
        ]);
        $initialStock = $this->product->fresh()->stock_available;

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->post("/admin/pengembalian/{$this->activeOrder->id}/complete", [
            'return_record_id' => $returnRecord->id,
        ]);

        $response->assertRedirect(route('admin.pengembalian'))
            ->assertSessionHas('success');
        $this->assertEquals('completed', $this->activeOrder->fresh()->status);
        $this->assertEquals($initialStock, $this->product->fresh()->stock_available);
        $this->assertDatabaseHas('return_records', [
            'id' => $returnRecord->id,
            'condition' => 'major_damage',
        ]);
    }

    public function test_blocked_complete_button_shows_reason_instead_of_silent_disabled_state(): void
    {
        $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin/pengembalian')
            ->assertOk()
            ->assertSee('data-return-blocked-message="Barang belum dikembalikan oleh customer"', false)
            ->assertDontSee('class="rc-btn-complete" disabled', false)
            ->assertSee('id="rtToastRoot"', false)
            ->assertSee("var toastRoot = document.getElementById('rtToastRoot');", false)
            ->assertSee('toastRoot.appendChild(el);', false)
            ->assertSee("el.querySelector('.rt-toast-close').addEventListener('click'", false)
            ->assertSee('setTimeout(function () { el.remove(); }, 200);', false)
            ->assertDontSee('document.body.appendChild(el);', false);
    }

    public function test_toast_container_is_fixed_top_right_vertical_stack(): void
    {
        $css = file_get_contents(public_path('css/summit-return.css'));

        $this->assertIsString($css);
        $this->assertMatchesRegularExpression(
            '/\.rt-toast-root\s*\{[^}]*position:\s*fixed;[^}]*top:\s*24px;[^}]*right:\s*24px;[^}]*z-index:\s*100000;[^}]*display:\s*flex;[^}]*flex-direction:\s*column;[^}]*gap:\s*10px;/s',
            $css
        );
        $this->assertDoesNotMatchRegularExpression('/\.rt-toast-root\s*\{[^}]*bottom:/s', $css);
    }

    public function test_complete_form_sends_explicit_return_record_id(): void
    {
        $returnRecord = ReturnRecord::create([
            'order_id' => $this->activeOrder->id,
            'status' => 'approved',
            'condition' => 'good',
            'returned_at' => now(),
        ]);

        $completeUrl = route('admin.pengembalian.complete', $this->activeOrder->id);
        $encodedCompleteUrl = str_replace('/', '\/', $completeUrl);

        $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin/pengembalian')
            ->assertOk()
            ->assertSee('"complete_url":"'.$encodedCompleteUrl.'"', false)
            ->assertSee('"return_record_id":'.$returnRecord->id, false)
            ->assertSee('name="return_record_id"', false);
    }
}
