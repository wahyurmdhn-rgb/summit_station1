<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_payment_page(): void
    {
        $user = User::create([
            'name' => 'Payment Member',
            'username' => 'paymentmember',
            'email' => 'payment.member@summit.id',
            'password' => 'password',
        ]);
        $category = Category::create(['name' => 'Tents', 'slug' => 'tents']);
        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'SS-TEN-001',
            'name' => 'Summit Series X1',
            'price_per_day' => 125000,
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
                    'subtitle' => 'Expedition',
                    'days' => 4,
                    'quantity' => 1,
                    'price_per_day' => 125000,
                    'subtotal' => 500000,
                    'image' => 'tent.jpg',
                ]
            ]
        ])->get('/payment');

        $response->assertStatus(200);
        $response->assertSee('Konfirmasi');
        $response->assertSee('Bayar');
        $response->assertSee('DETAIL TRANSAKSI');
        $response->assertSee('Summit Series X1');
        $response->assertSee('Biaya Sewa Dasar');
        $response->assertSee('Biaya Layanan');
        $response->assertSee('Pilih Metode Pembayaran');
        $response->assertSee('QRIS');
        $response->assertSee('GOPAY');
        $response->assertSee('DANA');
        $response->assertSee('OVO');
        $response->assertSee('SHOPEEPAY');
    }

    public function test_cart_links_to_payment(): void
    {
        $user = User::create([
            'name' => 'Payment Member 2',
            'username' => 'paymentmember2',
            'email' => 'payment.member2@summit.id',
            'password' => 'password',
        ]);
        $category = Category::create(['name' => 'Tents', 'slug' => 'tents']);
        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'SS-TEN-002',
            'name' => 'Alpine Shield',
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
                    'subtitle' => 'Expedition',
                    'days' => 3,
                    'quantity' => 1,
                    'price_per_day' => 100000,
                    'subtotal' => 300000,
                    'image' => 'tent.jpg',
                ]
            ]
        ])->get('/cart');

        $response->assertStatus(200);
        $response->assertSee('Ajukan Peminjaman');
        $response->assertSee(route('payment'), false);
    }
}
