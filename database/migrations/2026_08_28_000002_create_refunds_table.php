<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('code', 24)->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('original_amount')->default(0);
            $table->unsignedInteger('refund_amount')->default(0);
            $table->string('reason', 255)->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending')->index();
            $table->text('reject_reason')->nullable();
            $table->string('adjustment_reason', 255)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('order_id');
        });

        // Relasi processed_by menunjuk PK admin yang berbeda (id_admin)
        Schema::table('refunds', function (Blueprint $table) {
            $table->foreign('processed_by')->references('id_admin')->on('admin')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropForeign(['processed_by']);
        });
        Schema::dropIfExists('refunds');
    }
};