<?php

namespace Tests\Feature;

use App\Models\Bundle;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::firstOrCreate(
            ['slug' => 'tents-shelters'],
            ['name' => 'Tents & Shelters']
        );

        for ($i = 0; $i < 12; $i++) {
            Product::create([
                'category_id' => $category->id,
                'sku' => 'DASH-PRODUCT-' . $i,
                'name' => 'Dashboard Product ' . $i,
                'price_per_day' => 10000,
                'stock_total' => 5,
                'stock_available' => 5,
                'is_active' => true,
            ]);
        }
    }

    public function test_admin_dashboard_total_produk_is_products_plus_bundles(): void
    {
        // 4 Paket Sewa (valid).
        for ($i = 0; $i < 4; $i++) {
            Bundle::create([
                'name' => 'Dashboard Pkg ' . $i,
                'description' => 'pkg',
                'price' => 100000,
                'is_active' => true,
            ]);
        }

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('ADMIN');
        $response->assertSee('Dashboard');
        $response->assertSee('TOTAL PENDAPATAN');
        $response->assertSee('Total Produk');
        // 12 products + 4 bundles = 16.
        $this->assertStringContainsString('<div class="stat-card-value">16</div>', $response->getContent(), 'Total Produk harus = products + bundles = 16');
        $response->assertSee('Total Pengguna');
        $response->assertSee('Sedang Disewa');
        $response->assertSee('Monitoring Peralatan Populer');
        // Popular gear bersumber dari database (produk yang ada di DB),
        // BUKAN nama dummy/hardcoded.
        $this->assertStringNotContainsString('The North Face VE 25', $response->getContent());
        $this->assertStringNotContainsString('Garmin Fenix 7X', $response->getContent());
        $response->assertSee('Dashboard Product');
    }

    public function test_admin_dashboard_total_produk_reflects_zero_bundles(): void
    {
        // Tidak ada bundle -> Total Produk = products saja = 12.
        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin');

        $response->assertStatus(200);
        $this->assertStringContainsString('<div class="stat-card-value">12</div>', $response->getContent(), 'Total Produk harus = products saja = 12');
    }

    public function test_admin_dashboard_total_produk_updates_when_bundle_added_and_deleted(): void
    {
        $adminSession = [
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ];

        $before = $this->withSession($adminSession)->get('/admin');
        $this->assertStringContainsString('<div class="stat-card-value">12</div>', $before->getContent());

        $bundle = Bundle::create([
            'name' => 'Count Pkg',
            'description' => 'pkg',
            'price' => 90000,
            'is_active' => true,
        ]);

        $added = $this->withSession($adminSession)->get('/admin');
        $this->assertStringContainsString('<div class="stat-card-value">13</div>', $added->getContent());

        $bundle->delete();

        $deleted = $this->withSession($adminSession)->get('/admin');
        $this->assertStringContainsString('<div class="stat-card-value">12</div>', $deleted->getContent());
    }
}
