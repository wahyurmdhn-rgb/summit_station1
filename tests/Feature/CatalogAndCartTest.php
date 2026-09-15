<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogAndCartTest extends TestCase
{
    use RefreshDatabase;

    private function createSampleData(): array
    {
        $category1 = Category::create([
            'name' => 'Tents & Shelters',
            'slug' => 'tents-shelters',
        ]);

        $category2 = Category::create([
            'name' => 'Backpacks & Carriers',
            'slug' => 'backpacks-carriers',
        ]);

        $product1 = Product::create([
            'category_id' => $category1->id,
            'sku' => 'SS-TEN-001',
            'name' => 'Apex Predator 4P Expedition',
            'subtitle' => '4-Season Alpine Shelter',
            'price_per_day' => 125000,
            'stock_total' => 5,
            'stock_available' => 5,
            'is_active' => true,
        ]);

        $product2 = Product::create([
            'category_id' => $category2->id,
            'sku' => 'SS-BPK-002',
            'name' => 'Osprey Aether 65L Pro',
            'subtitle' => 'Custom Fit Heavy Load Carrier',
            'price_per_day' => 85000,
            'stock_total' => 3,
            'stock_available' => 3,
            'is_active' => true,
        ]);

        $productOut = Product::create([
            'category_id' => $category1->id,
            'sku' => 'SS-TEN-OUT',
            'name' => 'Zero Stock Tent',
            'subtitle' => 'Out of stock model',
            'price_per_day' => 100000,
            'stock_total' => 0,
            'stock_available' => 0,
            'is_active' => true,
        ]);

        return [$category1, $category2, $product1, $product2, $productOut];
    }

    public function test_catalog_displays_products_and_supports_filtering(): void
    {
        [$category1, $category2, $product1, $product2, $productOut] = $this->createSampleData();

        // 1. Visit Catalog
        $response = $this->get('/catalog');
        $response->assertStatus(200);
        $response->assertSee('Apex Predator 4P Expedition');
        $response->assertSee('Osprey Aether 65L Pro');

        // 2. Search Keyword
        $searchResponse = $this->get('/catalog?search=Osprey');
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('Osprey Aether 65L Pro');
        $searchResponse->assertDontSee('Apex Predator 4P Expedition');

        // 3. Category Filter
        $catResponse = $this->get('/catalog?category[]=' . $category1->slug);
        $catResponse->assertStatus(200);
        $catResponse->assertSee('Apex Predator 4P Expedition');
        $catResponse->assertDontSee('Osprey Aether 65L Pro');

        // 4. Availability Filter
        $availResponse = $this->get('/catalog?availability=available');
        $availResponse->assertStatus(200);
        $availResponse->assertSee('Apex Predator 4P Expedition');
        $availResponse->assertDontSee('Zero Stock Tent');
    }

    public function test_product_detail_and_not_found_handling(): void
    {
        [$category1, $category2, $product1] = $this->createSampleData();

        // Valid product detail
        $response = $this->get('/catalog/' . $product1->id);
        $response->assertStatus(200);
        $response->assertSee('Apex Predator 4P Expedition');
        $response->assertSee('TERSEDIA (STOK: 5)');

        // Invalid product detail (should show clean "Alat Tidak Ditemukan" view)
        $invalidResponse = $this->get('/catalog/99999');
        $invalidResponse->assertStatus(200);
        $invalidResponse->assertSee('Alat Tidak Ditemukan');
    }

    public function test_add_to_cart_with_stock_validation(): void
    {
        [$category1, $category2, $product1, $product2, $productOut] = $this->createSampleData();

        $user = User::create([
            'name' => 'Cart Flow Member',
            'username' => 'cartflowmember',
            'email' => 'cart.flow@summit.id',
            'password' => 'password',
        ]);

        $this->withSession([
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_role' => 'customer',
        ]);

        // 1. Add valid quantity
        $response = $this->post('/cart/add', [
            'product_id' => $product1->id,
            'days' => 4,
            'quantity' => 1,
        ]);
        $response->assertRedirect('/cart');
        $this->assertEquals(1, count(session('cart_items')));

        // 2. Add with quantity exceeding available stock (should fail)
        $failResponse = $this->post('/cart/add', [
            'product_id' => $product1->id,
            'days' => 4,
            'quantity' => 99,
        ]);
        $failResponse->assertSessionHasErrors('error');

        // 3. Add out of stock product (should fail)
        $outResponse = $this->post('/cart/add', [
            'product_id' => $productOut->id,
            'days' => 4,
            'quantity' => 1,
        ]);
        $outResponse->assertSessionHasErrors('error');
    }

    public function test_cart_management_update_remove_and_empty_state(): void
    {
        [$category1, $category2, $product1, $product2] = $this->createSampleData();

        $user = User::create([
            'name' => 'Cart Manage Member',
            'username' => 'cartmanagemember',
            'email' => 'cart.manage@summit.id',
            'password' => 'password',
        ]);

        // Set session cart with 2 items
        $this->withSession([
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_role' => 'customer',
            'cart_items' => [
                $product1->id => [
                    'id' => $product1->id,
                    'name' => $product1->name,
                    'category' => 'Tents',
                    'subtitle' => 'Expedition Shelter',
                    'days' => 4,
                    'quantity' => 1,
                    'price_per_day' => 125000,
                    'subtotal' => 500000,
                    'image' => 'tent.jpg',
                ],
                $product2->id => [
                    'id' => $product2->id,
                    'name' => $product2->name,
                    'category' => 'Backpacks',
                    'subtitle' => 'Heavy Load',
                    'days' => 4,
                    'quantity' => 1,
                    'price_per_day' => 85000,
                    'subtotal' => 340000,
                    'image' => 'backpack.jpg',
                ]
            ]
        ]);

        // View Cart: Subtotal = 840,000, Service Fee = 25,000, Total = 865,000
        $cartResponse = $this->get('/cart');
        $cartResponse->assertStatus(200);
        $cartResponse->assertSee('Rp 840.000');
        $cartResponse->assertSee('Rp 25.000');
        $cartResponse->assertSee('Rp865.000');

        // Update days for item 1
        $updateResponse = $this->post('/cart/update/' . $product1->id, [
            'days' => 5,
        ]);
        $updateResponse->assertRedirect('/cart');
        $this->assertEquals(5, session('cart_items')[$product1->id]['days']);

        // Remove item 1
        $removeResponse = $this->post('/cart/remove/' . $product1->id);
        $removeResponse->assertRedirect('/cart');
        $this->assertArrayNotHasKey($product1->id, session('cart_items'));

        // Clear cart
        $clearResponse = $this->post('/cart/clear');
        $clearResponse->assertRedirect('/cart');

        // Check empty state
        $emptyCartResponse = $this->get('/cart');
        $emptyCartResponse->assertStatus(200);
        $emptyCartResponse->assertSee('Keranjang masih kosong');
        $emptyCartResponse->assertSee('Lihat Katalog');
    }
}
