<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'weight')) {
                $table->string('weight', 50)->nullable()->after('condition');
            }
            if (!Schema::hasColumn('products', 'capacity')) {
                $table->string('capacity', 100)->nullable()->after('weight');
            }
        });

        // Backfill weight and capacity from specs if available
        $products = DB::table('products')->whereNotNull('specs')->get();
        foreach ($products as $p) {
            $specs = json_decode($p->specs, true);
            if (is_array($specs)) {
                $weight = $specs['BERAT'] ?? $specs['berat'] ?? null;
                $capacity = $specs['KAPASITAS'] ?? $specs['kapasitas'] ?? null;
                if ($weight || $capacity) {
                    DB::table('products')->where('id', $p->id)->update([
                        'weight' => $weight,
                        'capacity' => $capacity,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('products', 'weight')) {
                $cols[] = 'weight';
            }
            if (Schema::hasColumn('products', 'capacity')) {
                $cols[] = 'capacity';
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
