<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileRecentRentalTest extends TestCase
{
    use RefreshDatabase;

    private function sessionFor(User $user): array
    {
        return [
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_username' => $user->username,
            'account_role' => 'customer',
        ];
    }

    public function test_profile_shows_latest_rental_for_user_with_history(): void
    {
        $user = User::create([
            'name' => 'Rental Member',
            'username' => 'rentalmember',
            'email' => 'rental.member@summit.id',
            'password' => 'password',
        ]);
        $category = Category::create(['name' => 'Tents', 'slug' => 'tents']);
        $first = Product::create([
            'category_id' => $category->id,
            'sku' => 'SS-TEN-300',
            'name' => 'Older Tent',
            'price_per_day' => 90000,
            'stock_total' => 5,
            'stock_available' => 5,
            'is_active' => true,
        ]);
        $latest = Product::create([
            'category_id' => $category->id,
            'sku' => 'SS-TEN-301',
            'name' => 'Latest Expedition Tent',
            'price_per_day' => 110000,
            'stock_total' => 5,
            'stock_available' => 5,
            'is_active' => true,
        ]);

        $olderOrder = Order::create([
            'code' => 'RS-OLD-0001',
            'user_id' => $user->id,
            'rent_start' => now()->subDays(10)->startOfDay(),
            'rent_end' => now()->subDays(7)->endOfDay(),
            'subtotal' => 90000,
            'total' => 90000,
            'status' => 'completed',
            'created_at' => now()->subDays(2),
        ]);
        OrderItem::create([
            'order_id' => $olderOrder->id,
            'product_id' => $first->id,
            'name' => $first->name,
            'image' => $first->image ?? '',
            'quantity' => 1,
            'days' => 3,
            'unit_price' => 90000,
            'subtotal' => 90000,
        ]);

        $latestOrder = Order::create([
            'code' => 'RS-LAT-0002',
            'user_id' => $user->id,
            'rent_start' => now()->startOfDay(),
            'rent_end' => now()->addDays(2)->endOfDay(),
            'subtotal' => 110000,
            'total' => 110000,
            'status' => 'active',
            'created_at' => now(),
        ]);
        OrderItem::create([
            'order_id' => $latestOrder->id,
            'product_id' => $latest->id,
            'name' => $latest->name,
            'image' => $latest->image ?? '',
            'quantity' => 1,
            'days' => 2,
            'unit_price' => 110000,
            'subtotal' => 110000,
        ]);

        $response = $this->withSession($this->sessionFor($user))->get('/profile');

        $response->assertStatus(200);
        $response->assertSee('Penyewaan Terakhir');
        $response->assertSee($latest->name);
        $response->assertSee('#RS-LAT-0002');
        $response->assertDontSee('Rencanakan Ekspedisi Baru');
    }

    public function test_profile_shows_empty_state_for_user_without_history(): void
    {
        $user = User::create([
            'name' => 'Fresh Member',
            'username' => 'freshmember',
            'email' => 'fresh.member@summit.id',
            'password' => 'password',
        ]);

        $response = $this->withSession($this->sessionFor($user))->get('/profile');

        $response->assertStatus(200);
        $response->assertSee('Penyewaan Terakhir');
        $response->assertSee('Rencanakan Ekspedisi Baru');
    }
}
