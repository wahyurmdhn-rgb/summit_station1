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

class MinorConsentRentalBlockTest extends TestCase
{
    use RefreshDatabase;

    private function makeMinor(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Daffa Pratama',
            'username' => 'daffa_minor',
            'email' => 'daffa.minor@summit.test',
            'password' => 'password123',
            'date_of_birth' => now()->subYears(16)->format('Y-m-d'),
            'parent_consent_status' => 'submitted',
            'status' => 'active',
            'role' => 'customer',
        ], $overrides));
    }

    private function makeProduct(): Product
    {
        $category = Category::create(['name' => 'Tents', 'slug' => 'tents']);

        return Product::create([
            'category_id' => $category->id,
            'sku' => 'SS-MNR-001',
            'name' => 'Minor Block Tent',
            'price_per_day' => 125000,
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

    public function test_minor_with_unverified_consent_cannot_add_product_to_cart(): void
    {
        $user = $this->makeMinor();
        $product = $this->makeProduct();

        $response = $this->withSession($this->customerSession($user))
            ->post('/cart/add', [
                'product_id' => $product->id,
                'days' => 3,
                'quantity' => 1,
            ]);

        $response->assertSessionHasErrors('error');
        $this->assertTrue($user->is_consent_pending);
        $this->assertNull(session('cart_items'));
    }

    public function test_minor_with_unverified_consent_cannot_access_payment_page(): void
    {
        $user = $this->makeMinor();
        $product = $this->makeProduct();

        $response = $this->withSession($this->customerSession($user) + [
            'cart_items' => [
                $product->id => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'days' => 3,
                    'quantity' => 1,
                    'price_per_day' => 125000,
                    'subtotal' => 375000,
                    'image' => 'tent.jpg',
                ]
            ]
        ])->get('/payment');

        $response->assertSessionHasErrors('error');
    }

    public function test_minor_with_unverified_consent_cannot_process_payment(): void
    {
        $user = $this->makeMinor();
        $product = $this->makeProduct();
        Storage::fake('public');

        $response = $this->withSession($this->customerSession($user) + [
            'payment_deadline' => time() + 300,
            'cart_items' => [
                $product->id => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'days' => 3,
                    'quantity' => 1,
                    'price_per_day' => 125000,
                    'subtotal' => 375000,
                    'image' => 'tent.jpg',
                ]
            ]
        ])->post('/payment/process', [
            'payment_method' => 'qris',
            'proof' => UploadedFile::fake()->create('proof.jpg', 100),
        ]);

        $response->assertSessionHasErrors('error');
        $this->assertSame(0, Order::where('user_id', $user->id)->count());
    }

    public function test_minor_with_verified_consent_can_add_to_cart_and_access_payment(): void
    {
        $user = $this->makeMinor(['parent_consent_status' => 'verified']);
        $product = $this->makeProduct();

        $addResponse = $this->withSession($this->customerSession($user))
            ->post('/cart/add', [
                'product_id' => $product->id,
                'days' => 3,
                'quantity' => 1,
            ]);

        $addResponse->assertRedirect('/cart');
        $this->assertNotEmpty(session('cart_items'));

        $paymentResponse = $this->withSession($this->customerSession($user))
            ->get('/payment');

        $paymentResponse->assertStatus(200);
        $paymentResponse->assertSessionHasNoErrors();
    }

    public function test_adult_without_parent_consent_still_can_rent(): void
    {
        $user = User::create([
            'name' => 'Budi Dewasa',
            'username' => 'budi_adult',
            'email' => 'budi.adult@summit.test',
            'password' => 'password123',
            'date_of_birth' => '1995-01-01',
            'status' => 'active',
            'role' => 'customer',
        ]);
        $product = $this->makeProduct();

        $response = $this->withSession($this->customerSession($user))
            ->post('/cart/add', [
                'product_id' => $product->id,
                'days' => 3,
                'quantity' => 1,
            ]);

        $response->assertRedirect('/cart');
        $this->assertNotEmpty(session('cart_items'));
    }

    public function test_product_detail_shows_consent_pending_block_for_minor(): void
    {
        $user = $this->makeMinor();
        $product = $this->makeProduct();

        $response = $this->withSession($this->customerSession($user))
            ->get('/catalog/' . $product->id);

        $response->assertStatus(200);
        $response->assertSee('Persetujuan orang tua');
        $response->assertDontSee('Tambah ke Keranjang');
    }
}