<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hapus batasan legacy agar satu pengguna dapat memberi rating pada
     * setiap pesanan yang berbeda (bukan terbatas satu rating per produk).
     *
     * Schema lama (2026_08_24_000007) membuat UNIQUE(product_id, user_id)
     * yang masih tersisa di produksi. Karena sistem rating sekarang mengunci
     * "1 pesanan = 1 rating" lewat order_id, batasan lama itu menyebabkan
     * error duplicate-key saat pelanggan merating produk yang sama pada
     * pesanan berikutnya.
     */
    public function up(): void
    {
        if (! Schema::hasTable('reviews')) {
            return;
        }

        Schema::table('reviews', function (Blueprint $table) {
            $index = 'reviews_product_id_user_id_unique';
            $existing = collect(Schema::getIndexes('reviews'));
            $uniqueExists = $existing->first(fn ($i) => $i['name'] === $index || str_ends_with((string) $i['name'], $index));

            if ($uniqueExists) {
                // MySQL mewajibkan index pendukung untuk foreign key product_id.
                // Composite unique lama menjadi satu-satunya index untuk FK tersebut,
                // sehingga harus dibuat index product_id tersendiri lebih dulu.
                $plainIndex = 'reviews_product_id_index';
                $hasPlain = $existing->first(fn ($i) => $i['name'] === $plainIndex);
                if (! $hasPlain) {
                    $table->index('product_id', $plainIndex);
                }

                // 1. Lepas index unik legacy (product_id + user_id).
                $table->dropUnique($index);
            }
        });

        Schema::table('reviews', function (Blueprint $table) {
            // 2. product_id boleh NULL (pesanan paket/bundle).
            if (Schema::hasColumn('reviews', 'product_id') && ! $this->columnIsNullable('product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->change();
            }

            // 3. Pastikan satu pesanan hanya punya satu rating (DB-level),
            //    selaras dengan validasi anti-rating-ganda di controller.
            $orderIndex = 'reviews_order_id_unique';
            $orderExists = collect(Schema::getIndexes('reviews'))->first(fn ($i) => $i['name'] === $orderIndex || str_ends_with((string) $i['name'], $orderIndex));
            if (! $orderExists && Schema::hasColumn('reviews', 'order_id')) {
                $table->unique('order_id', $orderIndex);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('reviews')) {
            return;
        }

        Schema::table('reviews', function (Blueprint $table) {
            if (Schema::hasColumn('reviews', 'order_id')) {
                $table->dropUnique('reviews_order_id_unique');
            }
        });
    }

    private function columnIsNullable(string $column): bool
    {
        $columns = Schema::getColumns('reviews');
        $col = collect($columns)->firstWhere('name', $column);

        return $col['nullable'] ?? false;
    }
};
