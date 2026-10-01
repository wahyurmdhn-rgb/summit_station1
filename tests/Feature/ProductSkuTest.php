<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSkuTest extends TestCase
{
    use RefreshDatabase;

    private function adminSession(): array
    {
        return [
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ];
    }

    public function test_category_generates_deterministic_unique_sku(): void
    {
        $tenda = Category::create(['name' => 'Tenda', 'slug' => 'tenda']);
        $kursi = Category::create(['name' => 'Kursi', 'slug' => 'kursi']);
        $meja = Category::create(['name' => 'Meja', 'slug' => 'meja']);
        $sleepingBag = Category::create(['name' => 'Sleeping Bag', 'slug' => 'sleeping-bag']);
        $tendaCollision = Category::create(['name' => 'Tunda', 'slug' => 'tunda']);

        $this->assertSame('TND', $tenda->sku);
        $this->assertSame('KRS', $kursi->sku);
        $this->assertSame('MEJ', $meja->sku);
        $this->assertSame('SLB', $sleepingBag->sku);
        $this->assertSame('TND2', $tendaCollision->sku);
    }

    public function test_new_products_ignore_manual_sku_and_use_category_sequence(): void
    {
        $category = Category::create(['name' => 'Tenda', 'slug' => 'tenda']);

        $this->withSession($this->adminSession())->post('/admin/alat', [
            'name' => 'Tenda Dome',
            'sku' => 'MANUAL-999',
            'category_id' => $category->id,
            'price_per_day' => 100000,
            'stock_total' => 2,
            'stock_available' => 2,
        ])->assertRedirect('/admin/alat');

        $this->withSession($this->adminSession())->post('/admin/alat', [
            'name' => 'Tenda Camping',
            'sku' => 'MANUAL-998',
            'category_id' => $category->id,
            'price_per_day' => 120000,
            'stock_total' => 2,
            'stock_available' => 2,
        ])->assertRedirect('/admin/alat');

        $this->assertDatabaseHas('products', ['name' => 'Tenda Dome', 'sku' => 'TND-001']);
        $this->assertDatabaseHas('products', ['name' => 'Tenda Camping', 'sku' => 'TND-002']);
        $this->assertDatabaseMissing('products', ['sku' => 'MANUAL-999']);
    }

    public function test_changing_product_category_generates_new_sequence_and_same_category_keeps_sku(): void
    {
        $tenda = Category::create(['name' => 'Tenda', 'slug' => 'tenda']);
        $kursi = Category::create(['name' => 'Kursi', 'slug' => 'kursi']);

        $product = Product::create([
            'category_id' => $tenda->id,
            'sku' => 'TND-001',
            'name' => 'Produk Lama',
            'price_per_day' => 100000,
            'stock_total' => 1,
            'stock_available' => 1,
        ]);

        $this->withSession($this->adminSession())->put('/admin/alat/'.$product->id, [
            'name' => $product->name,
            'category_id' => $tenda->id,
            'price_per_day' => 100000,
            'stock_total' => 1,
            'stock_available' => 1,
        ])->assertRedirect('/admin/alat');

        $this->assertSame('TND-001', $product->fresh()->sku);

        $this->withSession($this->adminSession())->put('/admin/alat/'.$product->id, [
            'name' => $product->name,
            'sku' => 'ATTACKER-001',
            'category_id' => $kursi->id,
            'price_per_day' => 100000,
            'stock_total' => 1,
            'stock_available' => 1,
        ])->assertRedirect('/admin/alat');

        $this->assertSame('KRS-001', $product->fresh()->sku);
    }
}
