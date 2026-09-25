<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LatePenalty;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ReturnRecord;
use App\Models\User;
use App\Notifications\RentalStatusNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LatePenaltyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create([
            'name' => 'Tents',
            'slug' => 'tents',
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'sku' => 'TNT-EXP-01',
            'name' => 'Tenda Dome 4P',
            'subtitle' => 'Expedition Tent',
            'description' => 'Tenda berkualitas tinggi',
            'price_per_day' => 100000,
            'stock_total' => 10,
            'stock_available' => 8,
        ]);

        $this->user = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'username' => 'budis',
            'password' => 'password123',
            'domicile' => 'Jakarta',
            'status' => 'active',
        ]);
    }

    /**
     * Skenario 1: Tepat waktu -> 0 hari terlambat, status tepat_waktu, total denda = 0
     */
    public function test_scenario_1_on_time_return(): void
    {
        $expectedDate = Carbon::parse('2026-09-03');
        $actualDate = Carbon::parse('2026-09-03');

        $order = Order::create([
            'code' => 'ORD-TEST-001',
            'user_id' => $this->user->id,
            'rent_start' => Carbon::parse('2026-09-01'),
            'rent_end' => $expectedDate,
            'subtotal' => 200000,
            'service_fee' => 0,
            'discount' => 0,
            'total' => 200000,
            'status' => 'completed',
        ]);

        $overdue = $order->calculateOverdue($actualDate);

        $this->assertFalse($overdue['is_overdue']);
        $this->assertEquals(0, $overdue['days_overdue']);
        $this->assertEquals('tepat_waktu', $overdue['status']);
        $this->assertEquals(0, $overdue['calculated_fee']);
    }

    /**
     * Skenario 2: Terlambat 1 hari -> 1 hari terlambat, total denda Rp 10.000
     */
    public function test_scenario_2_one_day_late(): void
    {
        $expectedDate = Carbon::parse('2026-09-03');
        $actualDate = Carbon::parse('2026-09-04');

        $order = Order::create([
            'code' => 'ORD-TEST-002',
            'user_id' => $this->user->id,
            'rent_start' => Carbon::parse('2026-09-01'),
            'rent_end' => $expectedDate,
            'subtotal' => 200000,
            'service_fee' => 0,
            'discount' => 0,
            'total' => 200000,
            'status' => 'completed',
        ]);

        $overdue = $order->calculateOverdue($actualDate);

        $this->assertTrue($overdue['is_overdue']);
        $this->assertEquals(1, $overdue['days_overdue']);
        $this->assertEquals('terlambat', $overdue['status']);
        $this->assertEquals(10000, $overdue['calculated_fee']);
    }

    /**
     * Skenario 3: Terlambat 5 hari -> 5 hari terlambat, total denda Rp 50.000
     */
    public function test_scenario_3_five_days_late(): void
    {
        $expectedDate = Carbon::parse('2026-09-03');
        $actualDate = Carbon::parse('2026-09-08');

        $order = Order::create([
            'code' => 'ORD-TEST-003',
            'user_id' => $this->user->id,
            'rent_start' => Carbon::parse('2026-09-01'),
            'rent_end' => $expectedDate,
            'subtotal' => 200000,
            'service_fee' => 0,
            'discount' => 0,
            'total' => 200000,
            'status' => 'completed',
        ]);

        $overdue = $order->calculateOverdue($actualDate);

        $this->assertTrue($overdue['is_overdue']);
        $this->assertEquals(5, $overdue['days_overdue']);
        $this->assertEquals('terlambat', $overdue['status']);
        $this->assertEquals(50000, $overdue['calculated_fee']);
    }

    /**
     * Skenario 4: Belum dikembalikan & tanggal jatuh tempo sudah lewat
     */
    public function test_scenario_4_overdue_unreturned(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-06 14:00:00'));

        $order = Order::create([
            'code' => 'ORD-TEST-004',
            'user_id' => $this->user->id,
            'rent_start' => Carbon::parse('2026-09-01'),
            'rent_end' => Carbon::parse('2026-09-03'),
            'subtotal' => 200000,
            'service_fee' => 0,
            'discount' => 0,
            'total' => 200000,
            'status' => 'active',
        ]);

        $overdue = $order->calculateOverdue(null);

        $this->assertTrue($overdue['is_overdue']);
        $this->assertEquals(3, $overdue['days_overdue']);
        $this->assertEquals('belum_dikembalikan_terlambat', $overdue['status']);
        $this->assertEquals(30000, $overdue['calculated_fee']);

        Carbon::setTestNow();
    }

    /**
     * Skenario 5: Tepat waktu tapi barang rusak (denda kerusakan terpisah dari sanksi keterlambatan)
     */
    public function test_scenario_5_on_time_with_damage_separation(): void
    {
        $expectedDate = Carbon::parse('2026-09-03');
        $actualDate = Carbon::parse('2026-09-03');

        $order = Order::create([
            'code' => 'ORD-TEST-005',
            'user_id' => $this->user->id,
            'rent_start' => Carbon::parse('2026-09-01'),
            'rent_end' => $expectedDate,
            'subtotal' => 200000,
            'service_fee' => 0,
            'discount' => 0,
            'total' => 200000,
            'status' => 'completed',
        ]);

        $returnRecord = ReturnRecord::create([
            'order_id' => $order->id,
            'condition' => 'minor_damage',
            'damage_description' => 'Tenda sobek pada bagian resleting',
            'damage_cost' => 50000,
            'status' => 'approved',
            'returned_at' => $actualDate,
        ]);

        $overdue = $order->calculateOverdue($actualDate);

        $this->assertFalse($overdue['is_overdue']);
        $this->assertEquals(0, $overdue['days_overdue']);
        $this->assertEquals(0, $overdue['calculated_fee']);
        $this->assertEquals(50000, (int) $returnRecord->damage_cost);
    }

    /**
     * Admin menetapkan sanksi denda keterlambatan dan user menerima notifikasi
     */
    public function test_admin_stores_late_penalty_and_notifies_user(): void
    {
        Notification::fake();

        $order = Order::create([
            'code' => 'ORD-TEST-006',
            'user_id' => $this->user->id,
            'rent_start' => Carbon::parse('2026-09-01'),
            'rent_end' => Carbon::parse('2026-09-03'),
            'subtotal' => 200000,
            'service_fee' => 0,
            'discount' => 0,
            'total' => 200000,
            'status' => 'active',
        ]);

        $returnRecord = ReturnRecord::create([
            'order_id' => $order->id,
            'condition' => 'good',
            'status' => 'approved',
            'returned_at' => Carbon::parse('2026-09-08'),
        ]);

        $response = $this->withSession([
            'account_id'   => 1,
            'account_role' => 'admin',
            'account_name' => 'Admin Summit',
        ])->post("/admin/pengembalian/{$order->id}/penalty", [
            'return_record_id' => $returnRecord->id,
            'status' => 'menunggu_pembayaran',
            'admin_notes' => 'Terlambat 5 hari tanpa pemberitahuan.',
            'complete_order' => 1,
        ]);

        $response->assertRedirect('/admin/pengembalian');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('late_penalties', [
            'order_id' => $order->id,
            'user_id' => $this->user->id,
            'days_overdue' => 5,
            'fee_per_day' => 10000,
            'total_fee' => 50000,
            'status' => 'menunggu_pembayaran',
            'admin_notes' => 'Terlambat 5 hari tanpa pemberitahuan.',
        ]);

        $order->refresh();
        $this->assertEquals('active', $order->status);

        Notification::assertSentTo(
            $this->user,
            RentalStatusNotification::class,
            function ($notification) {
                return $notification->type === RentalStatusNotification::TYPE_LATE_PENALTY_ASSIGNED;
            }
        );
    }

    public function test_penalty_completion_does_not_bypass_unpaid_damage_fine(): void
    {
        $order = Order::create([
            'code' => 'ORD-TEST-DENDA',
            'user_id' => $this->user->id,
            'rent_start' => Carbon::parse('2026-09-01'),
            'rent_end' => Carbon::parse('2026-09-03'),
            'subtotal' => 200000,
            'total' => 200000,
            'status' => 'active',
        ]);
        $returnRecord = ReturnRecord::create([
            'order_id' => $order->id,
            'condition' => 'minor_damage',
            'damage_cost' => 50000,
            'status' => 'approved',
            'returned_at' => Carbon::parse('2026-09-08'),
        ]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_role' => 'admin',
            'account_name' => 'Admin Summit',
        ])->post("/admin/pengembalian/{$order->id}/penalty", [
            'return_record_id' => $returnRecord->id,
            'status' => 'tidak_ada_sanksi',
            'complete_order' => 1,
        ]);

        $response->assertRedirect('/admin/pengembalian')
            ->assertSessionHas('error');
        $this->assertEquals('active', $order->fresh()->status);
        $this->assertDatabaseHas('return_records', [
            'id' => $returnRecord->id,
            'damage_cost' => 50000,
            'damage_paid_at' => null,
        ]);
    }

    /**
     * Admin membatalkan sanksi denda keterlambatan
     */
    public function test_admin_cancels_late_penalty(): void
    {
        $order = Order::create([
            'code' => 'ORD-TEST-007',
            'user_id' => $this->user->id,
            'rent_start' => Carbon::parse('2026-09-01'),
            'rent_end' => Carbon::parse('2026-09-03'),
            'subtotal' => 200000,
            'service_fee' => 0,
            'discount' => 0,
            'total' => 200000,
            'status' => 'completed',
        ]);

        $penalty = LatePenalty::create([
            'user_id' => $this->user->id,
            'order_id' => $order->id,
            'days_overdue' => 3,
            'fee_per_day' => 10000,
            'total_fee' => 30000,
            'status' => 'menunggu_pembayaran',
        ]);

        $response = $this->withSession([
            'account_id'   => 1,
            'account_role' => 'admin',
            'account_name' => 'Admin Summit',
        ])->post("/admin/pengembalian/{$order->id}/penalty/cancel");

        $response->assertRedirect('/admin/pengembalian');
        $response->assertSessionHas('success');

        $penalty->refresh();
        $this->assertEquals('dibatalkan', $penalty->status);
        $this->assertNotNull($penalty->cancelled_at);
    }

    /**
     * User membayar sanksi keterlambatan dan admin menyetujui pembayarannya
     */
    public function test_user_pays_penalty_and_admin_approves(): void
    {
        Storage::fake('local');
        Notification::fake();

        $order = Order::create([
            'code' => 'ORD-TEST-008',
            'user_id' => $this->user->id,
            'rent_start' => Carbon::parse('2026-09-01'),
            'rent_end' => Carbon::parse('2026-09-03'),
            'subtotal' => 200000,
            'service_fee' => 0,
            'discount' => 0,
            'total' => 200000,
            'status' => 'completed',
        ]);

        $penalty = LatePenalty::create([
            'user_id' => $this->user->id,
            'order_id' => $order->id,
            'days_overdue' => 2,
            'fee_per_day' => 10000,
            'total_fee' => 20000,
            'status' => 'menunggu_pembayaran',
        ]);

        // 1. User uploads payment proof
        $file = UploadedFile::fake()->create('bukti_denda.jpg', 100, 'image/jpeg');
        $response = $this->withSession([
            'account_id' => $this->user->id,
            'account_role' => 'customer',
            'account_name' => $this->user->name,
        ])->post("/history/{$order->id}/pay-denda", [
            'denda_proof' => $file,
        ]);

        $response->assertRedirect(route('history'));
        $response->assertSessionHas('status');

        $payment = Payment::where('order_id', $order->id)->where('status', 'pending')->first();
        $this->assertNotNull($payment);
        $this->assertEquals(20000, (int) $payment->amount);
        $this->assertTrue($payment->is_denda_payment);

        $penalty->refresh();
        $this->assertEquals($payment->id, $penalty->payment_id);

        // 2. Admin approves fine payment
        $approveResponse = $this->withSession([
            'account_id'   => 1,
            'account_role' => 'admin',
            'account_name' => 'Admin Summit',
        ])->from('/admin/pengembalian')->post("/admin/pengembalian/{$payment->id}/approve-denda");

        $approveResponse->assertRedirect('/admin/pengembalian');

        $payment->refresh();
        $this->assertEquals('success', $payment->status);

        $penalty->refresh();
        $this->assertEquals('sudah_dibayar', $penalty->status);
        $this->assertNotNull($penalty->paid_at);

        Notification::assertSentTo(
            $this->user,
            RentalStatusNotification::class,
            function ($notification) {
                return $notification->type === RentalStatusNotification::TYPE_LATE_PENALTY_PAID;
            }
        );
    }
}
