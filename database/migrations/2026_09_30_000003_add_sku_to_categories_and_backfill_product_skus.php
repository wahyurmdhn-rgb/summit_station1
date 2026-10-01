<?php

use App\Models\Category;
use App\Models\Product;
use App\Services\SkuService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('sku', 32)->nullable()->after('slug');
        });

        DB::transaction(function () {
            Category::query()->orderBy('id')->lockForUpdate()->each(function (Category $category) {
                $category->sku = SkuService::generateCategorySku($category->name, $category->id);
                $category->save();
            });

            // Existing products.sku is unique. Assign temporary values first so
            // final deterministic codes cannot collide with an old manual SKU.
            Product::query()->orderBy('id')->lockForUpdate()->each(function (Product $product) {
                $product->sku = 'LEGACY-'.$product->id;
                $product->save();
            });

            Category::query()->orderBy('id')->lockForUpdate()->each(function (Category $category) {
                $sequence = 1;

                Product::query()
                    ->where('category_id', $category->id)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->each(function (Product $product) use ($category, &$sequence) {
                        $product->sku = sprintf('%s-%03d', $category->sku, $sequence);
                        $product->save();
                        $sequence++;
                    });
            });
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->unique('sku');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('sku', 32)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['sku']);
            $table->dropColumn('sku');
        });
    }
};
