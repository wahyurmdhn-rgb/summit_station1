<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('promotions');
    }

    public function down(): void
    {
        Schema::create('promotions', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('label');
            $table->string('type', 16)->default('fixed');
            $table->unsignedInteger('value');
            $table->unsignedInteger('min_spend')->nullable();
            $table->unsignedInteger('max_discount')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
