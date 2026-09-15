<?php

namespace Tests\Feature;

use App\Models\Bundle;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BundleStockAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(string $sku, int $stock, string $name): Product
    {
        $category = Category::firstOrCreate(['name' => 'Tents', 'slug' => 'tents']);

        return Product::create([
            'category_id' => $category->id,
            'sku' => $sku,
            'name' => $name,
            'price_per_day' => 50000,
            'stock_total' => max(1, $stock),
            'stock_available' => max(0, $stock),
            'is_active' => true,
        ]);
    }

    private function makeBundle(string $name, array $items): Bundle
    {
        $bundle = Bundle::create([
            'name' => $name,
            'description' => 'test bundle',
            'price' => 100000,
            'is_active' => true,
        ]);

        $sync = [];
        foreach ($items as $item) {
            $sync[$item[0]->id] = ['quantity' => $item[1]];
        }
        $bundle->products()->sync($sync);

        return $bundle;
    }

    private function customerSession(User $user): array
    {
        return [
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_role' => 'customer',
        ];
    }

    /**
     * Paket: Tenda stok 5 (kebutuhan 1) -> 5, Sleeping Bag stok 4 (kebutuhan 2) -> 2,
     *        Backpack stok 5 (kebutuhan 2) -> 2  => MIN = 2
     */
    public function test_available_stock_equals_floor_of_limiting_component(): void
    {
        $tenda = $this->makeProduct('T-1', 5, 'Tenda');
        $sleep = $this->makeProduct('S-1', 4, 'Sleeping Bag');
        $backpack = $this->makeProduct('B-1', 5, 'Backpack');

        $bundle = $this->makeBundle('Paket A', [
            [$tenda, 1],
            [$sleep, 2],
            [$backpack, 2],
        ]);

        $this->assertSame(2, $bundle->availableStock());
        $this->assertTrue($bundle->isAvailable());
    }

    public function test_available_stock_is_zero_when_component_has_zero_stock(): void
    {
        $tenda = $this->makeProduct('T-2', 5, 'Tenda');
        $sleep = $this->makeProduct('S-2', 0, 'Sleeping Bag');
        $backpack = $this->makeProduct('B-2', 5, 'Backpack');

        $bundle = $this->makeBundle('Paket A', [
            [$tenda, 1],
            [$sleep, 2],
            [$backpack, 2],
        ]);

        $this->assertSame(0, $bundle->availableStock());
        $this->assertFalse($bundle->isAvailable());
    }

    public function test_detail_page_shows_habis_and_disables_booking_when_component_out_of_stock(): void
    {
        $user = User::create([
            'name' => 'Detail Tester',
            'username' => 'detailtester',
            'email' => 'detail.tester@summit.id',
            'password' => 'password',
        ]);

        $tenda = $this->makeProduct('T-3', 5, 'Tenda');
        $sleep = $this->makeProduct('S-3', 0, 'North Face Inferno -20F');
        $backpack = $this->makeProduct('B-3', 5, 'Backpack');

        $bundle = $this->makeBundle('Mountain Summit Package', [
            [$tenda, 1],
            [$sleep, 2],
            [$backpack, 2],
        ]);

        $response = $this->withSession($this->customerSession($user))
            ->get(route('catalog.bundle', $bundle->id));
        $response->assertStatus(200);
        $response->assertSee('STOK HABIS', false);
        $response->assertSee('Stok Habis', false);
        $response->assertDontSee('TERSEDIA (STOK:');
        $response->assertDontSee('Booking Paket Sewa');
    }

    public function test_catalog_and_detail_are_consistent_for_out_of_stock_bundle(): void
    {
        $tenda = $this->makeProduct('T-4', 5, 'Tenda');
        $sleep = $this->makeProduct('S-4', 0, 'Sleeping Bag');
        $backpack = $this->makeProduct('B-4', 5, 'Backpack');

        $bundle = $this->makeBundle('Paket Habis', [
            [$tenda, 1],
            [$sleep, 2],
            [$backpack, 2],
        ]);

        // Katalog: paket menampilkan status HABIS dan tombol "Stok Habis" yang
        // disabled (tidak ada link "Booking" aktif untuk paket ini).
        $catalog = $this->get('/catalog');
        $catalog->assertStatus(200);
        $catalog->assertSee('Paket Habis');
        $catalog->assertSee('HABIS', false);
        $catalog->assertSee('Stok Habis', false);
        $catalog->assertDontSee(sprintf('href="%s"', route('catalog.bundle', $bundle->id)));

        // Detail: STOK HABIS, bukan TERSEDIA.
        $detail = $this->get(route('catalog.bundle', $bundle->id));
        $detail->assertStatus(200);
        $detail->assertSee('STOK HABIS', false);
        $detail->assertDontSee('TERSEDIA (STOK:');
    }

    public function test_detail_page_shows_available_stock_when_all_components_in_stock(): void
    {
        $tenda = $this->makeProduct('T-5', 5, 'Tenda');
        $other = $this->makeProduct('O-5', 2, 'Lainnya');

        $bundle = $this->makeBundle('Paket Tersedia', [
            [$tenda, 1],
            [$other, 1],
        ]);

        $this->assertSame(2, $bundle->availableStock());

        $detail = $this->get(route('catalog.bundle', $bundle->id));
        $detail->assertStatus(200);
        $detail->assertSee('TERSEDIA (STOK: 2)', false);
    }

    public function test_catalog_shows_active_booking_link_when_bundle_in_stock(): void
    {
        $tenda = $this->makeProduct('T-9', 5, 'Tenda');
        $other = $this->makeProduct('O-9', 2, 'Lainnya');

        $bundle = $this->makeBundle('Paket Tersedia', [
            [$tenda, 1],
            [$other, 1],
        ]);

        $catalog = $this->get('/catalog');
        $catalog->assertStatus(200);
        $catalog->assertSee('Paket Tersedia');
        $catalog->assertSee('STOK: 2', false);
        $catalog->assertSee(sprintf('href="%s"', route('catalog.bundle', $bundle->id)), false);
        $catalog->assertDontSee('Paket sedang habis', false);
    }

    public function test_add_bundle_rejects_when_available_stock_is_zero(): void
    {
        $user = User::create([
            'name' => 'Bundle Tester',
            'username' => 'bundletester',
            'email' => 'bundle.tester@summit.id',
            'password' => 'password',
        ]);

        $tenda = $this->makeProduct('T-6', 5, 'Tenda');
        $sleep = $this->makeProduct('S-6', 0, 'Sleeping Bag');
        $backpack = $this->makeProduct('B-6', 5, 'Backpack');

        $bundle = $this->makeBundle('Paket Habis', [
            [$tenda, 1],
            [$sleep, 2],
            [$backpack, 2],
        ]);

        $response = $this->withSession($this->customerSession($user))
            ->post('/cart/add-bundle', ['bundle_id' => $bundle->id, 'days' => 1, 'quantity' => 1]);

        $response->assertSessionHasErrors('error');
        $this->assertEmpty(session('cart_items'));
    }

    public function test_payment_index_rejects_out_of_stock_bundle_in_cart(): void
    {
        $user = User::create([
            'name' => 'Payment Tester',
            'username' => 'paymenttester',
            'email' => 'payment.tester@summit.id',
            'password' => 'password',
        ]);

        $tenda = $this->makeProduct('T-7', 5, 'Tenda');
        $sleep = $this->makeProduct('S-7', 0, 'Sleeping Bag');
        $backpack = $this->makeProduct('B-7', 5, 'Backpack');

        $bundle = $this->makeBundle('Paket Habis', [
            [$tenda, 1],
            [$sleep, 2],
            [$backpack, 2],
        ]);

        $cartKey = 'bundle_' . $bundle->id;
        $cart = [
            $cartKey => [
                'id' => $cartKey,
                'bundle_id' => $bundle->id,
                'is_bundle' => true,
                'name' => $bundle->name,
                'days' => 1,
                'quantity' => 1,
                'price_per_day' => $bundle->price,
                'subtotal' => $bundle->price,
                'selected' => true,
            ],
        ];

        $response = $this->withSession([...$this->customerSession($user), 'cart_items' => $cart])
            ->get('/payment');

        $response->assertRedirect(route('cart'));
        $response->assertSessionHasErrors('error');
    }

    public function test_payment_process_does_not_create_order_for_out_of_stock_bundle(): void
    {
        $user = User::create([
            'name' => 'Process Tester',
            'username' => 'processtester',
            'email' => 'process.tester@summit.id',
            'password' => 'password',
        ]);

        $tenda = $this->makeProduct('T-8', 5, 'Tenda');
        $sleep = $this->makeProduct('S-8', 0, 'Sleeping Bag');
        $backpack = $this->makeProduct('B-8', 5, 'Backpack');

        $bundle = $this->makeBundle('Paket Habis', [
            [$tenda, 1],
            [$sleep, 2],
            [$backpack, 2],
        ]);

        $cartKey = 'bundle_' . $bundle->id;
        $cart = [
            $cartKey => [
                'id' => $cartKey,
                'bundle_id' => $bundle->id,
                'is_bundle' => true,
                'name' => $bundle->name,
                'days' => 1,
                'quantity' => 1,
                'price_per_day' => $bundle->price,
                'subtotal' => $bundle->price,
                'selected' => true,
            ],
        ];

        $before = Order::count();

        $response = $this->withSession([
            ...$this->customerSession($user),
            'cart_items' => $cart,
            'payment_deadline' => time() + 300,
        ])->post('/payment/process', [
            'payment_method' => 'qris',
            'proof' => UploadedFile::fake()->create('proof.jpg', 100),
        ]);

        $response->assertSessionHasErrors();
        $this->assertSame($before, Order::count());
    }
}
