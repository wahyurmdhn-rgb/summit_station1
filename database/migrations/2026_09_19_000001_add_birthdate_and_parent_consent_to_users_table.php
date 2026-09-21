<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom tanggal lahir dan data persetujuan orang tua
     * ke tabel users. Umur dihitung otomatis dari date_of_birth sehingga
     * tidak disimpan sebagai kolom sendiri (menghindari data yang bisa
     * dideduplikasi / dimanipulasi).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('domicile');
            }
            if (! Schema::hasColumn('users', 'parent_consent_status')) {
                // not_required | pending | submitted | verified | rejected
                $table->string('parent_consent_status')->default('not_required')->after('date_of_birth');
            }
            if (! Schema::hasColumn('users', 'parent_name')) {
                $table->string('parent_name')->nullable()->after('parent_consent_status');
            }
            if (! Schema::hasColumn('users', 'parent_relation')) {
                $table->string('parent_relation')->nullable()->after('parent_name');
            }
            if (! Schema::hasColumn('users', 'parent_phone')) {
                $table->string('parent_phone')->nullable()->after('parent_relation');
            }
            if (! Schema::hasColumn('users', 'parent_consent_path')) {
                $table->string('parent_consent_path')->nullable()->after('parent_phone');
            }
            if (! Schema::hasColumn('users', 'parent_consent_rejected_reason')) {
                $table->text('parent_consent_rejected_reason')->nullable()->after('parent_consent_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'date_of_birth',
                'parent_consent_status',
                'parent_name',
                'parent_relation',
                'parent_phone',
                'parent_consent_path',
                'parent_consent_rejected_reason',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};