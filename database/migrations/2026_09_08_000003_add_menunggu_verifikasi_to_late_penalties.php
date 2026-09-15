<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('late_penalties', function (Blueprint $table) {
            $table->enum('status', [
                'tidak_ada_sanksi',
                'belum_diproses',
                'menunggu_pembayaran',
                'menunggu_verifikasi',
                'sudah_dibayar',
                'dibatalkan',
            ])->default('belum_diproses')->change();
        });
    }

    public function down(): void
    {
        Schema::table('late_penalties', function (Blueprint $table) {
            $table->enum('status', [
                'tidak_ada_sanksi',
                'belum_diproses',
                'menunggu_pembayaran',
                'sudah_dibayar',
                'dibatalkan',
            ])->default('belum_diproses')->change();
        });
    }
};