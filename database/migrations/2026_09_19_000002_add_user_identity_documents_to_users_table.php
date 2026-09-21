<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dokumen identitas berdasarkan umur:
 *   - >= 17 tahun : ktp_user_path          (KTP user sendiri)
 *   - <  17 tahun : ktp_orang_tua_path     (wajib, KTP orang tua/wali)
 *   - <  17 tahun : kartu_pelajar_path     (wajib, kartu pelajar)
 *
 * Kolom lama `ktp_path` (milik user sendiri) dipindahkan ke `ktp_user_path`
 * agar setiap dokumen tersimpan di field yang jelas dan tidak tertukar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'ktp_user_path')) {
                $table->string('ktp_user_path')->nullable()->after('ktp_path');
            }
            if (! Schema::hasColumn('users', 'ktp_orang_tua_path')) {
                $table->string('ktp_orang_tua_path')->nullable()->after('ktp_user_path');
            }
            if (! Schema::hasColumn('users', 'kartu_pelajar_path')) {
                $table->string('kartu_pelajar_path')->nullable()->after('ktp_orang_tua_path');
            }
        });

        // Pindahkan nilai KTP lama (milik user) ke field KTP user.
        DB::table('users')
            ->whereNotNull('ktp_path')
            ->whereNull('ktp_user_path')
            ->update(['ktp_user_path' => DB::raw('ktp_path')]);

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'ktp_path')) {
                $table->dropColumn('ktp_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'ktp_path')) {
                $table->string('ktp_path')->nullable()->after('domicile');
            }
        });

        // Kembalikan nilai KTP user ke kolom lama.
        DB::table('users')
            ->whereNotNull('ktp_user_path')
            ->whereNull('ktp_path')
            ->update(['ktp_path' => DB::raw('ktp_user_path')]);

        Schema::table('users', function (Blueprint $table) {
            $columns = ['ktp_user_path', 'ktp_orang_tua_path', 'kartu_pelajar_path'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};