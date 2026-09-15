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
        if (Schema::hasTable('reviews')) {
            Schema::table('reviews', function (Blueprint $table) {
                if (! Schema::hasColumn('reviews', 'order_id')) {
                    $table->foreignId('order_id')->nullable()->after('product_id')->constrained('orders')->cascadeOnDelete();
                }
                if (! Schema::hasColumn('reviews', 'is_visible')) {
                    $table->boolean('is_visible')->default(true)->after('comment');
                }
            });
        } else {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained('orders')->cascadeOnDelete();
                $table->unsignedTinyInteger('rating')->default(5);
                $table->string('title')->nullable();
                $table->text('comment')->nullable();
                $table->boolean('is_visible')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('reviews')) {
            Schema::table('reviews', function (Blueprint $table) {
                if (Schema::hasColumn('reviews', 'order_id')) {
                    $table->dropConstrainedForeignId('order_id');
                }
                if (Schema::hasColumn('reviews', 'is_visible')) {
                    $table->dropColumn('is_visible');
                }
            });
        }
    }
};
