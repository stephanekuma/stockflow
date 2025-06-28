<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitConversionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer des unités de base avec conversions
        $this->createUnitConversions();
    }

    private function createUnitConversions(): void
    {
        // Exemple 1: Système de pièces/cartons
        $piece = Unit::create([
            'store_id' => 1, // Ajustez selon votre store
            'name' => 'Pièce',
            'key' => 'piece',
            'is_base_unit' => true,
            'conversion_factor' => 1,
            'base_unit_id' => null,
        ]);

        $carton = Unit::create([
            'store_id' => 1,
            'name' => 'Carton',
            'key' => 'carton',
            'is_base_unit' => false,
            'conversion_factor' => 20, // 1 carton = 20 pièces
            'base_unit_id' => $piece->id,
        ]);

        // Exemple 2: Système de poids
        $gram = Unit::create([
            'store_id' => 1,
            'name' => 'Gramme',
            'key' => 'gram',
            'is_base_unit' => true,
            'conversion_factor' => 1,
            'base_unit_id' => null,
        ]);

        $kilogram = Unit::create([
            'store_id' => 1,
            'name' => 'Kilogramme',
            'key' => 'kg',
            'is_base_unit' => false,
            'conversion_factor' => 1000, // 1 kg = 1000 g
            'base_unit_id' => $gram->id,
        ]);

        // Exemple 3: Système de volume
        $milliliter = Unit::create([
            'store_id' => 1,
            'name' => 'Millilitre',
            'key' => 'ml',
            'is_base_unit' => true,
            'conversion_factor' => 1,
            'base_unit_id' => null,
        ]);

        $liter = Unit::create([
            'store_id' => 1,
            'name' => 'Litre',
            'key' => 'l',
            'is_base_unit' => false,
            'conversion_factor' => 1000, // 1 L = 1000 ml
            'base_unit_id' => $milliliter->id,
        ]);

        // Exemple 4: Système de longueur
        $centimeter = Unit::create([
            'store_id' => 1,
            'name' => 'Centimètre',
            'key' => 'cm',
            'is_base_unit' => true,
            'conversion_factor' => 1,
            'base_unit_id' => null,
        ]);

        $meter = Unit::create([
            'store_id' => 1,
            'name' => 'Mètre',
            'key' => 'm',
            'is_base_unit' => false,
            'conversion_factor' => 100, // 1 m = 100 cm
            'base_unit_id' => $centimeter->id,
        ]);

        $this->command->info('Unités avec conversions créées avec succès!');
        $this->command->info('Exemples configurés:');
        $this->command->info('- Pièce (base) ↔ Carton (1 carton = 20 pièces)');
        $this->command->info('- Gramme (base) ↔ Kilogramme (1 kg = 1000 g)');
        $this->command->info('- Millilitre (base) ↔ Litre (1 L = 1000 ml)');
        $this->command->info('- Centimètre (base) ↔ Mètre (1 m = 100 cm)');
    }
}
