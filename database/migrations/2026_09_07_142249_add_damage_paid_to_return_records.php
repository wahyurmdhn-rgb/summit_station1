<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('return_records', function (Blueprint $table) {
            // Penanda denda pada pengembalian ini sudah dibayar/lunas oleh user.
            // Lunas = damage_cost > 0 && damage_paid_at !== null (status tersimpan di backend).
            $table->timestamp('damage_paid_at')->nullable()->after('damage_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('return_records', function (Blueprint $table) {
            $table->dropColumn('damage_paid_at');
        });
    }
};
