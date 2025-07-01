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
        Schema::create('proforma_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proforma_invoice_id')
                ->constrained('proforma_invoices')
                ->onDelete('cascade');
            $table->foreignId('product_unit_id')
                ->nullable()
                ->constrained('product_units')
                ->onDelete('cascade');
            $table->foreignId('pack_id')
                ->nullable()
                ->constrained('packs')
                ->onDelete('cascade');
            $table->decimal('quantity', 10, 2);
            $table->decimal('price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proforma_invoice_items');
    }
};
