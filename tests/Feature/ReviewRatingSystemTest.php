<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewRatingSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;
    private User $userB;
    private Admin $admin;
    private Product $product;
    private Order $completedOrder;
    private Order $activeOrder;

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
            'sku' => 'TENT-APEX',
            'name' => 'Apex Ultralight',
            'subtitle' => '4-Season Expedition Tent',
            'price_per_day' => 250000,
            'stock_total' => 10,
            'stock_available' => 5,
            'is_active' => true,
        ]);

        $this->userA = User::create([
            'name' => 'Fajar Pratama',
            'username' => 'fajar_peaks',
            'email' => 'fajar@summit.id',
            'phone' => '08123456789',
            'domicile' => 'Bandung',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $this->userB = User::create([
            'name' => 'Dimas Explorer',
            'username' => 'dimas_exp',
            'email' => 'dimas@summit.id',
            'phone' => '08987654321',
            'domicile' => 'Jakarta',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        // Completed Order for User A
        $this->completedOrder = Order::create([
            'code' => 'RS-9988-ABC',
            'user_id' => $this->userA->id,
            'rent_start' => now()->subDays(5),
            'rent_end' => now()->subDays(2),
            'subtotal' => 750000,
            'service_fee' => 25000,
            'discount' => 0,
            'total' => 775000,
            'status' => 'completed',
        ]);

        OrderItem::create([
            'order_id' => $this->completedOrder->id,
            'product_id' => $this->product->id,
            'name' => $this->product->name,
            'quantity' => 1,
            'days' => 3,
            'unit_price' => 250000,
            'subtotal' => 750000,
        ]);

        // Active Order for User A
        $this->activeOrder = Order::create([
            'code' => 'RS-1122-ACT',
            'user_id' => $this->userA->id,
            'rent_start' => now(),
            'rent_end' => now()->addDays(3),
            'subtotal' => 500000,
            'service_fee' => 25000,
            'discount' => 0,
            'total' => 525000,
            'status' => 'active',
        ]);

        OrderItem::create([
            'order_id' => $this->activeOrder->id,
            'product_id' => $this->product->id,
            'name' => $this->product->name,
            'quantity' => 1,
            'days' => 2,
            'unit_price' => 250000,
            'subtotal' => 500000,
        ]);
    }

    public function test_user_with_completed_rental_can_submit_rating_and_review(): void
    {
        $response = $this->withSession([
            'account_id' => $this->userA->id,
            'account_name' => $this->userA->name,
            'account_role' => 'customer',
        ])->post('/reviews', [
            'order_id' => $this->completedOrder->id,
            'product_id' => $this->product->id,
            'rating' => 5,
            'comment' => 'Alatnya bagus dan kondisinya sangat baik saat ekspedisi!',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->userA->id,
            'order_id' => $this->completedOrder->id,
            'product_id' => $this->product->id,
            'rating' => 5,
            'comment' => 'Alatnya bagus dan kondisinya sangat baik saat ekspedisi!',
            'is_visible' => true,
        ]);

        $this->product->refresh();
        $this->assertEquals(5.0, (float) $this->product->rating);
        $this->assertEquals(1, $this->product->reviews_count);
    }

    public function test_submitted_review_appears_dynamically_on_home_page(): void
    {
        // Create review in database
        Review::create([
            'user_id' => $this->userA->id,
            'product_id' => $this->product->id,
            'order_id' => $this->completedOrder->id,
            'rating' => 5,
            'comment' => 'Peralatan tenda sangat kokoh menghadapi badai.',
            'is_visible' => true,
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('ULASAN PELANGGAN');
        $response->assertSee('Apa Kata Mereka?');
        $response->assertSee('Peralatan tenda sangat kokoh menghadapi badai.');
        $response->assertSee('Fajar Pratama');
        $response->assertSee('Apex Ultralight');
        $response->assertSee('FP'); // User Initials
        $response->assertSee('5.0/5.0');
    }

    public function test_home_page_shows_empty_state_when_no_reviews_exist(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Belum Ada Ulasan Pelanggan');
        $response->assertSee('Jadilah yang pertama menyewa alat');
    }

    public function test_user_cannot_review_same_order_twice(): void
    {
        Review::create([
            'user_id' => $this->userA->id,
            'product_id' => $this->product->id,
            'order_id' => $this->completedOrder->id,
            'rating' => 5,
            'comment' => 'Review pertama.',
            'is_visible' => true,
        ]);

        $response = $this->withSession([
            'account_id' => $this->userA->id,
            'account_name' => $this->userA->name,
            'account_role' => 'customer',
        ])->post('/reviews', [
            'order_id' => $this->completedOrder->id,
            'product_id' => $this->product->id,
            'rating' => 4,
            'comment' => 'Review kedua yang harus ditolak.',
        ]);

        $response->assertSessionHasErrors('review');
        $this->assertEquals(1, Review::where('order_id', $this->completedOrder->id)->count());
    }

    public function test_user_cannot_review_active_or_pending_order(): void
    {
        $response = $this->withSession([
            'account_id' => $this->userA->id,
            'account_name' => $this->userA->name,
            'account_role' => 'customer',
        ])->post('/reviews', [
            'order_id' => $this->activeOrder->id,
            'product_id' => $this->product->id,
            'rating' => 5,
            'comment' => 'Mencoba rating saat sewa masih aktif.',
        ]);

        $response->assertSessionHasErrors('review');
        $this->assertEquals(0, Review::where('order_id', $this->activeOrder->id)->count());
    }

    public function test_user_cannot_review_order_belonging_to_another_user(): void
    {
        // User B tries to review User A's completed order
        $response = $this->withSession([
            'account_id' => $this->userB->id,
            'account_name' => $this->userB->name,
            'account_role' => 'customer',
        ])->post('/reviews', [
            'order_id' => $this->completedOrder->id,
            'product_id' => $this->product->id,
            'rating' => 5,
            'comment' => 'Mencoba rating pesanan orang lain.',
        ]);

        $response->assertSessionHasErrors('review');
        $this->assertEquals(0, Review::where('order_id', $this->completedOrder->id)->count());
    }

    public function test_rating_validation_rejects_out_of_range_ratings(): void
    {
        // Test Rating 0
        $res0 = $this->withSession([
            'account_id' => $this->userA->id,
            'account_name' => $this->userA->name,
            'account_role' => 'customer',
        ])->post('/reviews', [
            'order_id' => $this->completedOrder->id,
            'product_id' => $this->product->id,
            'rating' => 0,
            'comment' => 'Rating tidak valid.',
        ]);
        $res0->assertSessionHasErrors('rating');

        // Test Rating 6
        $res6 = $this->withSession([
            'account_id' => $this->userA->id,
            'account_name' => $this->userA->name,
            'account_role' => 'customer',
        ])->post('/reviews', [
            'order_id' => $this->completedOrder->id,
            'product_id' => $this->product->id,
            'rating' => 6,
            'comment' => 'Rating tidak valid.',
        ]);
        $res6->assertSessionHasErrors('rating');
    }

    public function test_guest_cannot_submit_review(): void
    {
        $response = $this->post('/reviews', [
            'order_id' => $this->completedOrder->id,
            'product_id' => $this->product->id,
            'rating' => 5,
            'comment' => 'Guest mencoba review.',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_history_page_shows_rating_button_for_unreviewed_and_badge_for_reviewed(): void
    {
        // 1. Before review: shows "Beri Rating"
        $resBefore = $this->withSession([
            'account_id' => $this->userA->id,
            'account_name' => $this->userA->name,
            'account_role' => 'customer',
        ])->get('/history');

        $resBefore->assertStatus(200);
        $resBefore->assertSee('Beri Rating');

        // 2. Add review
        Review::create([
            'user_id' => $this->userA->id,
            'product_id' => $this->product->id,
            'order_id' => $this->completedOrder->id,
            'rating' => 5,
            'comment' => 'Sudah direview.',
            'is_visible' => true,
        ]);

        // 3. After review: shows "Sudah Diulas"
        $resAfter = $this->withSession([
            'account_id' => $this->userA->id,
            'account_name' => $this->userA->name,
            'account_role' => 'customer',
        ])->get('/history');

        $resAfter->assertStatus(200);
        $resAfter->assertSee('Sudah Diulas');
    }

    public function test_admin_can_view_moderate_and_delete_reviews(): void
    {
        $review = Review::create([
            'user_id' => $this->userA->id,
            'product_id' => $this->product->id,
            'order_id' => $this->completedOrder->id,
            'rating' => 5,
            'comment' => 'Review yang akan dimoderasi.',
            'is_visible' => true,
        ]);

        // Admin views Website page
        $resAdmin = $this->withSession([
            'account_id' => $this->admin->getKey(),
            'account_name' => $this->admin->name,
            'account_role' => 'admin',
        ])->get('/admin/website');

        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('Ulasan &amp; Rating Pelanggan', false);
        $resAdmin->assertSee('Review yang akan dimoderasi.');

        // Admin toggles visibility (hide)
        $toggleRes = $this->withSession([
            'account_id' => $this->admin->getKey(),
            'account_name' => $this->admin->name,
            'account_role' => 'admin',
        ])->patch('/admin/reviews/' . $review->id . '/toggle');

        $toggleRes->assertSessionHas('success');
        $review->refresh();
        $this->assertFalse($review->is_visible);

        // Hidden review should NOT appear on Home page
        $homeRes = $this->get('/');
        $homeRes->assertDontSee('Review yang akan dimoderasi.');

        // Admin deletes review
        $delRes = $this->withSession([
            'account_id' => $this->admin->getKey(),
            'account_name' => $this->admin->name,
            'account_role' => 'admin',
        ])->delete('/admin/reviews/' . $review->id);

        $delRes->assertSessionHas('success');
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_product_detail_page_displays_customer_reviews(): void
    {
        Review::create([
            'user_id' => $this->userA->id,
            'product_id' => $this->product->id,
            'order_id' => $this->completedOrder->id,
            'rating' => 5,
            'comment' => 'Tenda ini sangat ringan dan kokoh.',
            'is_visible' => true,
        ]);

        $res = $this->get('/catalog/' . $this->product->id);
        $res->assertStatus(200);
        $res->assertSee('Pengalaman Pelanggan');
        $res->assertSee('Tenda ini sangat ringan dan kokoh.');
        $res->assertSee('Fajar Pratama');
    }

    public function test_user_can_review_same_product_on_different_orders(): void
    {
        $order1 = Order::create([
            'code' => 'RS-RENT-001',
            'user_id' => $this->userA->id,
            'rent_start' => now()->subDays(20),
            'rent_end' => now()->subDays(15),
            'subtotal' => 500000,
            'service_fee' => 25000,
            'discount' => 0,
            'total' => 525000,
            'status' => 'completed',
        ]);
        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => $this->product->id,
            'name' => $this->product->name,
            'quantity' => 1,
            'days' => 2,
            'unit_price' => 250000,
            'subtotal' => 500000,
        ]);

        $order2 = Order::create([
            'code' => 'RS-RENT-002',
            'user_id' => $this->userA->id,
            'rent_start' => now()->subDays(10),
            'rent_end' => now()->subDays(5),
            'subtotal' => 750000,
            'service_fee' => 25000,
            'discount' => 0,
            'total' => 775000,
            'status' => 'completed',
        ]);
        OrderItem::create([
            'order_id' => $order2->id,
            'product_id' => $this->product->id,
            'name' => $this->product->name,
            'quantity' => 1,
            'days' => 3,
            'unit_price' => 250000,
            'subtotal' => 750000,
        ]);

        $session = [
            'account_id' => $this->userA->id,
            'account_name' => $this->userA->name,
            'account_role' => 'customer',
        ];

        // Rating pertama berhasil
        $res1 = $this->withSession($session)->post('/reviews', [
            'order_id' => $order1->id,
            'product_id' => $this->product->id,
            'rating' => 4,
            'comment' => 'Pertama kali sewa, kondisi bagus.',
        ]);
        $res1->assertSessionHas('success');
        $this->assertEquals(1, Review::where('product_id', $this->product->id)->count());

        // Rating kedua (produk sama, pesanan berbeda) juga harus berhasil
        $res2 = $this->withSession($session)->post('/reviews', [
            'order_id' => $order2->id,
            'product_id' => $this->product->id,
            'rating' => 5,
            'comment' => 'Kedua kalinya tetap memuaskan!',
        ]);
        $res2->assertSessionHas('success');
        $this->assertEquals(2, Review::where('product_id', $this->product->id)->count());
        $this->assertEquals(1, Review::where('order_id', $order2->id)->count());
    }
}
