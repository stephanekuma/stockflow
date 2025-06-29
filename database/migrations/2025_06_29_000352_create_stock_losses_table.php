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
        Schema::create('stock_losses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->string('loss_number')->unique();
            $table->timestamp('lost_at')->default(now());
            $table->enum('type', ['damaged', 'expired', 'stolen', 'other'])
                ->default('damaged');
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])
                ->default('pending');
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->decimal('total_loss', 10, 2)->default(0);
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_losses');
    }
};
