<?php

namespace Tests\Feature;

use App\Models\Bundle;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAlatTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::firstOrCreate(
            ['slug' => 'tents-shelters'],
            ['name' => 'Tents & Shelters']
        );

        Product::firstOrCreate(
            ['sku' => 'TENT-001-OR'],
            [
                'category_id' => $category->id,
                'name' => 'Apex Ultralight V2',
                'subtitle' => '4-Season Expedition Tent',
                'description' => 'Lightweight alpine tent',
                'price_per_day' => 250000,
                'stock_total' => 15,
                'stock_available' => 12,
                'is_active' => true,
            ]
        );
    }

    public function test_admin_can_access_alat_page(): void
    {
        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin/alat');

        $response->assertStatus(200);
        $response->assertSee('Kelola Produk');
        $response->assertSee('KONTROL INVENTARIS');
        $response->assertSee('TOTAL NILAI');
        $response->assertSee('TAMBAH PRODUK');
        $response->assertSee('Total SKU');
        $response->assertSee('Peringatan Stok Menipis');
        $response->assertSee('Kategori Produk');
        $response->assertSee('Apex Ultralight V2');
        $response->assertSee('TENT-001-OR');
    }

    public function test_customer_cannot_access_alat_page(): void
    {
        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Customer Summit',
            'account_role' => 'customer',
        ])->get('/admin/alat');

        $response->assertStatus(403);
    }

    public function test_guest_is_redirected_when_accessing_alat_page(): void
    {
        $response = $this->get('/admin/alat');

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_store_new_alat(): void
    {
        $category = Category::first();

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->post('/admin/alat', [
            'name' => 'Solar Camp Lantern',
            'sku' => 'LANTERN-999',
            'category_id' => $category->id,
            'price_per_day' => 35000,
            'stock_total' => 20,
            'stock_available' => 20,
            'is_active' => 1,
        ]);

        $response->assertRedirect('/admin/alat');
        $this->assertDatabaseHas('products', ['sku' => 'LANTERN-999', 'name' => 'Solar Camp Lantern']);
    }

    public function test_admin_can_update_alat(): void
    {
        $product = Product::first();

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->put('/admin/alat/' . $product->id, [
            'name' => 'Apex Ultralight V2 Pro Edit',
            'sku' => $product->sku,
            'category_id' => $product->category_id,
            'price_per_day' => 275000,
            'stock_total' => 18,
            'stock_available' => 15,
            'is_active' => 1,
        ]);

        $response->assertRedirect('/admin/alat');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Apex Ultralight V2 Pro Edit', 'price_per_day' => 275000]);
    }

    public function test_admin_can_toggle_alat_status(): void
    {
        $product = Product::first();
        $initialStatus = $product->is_active;

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->patch('/admin/alat/' . $product->id . '/toggle-status');

        $response->assertRedirect('/admin/alat');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => ! $initialStatus]);
    }

    public function test_admin_can_delete_alat(): void
    {
        $category = Category::first();
        $productToDelete = Product::create([
            'name' => 'Product To Delete',
            'sku' => 'DEL-999',
            'category_id' => $category->id,
            'price_per_day' => 10000,
            'stock_total' => 1,
            'stock_available' => 1,
        ]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->delete('/admin/alat/' . $productToDelete->id);

        $response->assertRedirect('/admin/alat');
        $this->assertDatabaseMissing('products', ['id' => $productToDelete->id]);
    }

    public function test_bundle_component_products_appear_in_admin_alat_list(): void
    {
        $category = Category::first();

        // Produk yang akan dipakai sebagai komponen Paket Sewa.
        $bundleProduct = Product::create([
            'category_id' => $category->id,
            'sku' => 'BUNDLE-COMP-001',
            'name' => 'Bundle Component Tent',
            'subtitle' => 'Part of a rental package',
            'price_per_day' => 200000,
            'stock_total' => 5,
            'stock_available' => 3,
            'is_active' => true,
        ]);

        // Produk yang TIDAK dipakai dalam paket (kontrol).
        $standaloneProduct = Product::create([
            'category_id' => $category->id,
            'sku' => 'STANDALONE-001',
            'name' => 'Standalone Lamp',
            'subtitle' => 'Not part of any package',
            'price_per_day' => 15000,
            'stock_total' => 10,
            'stock_available' => 8,
            'is_active' => true,
        ]);

        // Buat Paket Sewa dan lampirkan produk komponennya.
        Bundle::create(['name' => 'Test Package', 'description' => 'pkg', 'price' => 300000, 'is_active' => true])
            ->products()->sync([$bundleProduct->id => ['quantity' => 1]]);

        // Pastikan relasi produk <-> paket tetap utuh.
        $this->assertTrue($bundleProduct->bundles()->exists());
        $this->assertEquals(1, $bundleProduct->bundles()->first()->pivot->quantity);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin/alat');

        $response->assertStatus(200);
        // Produk anggota paket HARUS muncul, bukan disembunyikan.
        $response->assertSee('Bundle Component Tent');
        $response->assertSee('BUNDLE-COMP-001');
        // Produk non-paket juga ikut muncul.
        $response->assertSee('Standalone Lamp');
        $response->assertSee('STANDALONE-001');

        // Tidak ada duplikasi data produk di database (satu baris untuk satu produk).
        $this->assertSame(1, Product::where('sku', 'BUNDLE-COMP-001')->count());
        $this->assertSame(1, Product::where('sku', 'STANDALONE-001')->count());

        // Paket Sewa (bundle) itu sendiri juga muncul sebagai satu baris di daftar kelola,
        // bersama produk-produknya, tanpa duplikasi.
        $response->assertSee('Test Package');
        $response->assertSee('PKT-');
        $response->assertSee('Paket Sewa');

        // Total pagination gabungan (produk + paket) = setUp() (1) + 2 produk + 1 paket = 4.
        $response->assertSee('dari 4 Item', false);
    }

    public function test_bundle_component_product_found_in_search(): void
    {
        $category = Category::first();
        $bundleProduct = Product::create([
            'category_id' => $category->id,
            'sku' => 'BUNDLE-COMP-002',
            'name' => 'Package Stove',
            'subtitle' => 'Included in cooking package',
            'price_per_day' => 90000,
            'stock_total' => 4,
            'stock_available' => 2,
            'is_active' => true,
        ]);
        Product::create([
            'category_id' => $category->id,
            'sku' => 'OTHER-001',
            'name' => 'Random Flashlight',
            'price_per_day' => 20000,
            'stock_total' => 5,
            'stock_available' => 5,
            'is_active' => true,
        ]);
        Bundle::create(['name' => 'Cook Package', 'description' => 'p', 'price' => 200000, 'is_active' => true])
            ->products()->sync([$bundleProduct->id => ['quantity' => 1]]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin/alat?search=Package+Stove');

        $response->assertStatus(200);
        $response->assertSee('Package Stove');
        $response->assertSee('BUNDLE-COMP-002');
        // Pencarian hanya mengembalikan 1 baris (produk "Package Stove").
        // Catatan: seluruh produk tetap muncul di checklist anggota modal Edit Paket,
        // sehingga cek filter memakai jumlah hasil, bukan absence teks di seluruh halaman.
        $response->assertSee('dari 1 Item', false);
    }

    public function test_bundle_component_product_found_in_category_filter(): void
    {
        $tentCat = Category::firstOrCreate(['slug' => 'tents-shelters'], ['name' => 'Tents & Shelters']);
        $packsCat = Category::firstOrCreate(['slug' => 'backpacks'], ['name' => 'Backpacks']);

        $bundleProduct = Product::create([
            'category_id' => $packsCat->id,
            'sku' => 'BUNDLE-COMP-003',
            'name' => 'Package Backpack',
            'subtitle' => 'In a bundle',
            'price_per_day' => 120000,
            'stock_total' => 6,
            'stock_available' => 4,
            'is_active' => true,
        ]);
        Bundle::create(['name' => 'Backpack Pkg', 'description' => 'p', 'price' => 150000, 'is_active' => true])
            ->products()->sync([$bundleProduct->id => ['quantity' => 1]]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin/alat?category=backpacks');

        $response->assertStatus(200);
        $response->assertSee('Package Backpack');
        $response->assertSee('BUNDLE-COMP-003');

        // "Semua Kategori" juga harus memuatnya.
        $allRes = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin/alat?category=all');
        $allRes->assertStatus(200);
        $allRes->assertSee('Package Backpack');
    }

    public function test_admin_alat_pagination_total_includes_bundle_products(): void
    {
        $category = Category::first();

        // 3 produk polos + 1 produk komponen paket = 4 produk, cukup untuk 2 halaman (10/halaman)
        // Pastikan produk komponen paket terhitung dalam total pagination.
        for ($i = 0; $i < 3; $i++) {
            Product::create([
                'category_id' => $category->id,
                'sku' => "PLAIN-{$i}",
                'name' => "Plain Product {$i}",
                'price_per_day' => 10000,
                'stock_total' => 2,
                'stock_available' => 2,
                'is_active' => true,
            ]);
        }

        $bundleProduct = Product::create([
            'category_id' => $category->id,
            'sku' => 'BUNDLE-COMP-004',
            'name' => 'Bundle Component Pillow',
            'subtitle' => 'pkg',
            'price_per_day' => 50000,
            'stock_total' => 3,
            'stock_available' => 3,
            'is_active' => true,
        ]);
        Bundle::create(['name' => 'Pillow Pkg', 'description' => 'p', 'price' => 90000, 'is_active' => true])
            ->products()->sync([$bundleProduct->id => ['quantity' => 1]]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin/alat');

        $response->assertStatus(200);
        // Total gabungan (produk + paket): setUp() (1) + 3 polos + 1 komponen paket + 1 paket = 6.
        $response->assertSee('dari 6 Item', false);
    }

    public function test_bundle_row_shows_in_admin_alat_with_stock_and_status(): void
    {
        $category = Category::first();

        $comp = Product::create([
            'category_id' => $category->id,
            'sku' => 'BUNDLE-COMP-005',
            'name' => 'Bundle Component Mattress',
            'price_per_day' => 30000,
            'stock_total' => 4,
            'stock_available' => 3,
            'is_active' => true,
        ]);
        $bundle = Bundle::create([
            'name' => 'Royal Camping Pkg',
            'description' => 'pkg',
            'price' => 250000,
            'is_active' => true,
        ]);
        $bundle->products()->sync([$comp->id => ['quantity' => 1]]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin/alat');

        $response->assertStatus(200);
        $response->assertSee('Royal Camping Pkg');
        $response->assertSee('PKT-' . $bundle->id);
        // Paket yang stoknya > 0 harus tampil sebagai AKTIF.
        $response->assertSee('AKTIF');
        // Total gabungan: setUp() (1) + 1 produk + 1 paket = 3.
        $response->assertSee('dari 3 Item', false);
    }

    public function test_bundle_with_zero_stock_still_shows_but_as_habis(): void
    {
        $category = Category::first();

        // Komponen stoknya 0 -> availableStock() = 0 -> paket HABIS.
        $comp = Product::create([
            'category_id' => $category->id,
            'sku' => 'BUNDLE-COMP-006',
            'name' => 'Empty Stock Comp',
            'price_per_day' => 20000,
            'stock_total' => 2,
            'stock_available' => 0,
            'is_active' => true,
        ]);
        $bundle = Bundle::create([
            'name' => 'Out Of Stock Pkg',
            'description' => 'pkg',
            'price' => 120000,
            'is_active' => true,
        ]);
        $bundle->products()->sync([$comp->id => ['quantity' => 1]]);

        $this->assertEquals(0, $bundle->fresh()->availableStock());

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin/alat');

        $response->assertStatus(200);
        // Paket HABIS tetap MUNCUL di halaman admin (tidak disembunyikan).
        $response->assertSee('Out Of Stock Pkg');
        $response->assertSee('HABIS');
    }

    public function test_paket_sewa_filter_shows_only_bundles(): void
    {
        $category = Category::first();

        Product::create([
            'category_id' => $category->id,
            'sku' => 'NORMAL-SOLO-001',
            'name' => 'Solo Rope Gear',
            'price_per_day' => 50000,
            'stock_total' => 5,
            'stock_available' => 5,
            'is_active' => true,
        ]);

        $comp = Product::create([
            'category_id' => $category->id,
            'sku' => 'BUNDLE-COMP-007',
            'name' => 'Bundle Comp Rope',
            'price_per_day' => 40000,
            'stock_total' => 4,
            'stock_available' => 3,
            'is_active' => true,
        ]);
        $bundle = Bundle::create([
            'name' => 'Rope Pkg',
            'description' => 'pkg',
            'price' => 150000,
            'is_active' => true,
        ]);
        $bundle->products()->sync([$comp->id => ['quantity' => 1]]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin/alat?category=paket-sewa');

        $response->assertStatus(200);
        $response->assertSee('Rope Pkg');
        // Catatan: "Solo Rope Gear" (produk) tetap muncul di checklist anggota modal Edit Paket,
        // jadi filter di-verifikasi lewat jumlah baris hasil, bukan absence teks di seluruh halaman.
        $response->assertSee('dari 1 Item', false);
    }

    public function test_admin_can_toggle_bundle_status(): void
    {
        $bundle = Bundle::create([
            'name' => 'Toggle Pkg',
            'description' => 'pkg',
            'price' => 100000,
            'is_active' => true,
        ]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->patch('/admin/alat/bundle/' . $bundle->id . '/toggle-status');

        $response->assertRedirect();
        $this->assertFalse($bundle->fresh()->is_active);
    }

    public function test_admin_can_delete_bundle(): void
    {
        $bundle = Bundle::create([
            'name' => 'Delete Pkg',
            'description' => 'pkg',
            'price' => 80000,
            'is_active' => true,
        ]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->delete('/admin/alat/bundle/' . $bundle->id);

        $response->assertRedirect();
        $this->assertDatabaseMissing('bundles', ['id' => $bundle->id]);
    }

    public function test_bundle_category_badge_is_not_doubled(): void
    {
        $cat = Category::firstOrCreate(['slug' => 'backpacks'], ['name' => 'Backpacks']);
        $prod = Product::create([
            'category_id' => $cat->id,
            'sku' => 'BADGE-PROD',
            'name' => 'Badge Prod',
            'price_per_day' => 10000,
            'stock_total' => 3,
            'stock_available' => 3,
            'is_active' => true,
        ]);
        Bundle::create(['name' => 'Badge Pkg', 'description' => 'p', 'price' => 100000, 'is_active' => true])
            ->products()->sync([$prod->id => ['quantity' => 1]]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->get('/admin/alat?category=paket-sewa');

        $response->assertStatus(200);
        $html = $response->getContent();
        // Kategori "Paket Sewa" harus tampil tepat satu kali untuk baris paket,
        // TIDAK berupa "Paket Sewa · Paket Sewa".
        $this->assertStringNotContainsString('Paket Sewa · Paket Sewa', $html);
        $this->assertStringContainsString('Badge Pkg', $html);
    }

    public function test_admin_can_update_bundle_and_sync_members(): void
    {
        $cat = Category::first();
        $p1 = Product::create([
            'category_id' => $cat->id,
            'sku' => 'UPD-001',
            'name' => 'Update Comp 1',
            'price_per_day' => 10000,
            'stock_total' => 5,
            'stock_available' => 5,
            'is_active' => true,
        ]);
        $p2 = Product::create([
            'category_id' => $cat->id,
            'sku' => 'UPD-002',
            'name' => 'Update Comp 2',
            'price_per_day' => 20000,
            'stock_total' => 5,
            'stock_available' => 5,
            'is_active' => true,
        ]);
        $bundle = Bundle::create([
            'name' => 'Update Pkg',
            'description' => 'old desc',
            'price' => 100000,
            'image' => 'https://old.test/img.png',
            'is_active' => true,
        ]);
        $bundle->products()->sync([$p1->id => ['quantity' => 1]]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->put('/admin/alat/bundle/' . $bundle->id, [
            'name' => 'Update Pkg (Edited)',
            'description' => 'new desc',
            'price' => 150000,
            'image' => '',
            'is_active' => '1',
            'member_ids' => [$p1->id, $p2->id, $p2->id],
            'quantities' => [$p1->id => 2, $p2->id => 3],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $fresh = $bundle->fresh();
        $this->assertEquals('Update Pkg (Edited)', $fresh->name);
        $this->assertEquals(150000, $fresh->price);
        $this->assertEquals('new desc', $fresh->description);
        $this->assertTrue($fresh->is_active);
        // Gambar kosong -> gambar lama dipertahankan.
        $this->assertEquals('https://old.test/img.png', $fresh->image);

        // Anggota tersinkron: p1 (qty 2) dan p2 (qty 3), tanpa duplikasi p2.
        $this->assertEquals(2, $fresh->products()->count());
        $q1 = $fresh->products()->where('products.id', $p1->id)->first()->pivot->quantity;
        $q2 = $fresh->products()->where('products.id', $p2->id)->first()->pivot->quantity;
        $this->assertEquals(2, $q1);
        $this->assertEquals(3, $q2);
    }

    public function test_update_bundle_replaces_image_when_provided(): void
    {
        $bundle = Bundle::create([
            'name' => 'Image Pkg',
            'description' => 'p',
            'price' => 90000,
            'image' => 'https://old.test/a.png',
            'is_active' => true,
        ]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->put('/admin/alat/bundle/' . $bundle->id, [
            'name' => 'Image Pkg',
            'price' => 90000,
            'image' => 'https://new.test/b.png',
        ]);

        $response->assertRedirect();
        $this->assertEquals('https://new.test/b.png', $bundle->fresh()->image);
    }

    public function test_update_bundle_validates_required_fields(): void
    {
        $bundle = Bundle::create([
            'name' => 'Validated Pkg',
            'description' => 'p',
            'price' => 90000,
            'is_active' => true,
        ]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->put('/admin/alat/bundle/' . $bundle->id, [
            'name' => '',
            'price' => -5,
        ]);

        $response->assertSessionHasErrors(['name', 'price']);
        // Data lama tetap aman saat validasi gagal.
        $this->assertEquals('Validated Pkg', $bundle->fresh()->name);
        $this->assertEquals(90000, $bundle->fresh()->price);
    }

    public function test_update_bundle_rejects_duplicate_name(): void
    {
        Bundle::create(['name' => 'Existing Pkg', 'description' => 'p', 'price' => 100000, 'is_active' => true]);
        $bundle = Bundle::create(['name' => 'Keep Me', 'description' => 'p', 'price' => 80000, 'is_active' => true]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->put('/admin/alat/bundle/' . $bundle->id, [
            'name' => 'Existing Pkg',
            'price' => 80000,
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertEquals('Keep Me', $bundle->fresh()->name);
    }

    public function test_admin_can_store_new_bundle(): void
    {
        $cat = Category::first();
        $p1 = Product::create([
            'category_id' => $cat->id,
            'sku' => 'CREATE-001',
            'name' => 'Create Comp 1',
            'price_per_day' => 10000,
            'stock_total' => 5,
            'stock_available' => 5,
            'is_active' => true,
        ]);
        $p2 = Product::create([
            'category_id' => $cat->id,
            'sku' => 'CREATE-002',
            'name' => 'Create Comp 2',
            'price_per_day' => 20000,
            'stock_total' => 3,
            'stock_available' => 3,
            'is_active' => true,
        ]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->post('/admin/alat/bundle', [
            'name' => 'Brand New Pkg',
            'description' => 'fresh package',
            'price' => 120000,
            'image' => 'https://img.test/pkg.png',
            'is_active' => '1',
            'member_ids' => [$p1->id, $p2->id],
            'quantities' => [$p1->id => 2, $p2->id => 1],
        ]);

        $response->assertRedirect('/admin/alat');
        $response->assertSessionHasNoErrors();

        $bundle = Bundle::where('name', 'Brand New Pkg')->first();
        $this->assertNotNull($bundle);
        $this->assertEquals(120000, $bundle->price);
        $this->assertEquals('fresh package', $bundle->description);
        $this->assertEquals('https://img.test/pkg.png', $bundle->image);
        $this->assertTrue($bundle->is_active);
        $this->assertEquals(2, $bundle->products()->count());
        $this->assertEquals(2, $bundle->products()->where('products.id', $p1->id)->first()->pivot->quantity);
        $this->assertEquals(1, $bundle->products()->where('products.id', $p2->id)->first()->pivot->quantity);
    }

    public function test_store_bundle_validates_required_fields(): void
    {
        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->post('/admin/alat/bundle', [
            'name' => '',
            'price' => -5,
        ]);

        $response->assertSessionHasErrors(['name', 'price']);
        $this->assertSame(0, Bundle::count());
    }

    public function test_store_bundle_rejects_duplicate_name(): void
    {
        Bundle::create(['name' => 'Existing Pkg', 'description' => 'p', 'price' => 100000, 'is_active' => true]);

        $response = $this->withSession([
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ])->post('/admin/alat/bundle', [
            'name' => 'Existing Pkg',
            'price' => 80000,
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame(1, Bundle::count());
    }
}
