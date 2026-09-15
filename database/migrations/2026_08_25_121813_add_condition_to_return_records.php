<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('return_records', function (Blueprint $table) {
            $table->enum('condition', ['excellent', 'good', 'needs_cleaning', 'minor_damage', 'major_damage'])
                  ->nullable()
                  ->after('status');
            $table->text('damage_description')->nullable()->after('condition');
            $table->unsignedInteger('damage_cost')->default(0)->after('damage_description');
            $table->text('inspection_note')->nullable()->after('damage_cost');
            $table->string('inspection_photo')->nullable()->after('inspection_note');
        });
    }

    public function down(): void
    {
        Schema::table('return_records', function (Blueprint $table) {
            $table->dropColumn([
                'condition',
                'damage_description',
                'damage_cost',
                'inspection_note',
                'inspection_photo',
            ]);
        });
    }
};
