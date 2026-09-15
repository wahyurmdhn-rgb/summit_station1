<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom bundle_id pada tabel reviews agar ulasan paket/bundle
     * terhubung langsung ke bundel yang benar (bukan ke salah satu produk anggota).
     *
     * Sebelum migrasi ini, ulasan untuk paket disimpan sebagai product_id =
     * produk pertama dalam bundel. Akibatnya ulasan "bocor" ke bundel lain yang
     * juga berisi produk tersebut.
     */
    public function up(): void
    {
        if (! Schema::hasTable('reviews')) {
            return;
        }

        if (! Schema::hasColumn('reviews', 'bundle_id')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->foreignId('bundle_id')->nullable()->after('product_id')->constrained('bundles')->nullOnDelete();
            });
        }

        // Backfill: review lama yang merupakan ulasan paket/bundle.
        // Deteksi lewat order_items: bila order memiliki item bundel, ulasan tsb
        // seharusnya terhubung ke bundel (bundle_id).
        $rows = DB::table('reviews')
            ->whereNotNull('order_id')
            ->get(['id', 'product_id', 'order_id']);

        foreach ($rows as $row) {
            $item = DB::table('order_items')
                ->where('order_id', $row->order_id)
                ->whereNotNull('bundle_id')
                ->first();

            if ($item) {
                DB::table('reviews')->where('id', $row->id)->update([
                    'bundle_id' => $item->bundle_id,
                    'product_id' => null,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('reviews')) {
            return;
        }

        if (Schema::hasColumn('reviews', 'bundle_id')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->dropConstrainedForeignId('bundle_id');
            });
        }
    }
};