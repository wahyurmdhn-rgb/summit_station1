<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_catalog_page(): void
    {
        $category = Category::create([
            'name' => 'Tents & Shelters',
            'slug' => 'tents-shelters',
        ]);

        Product::create([
            'category_id' => $category->id,
            'sku' => 'SS-TEN-001',
            'name' => 'Apex Predator 4P Expedition',
            'subtitle' => '4-Season Alpine Shelter',
            'price_per_day' => 125000,
            'stock_total' => 5,
            'stock_available' => 5,
            'is_active' => true,
        ]);

        $response = $this->get('/catalog');

        $response->assertStatus(200);
        $response->assertSee('Summit Station');
        $response->assertDontSee('Mountain Summit Package');
        $response->assertDontSee('4-Person Camping Package');
        $response->assertSee('Apex Predator 4P Expedition');
        $response->assertSee('Summit Protection');
    }
}
