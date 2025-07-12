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
        Schema::table('cash_register_transactions', function (Blueprint $table) {
            // Drop the existing enum column
            $table->dropColumn('type');
        });

        Schema::table('cash_register_transactions', function (Blueprint $table) {
            // Recreate the enum column with all transaction types
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
            ])->after('cash_register_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_register_transactions', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('cash_register_transactions', function (Blueprint $table) {
            $table->enum('type', [
                'sale',
                'deposit',
                'inter-transfer-in',
                'inter-transfer-out',
                'return-as-deposit',
                'opening',
                'closing'
            ])->after('cash_register_id');
        });
    }
};
