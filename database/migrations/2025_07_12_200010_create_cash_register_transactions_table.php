<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cash_register_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_register_id')->constrained()->onDelete('cascade');
            $table->enum('type', [
                'sale',
                'deposit',
                'inter-transfer-in',
                'inter-transfer-out',
                'return-as-deposit',
                'opening',
                'closing',
                'expense',
                'purchase',
                'payroll',
                'customer-deposit',
                'customer-payment',
                'provider-payment'
            ]);
            $table->decimal('amount', 15, 2);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_register_transactions');
    }
};
