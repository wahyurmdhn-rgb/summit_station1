<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_cart_page_with_items(): void
    {
        $user = User::create([
            'name' => 'Cart Member',
            'username' => 'cartmember',
            'email' => 'cart.member@summit.id',
            'password' => 'password',
        ]);
        $category = Category::create(['name' => 'Tents', 'slug' => 'tents']);
        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'SS-TEN-101',
            'name' => 'Summit Peak 4P Tent',
            'price_per_day' => 100000,
            'stock_total' => 5,
            'stock_available' => 5,
            'is_active' => true,
        ]);

        $response = $this->withSession([
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_role' => 'customer',
            'cart_items' => [
                $product->id => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => 'Tents',
                    'subtitle' => 'Expedition Shelter',
                    'days' => 4,
                    'quantity' => 1,
                    'price_per_day' => 100000,
                    'subtotal' => 400000,
                    'image' => 'tent.jpg',
                ]
            ]
        ])->get('/cart');

        $response->assertStatus(200);
        $response->assertSee('Pilihan Peralatan');
        $response->assertSee('PERSIAPAN EKSPEDISI ANDA');
        $response->assertSee('Summit Peak 4P Tent');
        $response->assertSee('Ringkasan Pesanan');
        $response->assertSee('Ajukan Peminjaman');
    }

    public function test_user_can_add_item_to_cart(): void
    {
        $user = User::create([
            'name' => 'Cart Member 2',
            'username' => 'cartmember2',
            'email' => 'cart.member2@summit.id',
            'password' => 'password',
        ]);
        $category = Category::create(['name' => 'Tents', 'slug' => 'tents']);
        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'SS-TEN-102',
            'name' => 'Apex Pro Tent',
            'price_per_day' => 120000,
            'stock_total' => 5,
            'stock_available' => 5,
            'is_active' => true,
        ]);

        $response = $this->withSession([
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_role' => 'customer',
        ])->post('/cart/add', [
            'product_id' => $product->id,
            'days' => 3,
            'quantity' => 1,
        ]);

        $response->assertRedirect('/cart');
    }
}
