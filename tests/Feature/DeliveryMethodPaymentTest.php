<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeliveryMethodPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Delivery Tester',
            'username' => 'delivery_' . substr(uniqid(), -6),
            'email' => 'delivery' . uniqid() . '@summit.id',
            'password' => 'password',
        ]);
    }

    private function makeProduct(): Product
    {
        $category = Category::create(['name' => 'Tents', 'slug' => 'tents' . rand(100, 999)]);
        return Product::create([
            'category_id' => $category->id,
            'sku' => 'SS-DEL-' . rand(1000, 9999),
            'name' => 'Summit Delivery X',
            'price_per_day' => 125000,
            'stock_total' => 5,
            'stock_available' => 5,
            'is_active' => true,
        ]);
    }

    private function sessionFor(User $user, Product $product, array $extra = []): array
    {
        return array_merge([
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
                ],
            ],
        ], $extra);
    }

    public function test_payment_page_shows_delivery_method_section(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();

        $response = $this->withSession($this->sessionFor($user, $product))->get('/payment');

        $response->assertStatus(200);
        $response->assertSee('Metode Pengambilan');
        $response->assertSee('Bagaimana Anda ingin menerima perlengkapan rental?');
        $response->assertSee('Ambil di Tempat');
        $response->assertSee('Dikirim ke Lokasi');
        $response->assertSee('recipient_name');
        $response->assertSee('recipient_phone');
        $response->assertSee('delivery_address');
        $response->assertSee('delivery_note');
        $response->assertSee('Summit Station');
    }

    public function test_pickup_flow_saves_delivery_method_pickup_without_address(): void
    {
        Storage::fake('public');
        $user = $this->makeUser();
        $product = $this->makeProduct();

        $response = $this->withSession($this->sessionFor($user, $product, ['payment_deadline' => time() + 300]))
            ->post('/payment/process', [
                'payment_method' => 'qris',
                'delivery_method' => 'pickup',
                'proof' => UploadedFile::fake()->create('proof.jpg', 100),
            ]);

        $response->assertRedirect('/history');

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals('pickup', $order->delivery_method);
        $this->assertEquals('pending', $order->status);
        $this->assertNull($order->recipient_name);
        $this->assertNull($order->recipient_phone);
        $this->assertNull($order->delivery_address);
        $this->assertNull($order->delivery_note);
    }

    public function test_delivery_flow_saves_delivery_method_and_address(): void
    {
        Storage::fake('public');
        $user = $this->makeUser();
        $product = $this->makeProduct();

        $response = $this->withSession($this->sessionFor($user, $product, ['payment_deadline' => time() + 300]))
            ->post('/payment/process', [
                'payment_method' => 'qris',
                'delivery_method' => 'delivery',
                'recipient_name' => 'Budi Santoso',
                'recipient_phone' => '081234567890',
                'delivery_address' => 'Jl. Merapi No. 10, Magelang',
                'delivery_note' => 'Patokan: dekat SD Merapi',
                'proof' => UploadedFile::fake()->create('proof.jpg', 100),
            ]);

        $response->assertRedirect('/history');

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals('delivery', $order->delivery_method);
        $this->assertEquals('Budi Santoso', $order->recipient_name);
        $this->assertEquals('081234567890', $order->recipient_phone);
        $this->assertEquals('Jl. Merapi No. 10, Magelang', $order->delivery_address);
        $this->assertEquals('Patokan: dekat SD Merapi', $order->delivery_note);

        // Total tetap sesuai sistem (tidak ada biaya kirim).
        $this->assertEquals(500000, $order->subtotal);
        $this->assertEquals(25000, $order->service_fee);
        $this->assertEquals(525000, $order->total);
    }

    public function test_delivery_with_empty_address_is_rejected(): void
    {
        Storage::fake('public');
        $user = $this->makeUser();
        $product = $this->makeProduct();

        $this->withSession($this->sessionFor($user, $product, ['payment_deadline' => time() + 300]))
            ->post('/payment/process', [
                'payment_method' => 'qris',
                'delivery_method' => 'delivery',
                'recipient_name' => '',
                'recipient_phone' => '',
                'delivery_address' => '',
                'proof' => UploadedFile::fake()->create('proof.jpg', 100),
            ])
            ->assertSessionHasErrors('delivery_address');

        $this->assertSame(0, Order::where('user_id', $user->id)->count());
        $this->assertArrayHasKey($product->id, session('cart_items', []));
    }

    public function test_delivery_with_invalid_phone_is_rejected(): void
    {
        Storage::fake('public');
        $user = $this->makeUser();
        $product = $this->makeProduct();

        $this->withSession($this->sessionFor($user, $product, ['payment_deadline' => time() + 300]))
            ->post('/payment/process', [
                'payment_method' => 'qris',
                'delivery_method' => 'delivery',
                'recipient_name' => 'Budi Santoso',
                'recipient_phone' => '08123',
                'delivery_address' => 'Jl. Merapi No. 10, Magelang',
                'proof' => UploadedFile::fake()->create('proof.jpg', 100),
            ])
            ->assertSessionHasErrors('delivery_address');

        $this->assertSame(0, Order::where('user_id', $user->id)->count());
    }

    public function test_payment_qris_post_persists_delivery_and_prefills_payment_page(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();

        $response = $this->withSession($this->sessionFor($user, $product))
            ->post('/payment/qris', [
                'payment_method' => 'qris',
                'delivery_method' => 'delivery',
                'recipient_name' => 'Rina Rahayu',
                'recipient_phone' => '085234567891',
                'delivery_address' => 'Jl. Rinjani No. 3, Malang',
                'delivery_note' => 'Patokan gerbang putih',
            ]);

        $response->assertStatus(200);
        $response->assertSee('Dikirim ke Lokasi');
        $response->assertSee('Jl. Rinjani No. 3, Malang');

        $this->assertSame('delivery', session('checkout_delivery.delivery_method'));

        // Kembali ke halaman pembayaran: pilihan & alamat ter-prefill.
        $paymentPage = $this->withSession($this->sessionFor($user, $product))->get('/payment');
        $paymentPage->assertStatus(200);
        $paymentPage->assertSee('Rina Rahayu');
        $paymentPage->assertSee('Jl. Rinjani No. 3, Malang');
    }

    public function test_switching_from_delivery_back_to_pickup_updates_session(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();

        $this->withSession($this->sessionFor($user, $product))->post('/payment/qris', [
            'payment_method' => 'qris',
            'delivery_method' => 'delivery',
            'recipient_name' => 'Ana',
            'recipient_phone' => '081277778888',
            'delivery_address' => 'Jl. Cendrawasih 2',
        ])->assertStatus(200);

        $this->assertSame('delivery', session('checkout_delivery.delivery_method'));

        $this->withSession($this->sessionFor($user, $product))->post('/payment/qris', [
            'payment_method' => 'qris',
            'delivery_method' => 'pickup',
        ])->assertStatus(200);

        $this->assertSame('pickup', session('checkout_delivery.delivery_method'));
        $this->assertSame('', session('checkout_delivery.delivery_address'));
    }

    public function test_delivery_qris_page_requires_complete_fields_on_post(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();

        $this->withSession($this->sessionFor($user, $product))->post('/payment/qris', [
            'payment_method' => 'qris',
            'delivery_method' => 'delivery',
            'recipient_name' => '',
            'recipient_phone' => '',
            'delivery_address' => '',
        ])->assertRedirect(route('payment'))
          ->assertSessionHasErrors('delivery_method');
    }

    public function test_qris_page_get_still_works_and_defaults_to_pickup(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();

        $response = $this->withSession($this->sessionFor($user, $product))
            ->get('/payment/qris?method=qris');

        $response->assertStatus(200);
        $response->assertSee('Metode Pengambilan');
        $response->assertSee('Ambil di Tempat');
    }
}