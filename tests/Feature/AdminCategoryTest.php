<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryTest extends TestCase
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

    public function test_guest_cannot_access_category_endpoints(): void
    {
        $this->post('/admin/alat/category', ['name' => 'X'])->assertRedirect(route('admin.login'));
        $this->put('/admin/alat/category/1', ['name' => 'X'])->assertRedirect(route('admin.login'));
        $this->delete('/admin/alat/category/1')->assertRedirect(route('admin.login'));
    }

    public function test_customer_cannot_access_category_endpoints(): void
    {
        $this->withSession([
            'account_id' => 1,
            'account_name' => 'Customer',
            'account_role' => 'customer',
        ])->post('/admin/alat/category', ['name' => 'X'])->assertStatus(403);
    }

    public function test_admin_can_add_category(): void
    {
        $response = $this->withSession($this->adminSession())
            ->post('/admin/alat/category', ['name' => 'Apparel']);

        $response->assertRedirect('/admin/alat');
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('categories', ['name' => 'Apparel', 'slug' => 'apparel']);
    }

    public function test_add_category_requires_unique_name(): void
    {
        Category::create(['name' => 'Lighting', 'slug' => 'lighting']);

        $response = $this->withSession($this->adminSession())
            ->post('/admin/alat/category', ['name' => 'Lighting']);

        $response->assertSessionHasErrors('name');
        $this->assertSame(1, Category::where('name', 'Lighting')->count());
    }

    public function test_add_category_requires_name(): void
    {
        $response = $this->withSession($this->adminSession())
            ->post('/admin/alat/category', ['name' => '']);

        $response->assertSessionHasErrors('name');
        $this->assertSame(0, Category::count());
    }

    public function test_admin_can_edit_category(): void
    {
        $cat = Category::create(['name' => 'Old Name', 'slug' => 'old-name']);

        $response = $this->withSession($this->adminSession())
            ->put('/admin/alat/category/' . $cat->id, ['name' => 'New Name']);

        $response->assertRedirect('/admin/alat');
        $fresh = $cat->fresh();
        $this->assertEquals('New Name', $fresh->name);
        $this->assertEquals('new-name', $fresh->slug);
    }

    public function test_edit_category_rejects_duplicate_name_against_others(): void
    {
        Category::create(['name' => 'Taken', 'slug' => 'taken']);
        $cat = Category::create(['name' => 'Editable', 'slug' => 'editable']);

        $response = $this->withSession($this->adminSession())
            ->put('/admin/alat/category/' . $cat->id, ['name' => 'Taken']);

        $response->assertSessionHasErrors('name');
        $this->assertEquals('Editable', $cat->fresh()->name);
    }

    public function test_edit_category_allows_keeping_same_name(): void
    {
        $cat = Category::create(['name' => 'Same', 'slug' => 'same']);

        $response = $this->withSession($this->adminSession())
            ->put('/admin/alat/category/' . $cat->id, ['name' => 'Same']);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('Same', $cat->fresh()->name);
    }

    public function test_admin_can_delete_unused_category(): void
    {
        $cat = Category::create(['name' => 'Unused', 'slug' => 'unused']);

        $response = $this->withSession($this->adminSession())
            ->delete('/admin/alat/category/' . $cat->id);

        $response->assertRedirect('/admin/alat');
        $this->assertDatabaseMissing('categories', ['id' => $cat->id]);
    }

    public function test_category_in_use_cannot_be_deleted(): void
    {
        $cat = Category::create(['name' => 'InUse', 'slug' => 'in-use']);
        Product::create([
            'category_id' => $cat->id,
            'sku' => 'CAT-INUSE-001',
            'name' => 'Used Product',
            'price_per_day' => 10000,
            'stock_total' => 3,
            'stock_available' => 3,
            'is_active' => true,
        ]);

        $response = $this->withSession($this->adminSession())
            ->delete('/admin/alat/category/' . $cat->id);

        $response->assertRedirect('/admin/alat');
        $response->assertSessionHasErrors('category');
        $this->assertDatabaseHas('categories', ['id' => $cat->id]);
        $this->assertDatabaseHas('products', ['sku' => 'CAT-INUSE-001']);
    }

    public function test_new_category_is_available_for_new_product(): void
    {
        $response = $this->withSession($this->adminSession())
            ->post('/admin/alat/category', ['name' => 'Apparel']);

        $cat = Category::where('name', 'Apparel')->first();
        $this->assertNotNull($cat);

        $store = $this->withSession($this->adminSession())
            ->post('/admin/alat', [
                'name' => 'Rain Jacket',
                'sku' => 'APPRL-001',
                'category_id' => $cat->id,
                'price_per_day' => 45000,
                'stock_total' => 10,
                'stock_available' => 8,
                'is_active' => 1,
            ]);

        $store->assertRedirect('/admin/alat');
        $this->assertDatabaseHas('products', ['sku' => 'APPRL-001', 'category_id' => $cat->id]);
    }

    public function test_category_change_reflected_on_product_and_admin_alat(): void
    {
        $cat = Category::create(['name' => 'Sleeping', 'slug' => 'sleeping']);
        Product::create([
            'category_id' => $cat->id,
            'sku' => 'REFL-001',
            'name' => 'Reflect Product',
            'price_per_day' => 10000,
            'stock_total' => 2,
            'stock_available' => 2,
            'is_active' => true,
        ]);

        $this->withSession($this->adminSession())
            ->put('/admin/alat/category/' . $cat->id, ['name' => 'Slumber Gear']);

        $this->assertDatabaseHas('categories', ['id' => $cat->id, 'name' => 'Slumber Gear']);

        $response = $this->withSession($this->adminSession())->get('/admin/alat');
        $response->assertStatus(200);
        $response->assertSee('Slumber Gear');
    }

    public function test_user_catalog_uses_real_category_data_for_filter(): void
    {
        $apparel = Category::create(['name' => 'Apparel', 'slug' => 'apparel']);
        $other = Category::create(['name' => 'Hardware', 'slug' => 'hardware']);
        Product::create([
            'category_id' => $apparel->id,
            'sku' => 'CAT-FILTER-001',
            'name' => 'Filterable Jacket',
            'price_per_day' => 50000,
            'stock_total' => 4,
            'stock_available' => 4,
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $other->id,
            'sku' => 'CAT-FILTER-002',
            'name' => 'Non Filterable',
            'price_per_day' => 30000,
            'stock_total' => 4,
            'stock_available' => 4,
            'is_active' => true,
        ]);

        $response = $this->get('/catalog?category[]=apparel');
        $response->assertStatus(200);
        $response->assertSee('Filterable Jacket');

        $allRes = $this->get('/catalog');
        $allRes->assertStatus(200);
        $allRes->assertSee('Non Filterable');
    }
}
