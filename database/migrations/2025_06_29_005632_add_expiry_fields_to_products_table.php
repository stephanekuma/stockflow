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
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_perishable')->default(false)->after('description');
            $table->date('expiry_date')->nullable()->after('is_perishable');
            $table->integer('expiry_alert_days')->default(30)->after('expiry_date');
            $table->enum('expiry_status', ['good', 'warning', 'expired'])->default('good')->after('expiry_alert_days');
            $table->text('expiry_notes')->nullable()->after('expiry_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'is_perishable',
                'expiry_date',
                'expiry_alert_days',
                'expiry_status',
                'expiry_notes'
            ]);
        });
    }
};
