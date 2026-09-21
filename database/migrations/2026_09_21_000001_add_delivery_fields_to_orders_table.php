<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('delivery_method', ['pickup', 'delivery'])->nullable()->after('notes');
            $table->string('recipient_name', 150)->nullable()->after('delivery_method');
            $table->string('recipient_phone', 30)->nullable()->after('recipient_name');
            $table->text('delivery_address')->nullable()->after('recipient_phone');
            $table->text('delivery_note')->nullable()->after('delivery_address');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_method', 'recipient_name', 'recipient_phone', 'delivery_address', 'delivery_note']);
        });
    }
};