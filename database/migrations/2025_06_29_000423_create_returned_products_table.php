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
        Schema::create('returned_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_return_id')
                ->constrained('stock_returns')
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
            $table->decimal('refund_amount', 10, 2);
            $table->text('return_reason');
            $table->enum('condition', ['good', 'damaged', 'expired'])
                ->default('good');
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('returned_products');
    }
};
