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
        // Ajouter des champs pour la conversion d'unités
        Schema::table('units', function (Blueprint $table) {
            $table->boolean('is_base_unit')->default(false)->after('key');
            $table->decimal('conversion_factor', 10, 4)->default(1)->after('is_base_unit');
            $table->foreignId('base_unit_id')->nullable()->after('conversion_factor')
                ->constrained('units')->onDelete('set null');
        });

        // Ajouter des champs pour les conversions personnalisées par produit
        Schema::table('product_units', function (Blueprint $table) {
            $table->decimal('custom_conversion_factor', 10, 4)->nullable()->after('quantity');
            $table->foreignId('custom_base_unit_id')->nullable()->after('custom_conversion_factor')
                ->constrained('units')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_units', function (Blueprint $table) {
            $table->dropForeign(['custom_base_unit_id']);
            $table->dropColumn(['custom_conversion_factor', 'custom_base_unit_id']);
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropForeign(['base_unit_id']);
            $table->dropColumn(['is_base_unit', 'conversion_factor', 'base_unit_id']);
        });
    }
};
