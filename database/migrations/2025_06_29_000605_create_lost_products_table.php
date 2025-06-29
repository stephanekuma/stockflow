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
        Schema::create('lost_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_loss_id')
                ->constrained('stock_losses')
                ->onDelete('cascade');
            $table->foreignId('product_unit_id')
                ->nullable()
                ->constrained('product_units')
                ->onDelete('set null');
            $table->foreignId('pack_id')
                ->nullable()
                ->constrained('packs')
                ->onDelete('set null');
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->decimal('loss_amount', 10, 2);
            $table->text('loss_reason');
            $table->enum('condition', ['damaged', 'expired', 'stolen', 'other'])
                ->default('damaged');
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lost_products');
    }
};
