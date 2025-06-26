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
        Schema::create('print_purchase_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases');
            $table->foreignId('user_id')->constrained('users');
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('print_purchase_records');
    }
};
