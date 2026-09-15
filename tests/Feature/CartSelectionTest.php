<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartSelectionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;
    private Product $product2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Selection Tester',
            'username' => 'selectiontester',
            'email' => 'selection.tester@summit.id',
            'password' => 'password',
        ]);

        $category = Category::create(['name' => 'Tents', 'slug' => 'tents']);

        $this->product = Product::create([
            'category_id' => $category->id,
            'sku' => 'SS-SEL-01',
            'name' => 'Selection Tent A',
            'price_per_day' => 100000,
            'stock_total' => 5,
            'stock_available' => 5,
            'is_active' => true,
        ]);

        $this->product2 = Product::create([
            'category_id' => $category->id,
            'sku' => 'SS-SEL-02',
            'name' => 'Selection Tent B',
            'price_per_day' => 50000,
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

    private function cartItem(Product $product, int $days = 1, int $quantity = 1, int $subtotal = null, ?bool $withSelected = null): array
    {
        $subtotal = $subtotal ?? $product->price_per_day * $days * $quantity;
        $item = [
            'id' => $product->id,
            'name' => $product->name,
            'category' => 'Tents',
            'subtitle' => 'Expedition',
            'days' => $days,
            'quantity' => $quantity,
            'price_per_day' => $product->price_per_day,
            'subtotal' => $subtotal,
            'image' => 'tent.jpg',
        ];

        if ($withSelected === true) {
            $item['selected'] = true;
        } elseif ($withSelected === false) {
            $item['selected'] = false;
        }

        return $item;
    }

    public function test_all_items_selected_by_default_when_flag_missing(): void
    {
        $cart = [
            $this->product->id => $this->cartItem($this->product),
            $this->product2->id => $this->cartItem($this->product2),
        ];

        $response = $this->withSession($this->sessionData($cart))->get('/cart');

        $response->assertStatus(200);
        $response->assertSee('2 dari 2 barang dipilih');
        $response->assertSee('is-selected');
    }

    public function test_unselecting_one_item_updates_totals(): void
    {
        $cart = [
            $this->product->id => $this->cartItem($this->product),
            $this->product2->id => $this->cartItem($this->product2),
        ];

        $response = $this->withSession($this->sessionData($cart))
            ->postJson('/cart/select', [
                'id' => (string) $this->product->id,
                'checked' => false,
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'selectedCount' => 1,
            'totalItems' => 2,
            'subtotalRentals' => 50000,
            'serviceFee' => 25000,
            'totalPayable' => 75000,
        ]);
    }

    public function test_unselecting_all_items_zeroes_totals(): void
    {
        $cart = [
            $this->product->id => $this->cartItem($this->product),
            $this->product2->id => $this->cartItem($this->product2),
        ];

        $this->withSession($this->sessionData($cart))
            ->postJson('/cart/select', [
                'id' => (string) $this->product->id,
                'checked' => false,
            ])
            ->assertOk()
            ->assertJson(['selectedCount' => 1]);

        $this->postJson('/cart/select', [
            'id' => (string) $this->product2->id,
            'checked' => false,
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'selectedCount' => 0,
                'totalItems' => 2,
                'subtotalRentals' => 0,
                'serviceFee' => 0,
                'totalPayable' => 0,
            ]);
    }

    public function test_select_all_and_unselect_all_mode(): void
    {
        $cart = [
            $this->product->id => $this->cartItem($this->product, withSelected: false),
            $this->product2->id => $this->cartItem($this->product2, withSelected: false),
        ];

        $this->withSession($this->sessionData($cart))
            ->postJson('/cart/select', ['all' => true, 'checked' => true])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'selectedCount' => 2,
                'subtotalRentals' => 150000,
                'totalPayable' => 175000,
            ]);

        $unselect = $this->withSession($this->sessionData($cart))
            ->postJson('/cart/select', ['all' => true, 'checked' => false])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'selectedCount' => 0,
                'subtotalRentals' => 0,
                'serviceFee' => 0,
                'totalPayable' => 0,
            ]);
    }

    public function test_select_all_persists_checked_item(): void
    {
        $cart = [
            $this->product->id => $this->cartItem($this->product, withSelected: true),
            $this->product2->id => $this->cartItem($this->product2, withSelected: false),
        ];

        $this->withSession($this->sessionData($cart))
            ->postJson('/cart/select', ['all' => true, 'checked' => false])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'selectedCount' => 0,
            ]);
    }

    public function test_select_invalid_item_returns_error(): void
    {
        $cart = [
            $this->product->id => $this->cartItem($this->product),
        ];

        $this->withSession($this->sessionData($cart))
            ->postJson('/cart/select', [
                'id' => '999999',
                'checked' => false,
            ])
            ->assertStatus(422);
    }

    public function test_payment_redirects_when_no_item_selected(): void
    {
        $cart = [
            $this->product->id => $this->cartItem($this->product, withSelected: false),
            $this->product2->id => $this->cartItem($this->product2, withSelected: false),
        ];

        $this->withSession($this->sessionData($cart))
            ->get('/payment')
            ->assertRedirect(route('cart'))
            ->assertSessionHasErrors('error');
    }

    public function test_payment_only_processes_selected_items(): void
    {
        $cart = [
            $this->product->id => $this->cartItem($this->product, withSelected: true),
            $this->product2->id => $this->cartItem($this->product2, withSelected: false),
        ];

        $response = $this->withSession($this->sessionData($cart))->get('/payment');
        $response->assertStatus(200);
        $response->assertSee($this->product->name);
        $response->assertDontSee('Selection Tent B');
    }
}
