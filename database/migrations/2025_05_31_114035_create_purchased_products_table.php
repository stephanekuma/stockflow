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
        Schema::create('purchased_products', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('store_id')
            //     ->constrained('stores')
            //     ->onDelete('cascade');
            $table->foreignId('purchase_id')
                ->constrained('purchases')
                ->onDelete('cascade');
            $table->foreignId('product_unit_id')
                ->constrained('product_units')
                ->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('discount', 10, 2)->nullable();
            $table->decimal('vat', 10, 2)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchased_products');
    }
};
