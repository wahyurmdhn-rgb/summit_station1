<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDetailPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_product_detail_page(): void
    {
        $user = User::create([
            'name' => 'Detail Member',
            'username' => 'detailmember',
            'email' => 'detail.member@summit.id',
            'password' => 'password',
        ]);

        $category = Category::create([
            'name' => 'Tents & Shelters',
            'slug' => 'tents-shelters',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'SS-TEN-001',
            'name' => 'Apex Sentinel',
            'subtitle' => '4-Season Expedition Tent',
            'price_per_day' => 125000,
            'stock_total' => 10,
            'stock_available' => 10,
            'grade' => 'PRO-GRADE',
            'specs' => [
                'WEIGHT' => '3.2kg',
                'CAPACITY' => '2 Person',
                'CONDITION' => 'Excellent',
                'SEASON' => '4-Season',
            ],
            'is_active' => true,
        ]);

        $response = $this->withSession([
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_role' => 'customer',
        ])->get('/catalog/' . $product->id);

        $response->assertStatus(200);
        $response->assertSee('Apex Sentinel');
        $response->assertSee('4-Season Expedition Tent');
        $response->assertSee('PRO-GRADE');
        $response->assertSee('TERSEDIA');
        $response->assertSee('WEIGHT');
        $response->assertSee('3.2kg');
        $response->assertSee('CAPACITY');
        $response->assertSee('2 Person');
        $response->assertSee('CONDITION');
        $response->assertSee('Excellent');
        $response->assertSee('125.000');
        $response->assertSee('Tambah ke Keranjang');
        $response->assertSee('Weather Resistance');
        $response->assertSee('Structural Integrity');
        $response->assertSee('Active Ventilation');
    }
}
