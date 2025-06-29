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
        Schema::create('stock_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->onDelete('cascade');
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->onDelete('cascade');
            $table->foreignId('sale_id')
                ->nullable()
                ->constrained('sales')
                ->onDelete('set null');
            $table->string('return_number')->unique();
            $table->timestamp('returned_at')->default(now());
            $table->enum('type', ['return', 'exchange', 'refund'])
                ->default('return');
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])
                ->default('pending');
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->decimal('total_refund', 10, 2)->default(0);
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_returns');
    }
};
