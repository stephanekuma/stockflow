<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Unit;
use App\Models\ProductUnit;

class ProductUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();
        $units = Unit::all();

        if ($products->isEmpty() || $units->isEmpty()) {
            $this->command->warn('Skipping ProductUnitSeeder: Missing products or units');
            return;
        }

        foreach ($products as $product) {
            // Associer chaque produit à la première unité disponible
            $unit = $units->first();

            ProductUnit::create([
                'store_id' => $product->store_id,
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'quantity' => 1,
                'cost_price' => 100,
                'price' => 150,
            ]);

            $this->command->info("ProductUnit créé pour le produit {$product->name} avec l'unité {$unit->name}");
        }

        $this->command->info('ProductUnitSeeder completed successfully!');
    }
}
