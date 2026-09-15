<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReturnRecord;
use App\Models\User;
use App\Services\ReturnNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Durasi penyewaan berbasis tanggal kalender (INKLUSIF).
 *
 * Durasi = selisih tanggal pengembalian - tanggal mulai + 1 hari.
 * - 30/08 -> 30/08 = 1 hari
 * - 30/08 -> 31/08 = 2 hari
 * - 30/08 -> 02/09 = 4 hari
 *
 * Test memakai tanggal relatif terhadap hari ini agar tetap lolos kapan pun dijalankan.
 */
class RentalDurationCalendarTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'Penyewa Kalender',
            'username' => 'penyewa_kalender',
            'email' => 'penyewa.kalender@summit.test',
            'domicile' => 'Yogyakarta',
            'password' => 'password123',
            'status' => 'active',
        ]);

        $category = Category::create([
            'name' => 'Tents & Shelters',
            'slug' => 'tents-shelters',
            'description' => 'Expedition grade tents',
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'sku' => 'SS-TEN-777',
            'name' => 'Apex Calendar 6P',
            'subtitle' => 'Calendar Based Rental Tent',
            'price_per_day' => 250000,
            'stock_total' => 5,
            'stock_available' => 5,
            'is_active' => true,
        ]);
    }

    private function customerSession(User $user): array
    {
        return [
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_username' => $user->username,
            'account_role' => 'customer',
        ];
    }

    private function today(): string
    {
        return now()->startOfDay()->format('Y-m-d');
    }

    private function addDays(int $days): string
    {
        return now()->startOfDay()->addDays($days)->format('Y-m-d');
    }

    /**
     * TEST 1 — Tanggal mulai = tanggal pengembalian = 1 hari sewa.
     */
    public function test_same_day_start_and_return_is_one_day(): void
    {
        $response = $this->withSession($this->customerSession($this->owner))
            ->post('/cart/add', [
                'product_id' => $this->product->id,
                'rent_start' => $this->today(),
                'rent_end' => $this->today(),
                'quantity' => 1,
            ]);

        $response->assertRedirect('/cart');

        $item = session('cart_items')[$this->product->id];
        $this->assertEquals(1, $item['days']);
        $this->assertEquals($this->today(), $item['rent_start']);
        $this->assertEquals($this->today(), $item['rent_end']);
        $this->assertEquals(250000, $item['subtotal']);
    }

    /**
     * TEST 2 — 30/08 -> 31/08 = 2 hari sewa (harga dikalikan 2).
     */
    public function test_consecutive_days_are_two_rental_days(): void
    {
        $response = $this->withSession($this->customerSession($this->owner))
            ->post('/cart/add', [
                'product_id' => $this->product->id,
                'rent_start' => $this->today(),
                'rent_end' => $this->addDays(1),
                'quantity' => 1,
            ]);

        $response->assertRedirect('/cart');

        $item = session('cart_items')[$this->product->id];
        $this->assertEquals(2, $item['days']);
        $this->assertEquals($this->today(), $item['rent_start']);
        $this->assertEquals($this->addDays(1), $item['rent_end']);
        $this->assertEquals(500000, $item['subtotal']);
    }

    /**
     * TEST 3 — 30/08 -> 02/09 = 4 hari sewa (rentang kalender penuh).
     */
    public function test_spanning_multiple_days_counts_inclusive_days(): void
    {
        $this->withSession($this->customerSession($this->owner))
            ->post('/cart/add', [
                'product_id' => $this->product->id,
                'rent_start' => $this->today(),
                'rent_end' => $this->addDays(3),
                'quantity' => 1,
            ]);

        $item = session('cart_items')[$this->product->id];
        $this->assertEquals(4, $item['days']);
        $this->assertEquals(1000000, $item['subtotal']);

        // Model helper juga konsisten: inklusif +1.
        $order = Order::create([
            'code' => 'RS-CAL-0003',
            'user_id' => $this->owner->id,
            'rent_start' => $this->today(),
            'rent_end' => $this->addDays(3),
            'subtotal' => 1000000,
            'service_fee' => 25000,
            'discount' => 0,
            'total' => 1025000,
            'status' => 'active',
        ]);
        $this->assertEquals(4, $order->rentalDays());
    }

    /**
     * TEST WAJIB 3 — 30/08 -> 01/09 = 3 hari sewa.
     */
    public function test_same_day_plus_two_is_three_rental_days(): void
    {
        $this->withSession($this->customerSession($this->owner))
            ->post('/cart/add', [
                'product_id' => $this->product->id,
                'rent_start' => $this->today(),
                'rent_end' => $this->addDays(2),
                'quantity' => 1,
            ]);

        $item = session('cart_items')[$this->product->id];
        $this->assertEquals(3, $item['days']);
        $this->assertEquals(750000, $item['subtotal']);
    }

    /**
     * TEST WAJIB 5 — 30/08 -> 05/09 = 7 hari sewa.
     */
    public function test_seven_calendar_days_span_is_seven_rental_days(): void
    {
        $this->withSession($this->customerSession($this->owner))
            ->post('/cart/add', [
                'product_id' => $this->product->id,
                'rent_start' => $this->today(),
                'rent_end' => $this->addDays(6),
                'quantity' => 1,
            ]);

        $item = session('cart_items')[$this->product->id];
        $this->assertEquals(7, $item['days']);
        $this->assertEquals(1750000, $item['subtotal']);
    }

    /**
     * TEST 4 — Tanggal pengembalian sebelum tanggal mulai DITOLAK.
     */
    public function test_return_before_start_is_rejected(): void
    {
        $response = $this->withSession($this->customerSession($this->owner))
            ->post('/cart/add', [
                'product_id' => $this->product->id,
                'rent_start' => $this->addDays(2),
                'rent_end' => $this->addDays(1),
                'quantity' => 1,
            ]);

        $response->assertSessionHasErrors('error');
        $this->assertStringContainsString(
            'Tanggal pengembalian tidak boleh sebelum tanggal mulai penyewaan.',
            session('errors')->first('error')
        );
        $this->assertEmpty(session('cart_items', []));
    }

    /**
     * TEST 4b — Tanggal mulai sebelum hari ini DITOLAK.
     */
    public function test_start_before_today_is_rejected(): void
    {
        $response = $this->withSession($this->customerSession($this->owner))
            ->post('/cart/add', [
                'product_id' => $this->product->id,
                'rent_start' => $this->addDays(-1),
                'rent_end' => $this->today(),
                'quantity' => 1,
            ]);

        $response->assertSessionHasErrors('error');
        $this->assertStringContainsString(
            'Tanggal mulai tidak boleh sebelum hari ini.',
            session('errors')->first('error')
        );
        $this->assertEmpty(session('cart_items', []));
    }

    /**
     * TEST 5 — Tanggal tersimpan di DB saat order diproses dan tetap ada setelah "reopen".
     */
    public function test_dates_persisted_on_process_and_survive_reopen(): void
    {
        Storage::fake('public');

        $response = $this->withSession([
            'account_id' => $this->owner->id,
            'account_name' => $this->owner->name,
            'account_role' => 'customer',
            'payment_deadline' => time() + 300,
            'cart_items' => [
                $this->product->id => [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'days' => 4,
                    'quantity' => 1,
                    'price_per_day' => 250000,
                    'subtotal' => 1000000,
                    'image' => 'tent.jpg',
                    'rent_start' => $this->today(),
                    'rent_end' => $this->addDays(3),
                ],
            ],
        ])->post('/payment/process', [
            'payment_method' => 'qris',
            'proof' => UploadedFile::fake()->create('proof.jpg', 100),
        ]);

        $response->assertRedirect('/history');

        $order = Order::where('user_id', $this->owner->id)->firstOrFail();
        $this->assertEquals($this->today(), $order->rent_start->format('Y-m-d'));
        $this->assertEquals($this->addDays(3), $order->rent_end->format('Y-m-d'));
        $this->assertEquals(4, $order->rentalDays());
        $this->assertEquals(4, $order->items->first()->days);

        // Reopen: data berasal dari DB, bukan hanya dari halaman pertama.
        $this->withSession($this->customerSession($this->owner))->get('/cart');
        $reopened = Order::findOrFail($order->id);
        $this->assertEquals($this->addDays(3), $reopened->rent_end->format('Y-m-d'));
        $this->assertEquals(4, $reopened->rentalDays());

        // Halaman riwayat menampilkan booking yang tersimpan di DB.
        $history = $this->withSession($this->customerSession($this->owner))
            ->get(route('history'));
        $history->assertStatus(200);
        $history->assertSee($order->code, false);
    }

    /**
     * TEST WAJIB 6 — Total harga mengikuti durasi yang benar (bukan 1 hari).
     * Rp250.000/hari x 4 hari = Rp1.000.000 (subtotal), total = Rp1.025.000.
     */
    public function test_payment_total_follows_selected_duration(): void
    {
        Storage::fake('public');

        $this->withSession($this->customerSession($this->owner))
            ->post('/cart/add', [
                'product_id' => $this->product->id,
                'rent_start' => $this->today(),
                'rent_end' => $this->addDays(3),
                'quantity' => 1,
            ]);

        $item = session('cart_items')[$this->product->id];
        $this->assertEquals(4, $item['days']);
        $this->assertEquals(1000000, $item['subtotal']);

        $this->withSession([
            'account_id' => $this->owner->id,
            'account_name' => $this->owner->name,
            'account_role' => 'customer',
            'payment_deadline' => time() + 300,
        ])->post('/payment/process', [
            'payment_method' => 'qris',
            'proof' => UploadedFile::fake()->create('proof.jpg', 100),
        ])->assertRedirect('/history');

        $order = Order::where('user_id', $this->owner->id)->firstOrFail();
        $this->assertEquals(4, $order->rentalDays());
        $this->assertEquals(4, $order->items->first()->days);
        $this->assertEquals(1000000, $order->subtotal);
        $this->assertEquals(1025000, $order->total);
    }

    /**
     * TEST 6 — Order sudah dikembalikan (completed) tidak menghasilkan
     * notifikasi keterlambatan apa pun.
     */
    public function test_returned_order_is_not_overdue_and_no_notification(): void
    {
        $order = Order::create([
            'code' => 'RS-CAL-0006',
            'user_id' => $this->owner->id,
            'rent_start' => $this->addDays(-7),
            'rent_end' => $this->addDays(-1),
            'subtotal' => 500000,
            'service_fee' => 25000,
            'discount' => 0,
            'total' => 525000,
            'status' => 'completed',
        ]);

        ReturnRecord::create([
            'order_id' => $order->id,
            'user_id' => $this->owner->id,
            'code' => 'RET-' . strtoupper(uniqid()),
            'items_json' => json_encode([['name' => $this->product->name, 'qty' => 1]]),
            'condition' => 'good',
            'status' => 'approved',
        ]);

        $fresh = Order::findOrFail($order->id);
        $this->assertEquals('returned', $fresh->returnState()['state']);

        $this->assertNull(ReturnNotificationService::intendedType($fresh));

        $this->withSession($this->customerSession($this->owner))
            ->get(route('history', ['status' => 'active']))
            ->assertStatus(200);

        $this->assertFalse(
            $this->owner->notifications()->exists(),
            'Order yang sudah dikembalikan tidak boleh memicu notifikasi tenggat.'
        );
    }

    /**
     * TEST 7 — Durasi > 30 hari ditolak (batas maksimal).
     */
    public function test_duration_longer_than_thirty_days_is_rejected(): void
    {
        $response = $this->withSession($this->customerSession($this->owner))
            ->post('/cart/add', [
                'product_id' => $this->product->id,
                'rent_start' => $this->today(),
                'rent_end' => $this->addDays(30),
                'quantity' => 1,
            ]);

        $response->assertSessionHasErrors('error');
        $this->assertStringContainsString('Durasi maksimal 30 hari.', session('errors')->first('error'));
        $this->assertEmpty(session('cart_items', []));
    }

    /**
     * TEST 8 — Update keranjang dengan tanggal baru menghitung ulang durasi
     * (misal from 30/08-31/08 jadi 30/08-02/09 = 4 hari).
     */
    public function test_cart_update_with_dates_recomputes_duration(): void
    {
        session([
            'account_id' => $this->owner->id,
            'account_role' => 'customer',
            'cart_items' => [
                $this->product->id => [
                    'id' => $this->product->id,
                    'is_bundle' => false,
                    'name' => $this->product->name,
                    'category' => 'Tents & Shelters',
                    'days' => 2,
                    'rent_start' => $this->today(),
                    'rent_end' => $this->addDays(1),
                    'quantity' => 1,
                    'stock_available' => 5,
                    'price_per_day' => 250000,
                    'subtotal' => 500000,
                    'selected' => true,
                    'image' => 'tent.jpg',
                ],
            ],
        ]);

        $this->withSession([
            'account_id' => $this->owner->id,
            'account_role' => 'customer',
        ])->json('POST', '/cart/update/' . $this->product->id, [
            'days' => 2,
            'quantity' => 1,
            'rent_start' => $this->today(),
            'rent_end' => $this->addDays(3),
        ])->assertOk()
            ->assertJson(['success' => true]);

        $item = session('cart_items')[$this->product->id];
        $this->assertEquals(4, $item['days']);
        $this->assertEquals($this->addDays(3), $item['rent_end']);
        $this->assertEquals(1000000, $item['subtotal']);
    }
}