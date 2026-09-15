<?php

namespace Tests\Feature;

use App\Models\Bundle;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartQuantityLimitTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Limit Tester',
            'username' => 'limittester',
            'email' => 'limit.tester@summit.id',
            'password' => 'password',
        ]);

        $category = Category::create(['name' => 'Tents', 'slug' => 'tents']);
        $this->product = Product::create([
            'category_id' => $category->id,
            'sku' => 'SS-LIM-5',
            'name' => 'Limit Product (Max 5)',
            'price_per_day' => 100000,
            'stock_total' => 5,
            'stock_available' => 5,
            'is_active' => true,
        ]);
    }

    private function sessionData(array $cart): array
    {
        return [
            'account_id' => $this->user->id,
            'account_name' => $this->user->name,
            'account_role' => 'customer',
            'cart_items' => $cart,
        ];
    }

    private function cartItem(int $quantity = 1, int $stock = 5): array
    {
        return [
            'id' => $this->product->id,
            'name' => $this->product->name,
            'category' => 'Tents',
            'subtitle' => 'Expedition Shelter',
            'is_bundle' => false,
            'days' => 1,
            'quantity' => $quantity,
            'stock_available' => $stock,
            'price_per_day' => 100000,
            'subtotal' => 100000 * $quantity,
            'image' => 'tent.jpg',
        ];
    }

    public function test_cart_page_shows_max_quantity_label(): void
    {
        $cart = [$this->product->id => $this->cartItem()];
        $response = $this->withSession($this->sessionData($cart))->get('/cart');
        $response->assertStatus(200);
        $response->assertSee('Jumlah Unit (MAKS: 5)');
    }

    public function test_cart_can_increase_quantity_up_to_max(): void
    {
        $cart = [$this->product->id => $this->cartItem(2, 5)];
        $response = $this
            ->withSession($this->sessionData($cart))
            ->json('POST', '/cart/update/' . $this->product->id, ['days' => 1, 'quantity' => 5]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertSame(5, $response->json('item.quantity'));
        // subtotal = 100000 * 1 hari * 5 unit
        $this->assertSame(500000, $response->json('item.subtotal'));
        $this->assertSame('Rp 500.000', $response->json('formattedItemSubtotal'));
    }

    public function test_backend_rejects_quantity_above_max(): void
    {
        $cart = [$this->product->id => $this->cartItem(1, 5)];
        $response = $this
            ->withSession($this->sessionData($cart))
            ->json('POST', '/cart/update/' . $this->product->id, ['days' => 1, 'quantity' => 6]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $response->assertJsonFragment(['message' => 'Jumlah unit maksimal 5.']);

        // Cart should remain unchanged (quantity still 1)
        $updatedCart = session('cart_items');
        $this->assertSame(1, $updatedCart[$this->product->id]['quantity']);
    }

    public function test_backend_rejects_quantity_below_minimum(): void
    {
        $cart = [$this->product->id => $this->cartItem(1, 5)];
        $response = $this
            ->withSession($this->sessionData($cart))
            ->json('POST', '/cart/update/' . $this->product->id, ['days' => 1, 'quantity' => 0]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertSame(1, $cart[$this->product->id]['quantity']);
    }

    public function test_backend_uses_each_products_own_max(): void
    {
        $category = Category::create(['name' => 'Sleep', 'slug' => 'sleep']);
        $productB = Product::create([
            'category_id' => $category->id,
            'sku' => 'SS-LIM-3',
            'name' => 'Limit Product B (Max 3)',
            'price_per_day' => 50000,
            'stock_total' => 3,
            'stock_available' => 3,
            'is_active' => true,
        ]);
        $cart = [
            $this->product->id => $this->cartItem(1, 5),
            $productB->id => [
                'id' => $productB->id,
                'name' => $productB->name,
                'category' => 'Sleep',
                'subtitle' => 'Bag',
                'is_bundle' => false,
                'days' => 1,
                'quantity' => 1,
                'stock_available' => 3,
                'price_per_day' => 50000,
                'subtotal' => 50000,
                'image' => 'bag.jpg',
            ],
        ];

        // Product B max = 3, so 4 must be rejected
        $res = $this
            ->withSession($this->sessionData($cart))
            ->json('POST', '/cart/update/' . $productB->id, ['days' => 1, 'quantity' => 4]);
        $res->assertStatus(422);
        $res->assertJsonFragment(['message' => 'Jumlah unit maksimal 3.']);

        // Product B at its max (3) must be accepted
        $ok = $this
            ->withSession($this->sessionData($cart))
            ->json('POST', '/cart/update/' . $productB->id, ['days' => 1, 'quantity' => 3]);
        $ok->assertOk();
        $this->assertSame(3, $ok->json('item.quantity'));
    }

    public function test_summary_updates_with_quantity_change(): void
    {
        $cart = [$this->product->id => $this->cartItem(1, 5)];
        $response = $this
            ->withSession($this->sessionData($cart))
            ->json('POST', '/cart/update/' . $this->product->id, ['days' => 1, 'quantity' => 4]);

        $response->assertOk();
        // subtotal rentals = 100000 * 1 * 4 = 400000, service fee 25000, total = 425000
        $this->assertSame(400000, $response->json('subtotalRentals'));
        $this->assertSame(25000, $response->json('serviceFee'));
        $this->assertSame(425000, $response->json('totalPayable'));
        $this->assertSame('Rp400.000', str_replace('Rp ', 'Rp', $response->json('formattedSubtotalRentals')));
    }

    public function test_bundle_quantity_above_max_is_rejected_on_add(): void
    {
        $bundle = Bundle::create([
            'name' => 'bundle limit ' . uniqid(),
            'description' => 'test',
            'price' => 100000,
            'is_active' => true,
        ]);

        $response = $this->withSession([
            'account_id' => $this->user->id,
            'account_name' => $this->user->name,
            'account_role' => 'customer',
        ])->post('/cart/add-bundle', [
            'bundle_id' => $bundle->id,
            'days' => 1,
            'quantity' => 6,
        ]);

        $response->assertSessionHasErrors('error');
        $this->assertEmpty(session('cart_items'));
    }
}
