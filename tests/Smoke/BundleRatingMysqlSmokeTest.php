<?php

namespace Tests\Smoke;

use App\Models\Bundle;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Smoke test: reproduces the bundle-order rating bug on real MySQL.
 * A bundle order item has product_id = NULL (as created by PaymentController::process),
 * yet reviews.product_id is NOT NULL. Verifies the ReviewController resolves a valid
 * product_id from the order/bundle relationship. Cleans up all created rows.
 */
class BundleRatingMysqlSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        parent::tearDown();
    }

    public function test_bundle_order_rating_gets_valid_product_id_no_sql_error(): void
    {
        $this->assertSame('mysql', config('database.default'));

        $category = Category::create([
            'name' => 'Smoke Cat ' . uniqid(),
            'slug' => 'smoke-cat-' . uniqid(),
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'BT-' . uniqid(),
            'name' => 'Bundle Test Product',
            'price_per_day' => 100000,
            'stock_total' => 5,
            'stock_available' => 5,
            'is_active' => true,
        ]);

        $bundle = Bundle::create([
            'name' => 'Bundle Smoke ' . uniqid(),
            'description' => 'test',
            'price' => 200000,
            'is_active' => true,
        ]);
        DB::table('bundle_product')->insert([
            'bundle_id' => $bundle->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $user = User::create([
            'name' => 'Bundle Rater',
            'email' => 'bundle.rater.' . uniqid() . '@summit.test',
            'username' => 'bundle_rate_' . uniqid(),
            'password' => 'password',
        ]);

        $order = Order::create([
            'code' => 'RS-BRCK-' . strtoupper(substr(uniqid(), -5)),
            'user_id' => $user->id,
            'rent_start' => now()->subDays(5),
            'rent_end' => now()->subDays(2),
            'subtotal' => 600000,
            'service_fee' => 25000,
            'discount' => 0,
            'total' => 625000,
            'status' => 'completed',
        ]);

        // Bundle order item WITHOUT product_id (bundle_id intentionally null = legacy data).
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'bundle_id' => $bundle->id,
            'name' => $bundle->name,
            'quantity' => 1,
            'days' => 3,
            'unit_price' => 200000,
            'subtotal' => 600000,
        ]);

        // POST /reviews WITHOUT product_id (simulates the bundle-order frontend that
        // cannot derive a product_id from order_items, matching the original bug).
        $response = $this->withSession([
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_role' => 'customer',
        ])->post('/reviews', [
            'order_id' => $order->id,
            'rating' => 5,
            'comment' => 'Bundle order rating smoke test.',
        ]);

        $response->assertSessionHas('success');

        $review = Review::where('order_id', $order->id)->first();
        $this->assertNotNull($review, 'Review must be created without SQL 23000');
        $this->assertEquals($user->id, $review->user_id);
        $this->assertEquals($order->id, $review->order_id);
        $this->assertEquals($product->id, $review->product_id, 'product_id must resolve to a valid product');
        $this->assertEquals(5, $review->rating);
        $this->assertEquals('Bundle order rating smoke test.', $review->comment);
        $this->assertEquals(true, $review->is_visible);

        fwrite(STDERR, 'BUNDLE RATING OK: order=' . $order->id
            . ' product_id=' . $review->product_id . " no SQL error\n");
    }
}
