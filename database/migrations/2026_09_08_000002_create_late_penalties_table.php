<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('late_penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('return_record_id')->nullable()->constrained('return_records')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->unsignedInteger('days_overdue')->default(0);
            $table->unsignedInteger('fee_per_day')->default(10000);
            $table->unsignedInteger('total_fee')->default(0);
            $table->enum('status', [
                'tidak_ada_sanksi',
                'belum_diproses',
                'menunggu_pembayaran',
                'sudah_dibayar',
                'dibatalkan',
            ])->default('belum_diproses');
            $table->text('admin_notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('late_penalties');
    }
};
