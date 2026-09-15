<?php

namespace Tests\Smoke;

use App\Models\Bundle;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Reproduces the reported Admin → Alat bug on real MySQL:
 * creating a Paket Sewa (bundle) and attaching an existing product to it
 * must NOT hide that product from the Admin Alat product list.
 */
class AdminAlatBundleMysqlSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        parent::tearDown();
    }

    public function test_bundle_product_appears_in_admin_alat_after_attaching_to_bundle(): void
    {
        $this->assertSame('mysql', config('database.default'));

        $category = Category::create([
            'name' => 'Smoke Alat Cat ' . uniqid(),
            'slug' => 'smoke-alat-' . uniqid(),
        ]);

        // 1. Buat produk baru (barang yang nanti dimasukkan ke Paket Sewa).
        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'SMOKE-ALAT-' . strtoupper(substr(uniqid(), -6)),
            'name' => 'Smoke Bundle Component ' . substr(uniqid(), -4),
            'subtitle' => 'Part of Paket Sewa',
            'price_per_day' => 100000,
            'stock_total' => 5,
            'stock_available' => 5,
            'is_active' => true,
        ]);

        // 2. Buat Paket Sewa dan masukkan produk ke dalamnya.
        $bundle = Bundle::create([
            'name' => 'Smoke Paket Sewa ' . substr(uniqid(), -4),
            'description' => 'test',
            'price' => 200000,
            'is_active' => true,
        ]);
        DB::table('bundle_product')->insert([
            'bundle_id' => $bundle->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        // Pastikan relasi products <-> bundle tersimpan.
        $this->assertTrue(DB::table('bundle_product')
            ->where('bundle_id', $bundle->id)
            ->where('product_id', $product->id)
            ->exists());

        // 3. Buka /admin/alat dan pastikan produk anggota paket MUNCUL.
        // Since products and bundles now paginate together (10/page), search across pages.
        $page = 1;
        $found = false;
        $html = '';
        do {
            $response = $this->withSession([
                'account_id' => 1,
                'account_name' => 'Admin Summit',
                'account_role' => 'admin',
            ])->get('/admin/alat?page=' . $page);
            $response->assertStatus(200);
            $html .= $response->getContent();
            $found = $found || str_contains($html, $product->name);
            $lastPage = $response->viewData('products')->lastPage();
            $page++;
        } while (!$found && $page <= $lastPage && $page <= 20);

        $response->assertStatus(200);

        fwrite(STDERR, 'ADMIN ALAT HTTP ' . $response->getStatusCode()
            . ' bundle_product_id=' . $product->id
            . ' sku=' . $product->sku
            . ' name=' . $product->name
            . ' present_in_page=' . (str_contains($html, $product->name) ? 'YES' : 'NO') . "\n");

        $this->assertStringContainsString($product->name, $html, 'Bundle component product must appear on /admin/alat');
        $this->assertStringNotContainsString('Tidak ada data alat', $html);
    }
}
