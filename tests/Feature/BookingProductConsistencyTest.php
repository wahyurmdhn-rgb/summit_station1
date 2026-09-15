<?php

namespace Tests\Feature;

use App\Models\Bundle;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookingProductConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_and_catalog_consistency_for_multiple_products_and_bundles(): void
    {
        // 1. Setup Categories
        $tentCat = Category::create(['name' => 'Tents & Shelters', 'slug' => 'tents-shelters']);
        $packCat = Category::create(['name' => 'Backpacks', 'slug' => 'backpacks']);
        $lightCat = Category::create(['name' => 'Lighting', 'slug' => 'lighting']);
        $footCat = Category::create(['name' => 'Footwear', 'slug' => 'footwear']);

        // 2. Create 5 Distinct Products
        $prodA = Product::create([
            'category_id' => $tentCat->id,
            'sku' => 'TENT-001-OR',
            'name' => 'Apex Ultralight V2',
            'subtitle' => '4-Season Expedition Tent',
            'price_per_day' => 250000,
            'stock_total' => 10,
            'stock_available' => 8,
            'is_active' => true,
        ]);

        $prodB = Product::create([
            'category_id' => $packCat->id,
            'sku' => 'BACK-042-GR',
            'name' => 'Terra 65 Expedition',
            'subtitle' => 'Ergonomic Load Balance',
            'price_per_day' => 125000,
            'stock_total' => 10,
            'stock_available' => 6,
            'is_active' => true,
        ]);

        $prodC = Product::create([
            'category_id' => $tentCat->id,
            'sku' => 'SS-TEN-055',
            'name' => 'MSR Hubba Hubba NX',
            'subtitle' => 'Ultralight 2-Person Backpacking Tent',
            'price_per_day' => 140000,
            'stock_total' => 12,
            'stock_available' => 7,
            'is_active' => true,
        ]);

        $prodD = Product::create([
            'category_id' => $footCat->id,
            'sku' => 'SS-FTW-021',
            'name' => 'Lowa Renegade GTX',
            'subtitle' => 'All-Terrain Gore-Tex Trekking Boots',
            'price_per_day' => 65000,
            'stock_total' => 15,
            'stock_available' => 10,
            'is_active' => true,
        ]);

        $prodE = Product::create([
            'category_id' => $lightCat->id,
            'sku' => 'SS-LGT-507',
            'name' => 'Petzl Swift RL 900',
            'subtitle' => 'Reactive Headlamp 900 Lumens',
            'price_per_day' => 15000,
            'stock_total' => 20,
            'stock_available' => 16,
            'is_active' => true,
        ]);

        // 3. Create 2 Distinct Bundles
        $bundle1 = Bundle::create([
            'name' => 'Mountain Summit Package',
            'description' => 'Complete high-altitude gear including 4-season tent, -20F sleeping bag, and technical backpack.',
            'price' => 250000,
            'is_active' => true,
        ]);

        $bundle2 = Bundle::create([
            'name' => '4-Person Camping Package',
            'description' => 'Family-sized tent, 4 sleeping pads, and a complete basecamp cooking system.',
            'price' => 180000,
            'is_active' => true,
        ]);

        // Attach component products so the bundles are valid & bookable packages.
        $bundle1->products()->sync([
            $prodA->id => ['quantity' => 1],
            $prodC->id => ['quantity' => 2],
        ]);
        $bundle2->products()->sync([
            $prodB->id => ['quantity' => 1],
            $prodE->id => ['quantity' => 1],
        ]);

        // ─── TEST 1: Catalog Page Contains Accurate Links for All 5 Products & 2 Bundles ───
        $catalogRes = $this->get('/catalog');
        $catalogRes->assertStatus(200);

        // Verify Bundle Links
        $catalogRes->assertSee(route('catalog.bundle', $bundle1->id));
        $catalogRes->assertSee(route('catalog.bundle', $bundle2->id));

        // Verify Product Links
        $catalogRes->assertSee(route('catalog.show', $prodA->id));
        $catalogRes->assertSee(route('catalog.show', $prodB->id));
        $catalogRes->assertSee(route('catalog.show', $prodC->id));
        $catalogRes->assertSee(route('catalog.show', $prodD->id));
        $catalogRes->assertSee(route('catalog.show', $prodE->id));

        // Authenticate as a static customer so booking forms (with hidden id inputs) render
        $detailUser = User::create([
            'name' => 'Detail Tester',
            'username' => 'detail_tester',
            'email' => 'detail.tester@summit.id',
            'password' => 'password123',
        ]);
        $this->withSession([
            'account_id' => $detailUser->id,
            'account_name' => $detailUser->name,
            'account_role' => 'customer',
        ]);

        // ─── TEST 2: Product A (Apex Ultralight V2) ───
        $resA = $this->get('/catalog/' . $prodA->id);
        $resA->assertStatus(200);
        $resA->assertSee('Apex Ultralight V2');
        $resA->assertSee('250.000');
        $resA->assertSee('value="' . $prodA->id . '"', false);
        $resA->assertDontSee('Terra 65 Expedition');

        // ─── TEST 3: Product B (Terra 65 Expedition) ───
        $resB = $this->get('/catalog/' . $prodB->id);
        $resB->assertStatus(200);
        $resB->assertSee('Terra 65 Expedition');
        $resB->assertSee('125.000');
        $resB->assertSee('value="' . $prodB->id . '"', false);
        $resB->assertDontSee('Apex Ultralight V2');

        // ─── TEST 4: Product C (MSR Hubba Hubba NX) ───
        $resC = $this->get('/catalog/' . $prodC->id);
        $resC->assertStatus(200);
        $resC->assertSee('MSR Hubba Hubba NX');
        $resC->assertSee('140.000');
        $resC->assertSee('value="' . $prodC->id . '"', false);

        // ─── TEST 5: Product D (Lowa Renegade GTX) ───
        $resD = $this->get('/catalog/' . $prodD->id);
        $resD->assertStatus(200);
        $resD->assertSee('Lowa Renegade GTX');
        $resD->assertSee('65.000');
        $resD->assertSee('value="' . $prodD->id . '"', false);

        // ─── TEST 6: Product E (Petzl Swift RL 900) ───
        $resE = $this->get('/catalog/' . $prodE->id);
        $resE->assertStatus(200);
        $resE->assertSee('Petzl Swift RL 900');
        $resE->assertSee('15.000');
        $resE->assertSee('value="' . $prodE->id . '"', false);

        // ─── TEST 7: Bundle 1 (Mountain Summit Package) ───
        $resBundle1 = $this->get('/catalog/bundle/' . $bundle1->id);
        $resBundle1->assertStatus(200);
        $resBundle1->assertSee('Mountain Summit Package');
        $resBundle1->assertSee('250.000');
        $resBundle1->assertSee('name="bundle_id" value="' . $bundle1->id . '"', false);

        // ─── TEST 8: Bundle 2 (4-Person Camping Package) ───
        $resBundle2 = $this->get('/catalog/bundle/' . $bundle2->id);
        $resBundle2->assertStatus(200);
        $resBundle2->assertSee('4-Person Camping Package');
        $resBundle2->assertSee('180.000');
        $resBundle2->assertSee('name="bundle_id" value="' . $bundle2->id . '"', false);

        // ─── TEST 9: Direct URL & 404 Handling ───
        $resInvalidProduct = $this->get('/catalog/999999');
        $resInvalidProduct->assertStatus(200);
        $resInvalidProduct->assertSee('Alat Tidak Ditemukan');

        $resInvalidBundle = $this->get('/catalog/bundle/999999');
        $resInvalidBundle->assertStatus(200);
        $resInvalidBundle->assertSee('Alat Tidak Ditemukan');

        // ─── TEST 10: Full Cart & Checkout Workflow Database Consistency ───
        $user = User::create([
            'name' => 'Bima Sakti',
            'username' => 'bima_peak',
            'email' => 'bima@summit.id',
            'password' => 'password123',
        ]);

        // Add Product A to cart
        $this->withSession([
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_role' => 'customer',
        ])->post('/cart/add', [
            'product_id' => $prodA->id,
            'days' => 4,
            'quantity' => 1,
        ])->assertRedirect('/cart');

        // Process payment
        Storage::fake('public');
        $this->withSession([
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_role' => 'customer',
            'payment_deadline' => time() + 300,
            'cart_items' => [
                $prodA->id => [
                    'id' => $prodA->id,
                    'name' => $prodA->name,
                    'category' => 'Tents & Shelters',
                    'subtitle' => '4-Season Expedition Tent',
                    'days' => 4,
                    'quantity' => 1,
                    'price_per_day' => 250000,
                    'subtotal' => 1000000,
                    'image' => 'tent.jpg',
                ]
            ]
        ])->post('/payment/process', [
            'payment_method' => 'qris',
            'proof' => UploadedFile::fake()->create('proof.jpg', 100),
        ])->assertRedirect('/history');

        // Assert database record 100% matches Product A
        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals(1000000 + 25000, $order->total);

        $orderItem = OrderItem::where('order_id', $order->id)->first();
        $this->assertNotNull($orderItem);
        $this->assertEquals($prodA->id, $orderItem->product_id);
        $this->assertEquals('Apex Ultralight V2', $orderItem->name);
        $this->assertEquals(250000, $orderItem->unit_price);
        $this->assertEquals(4, $orderItem->days);
        $this->assertEquals(1000000, $orderItem->subtotal);

        $payment = Payment::where('order_id', $order->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('pending', $payment->status);
    }
}
