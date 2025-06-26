<?php

namespace Database\Factories;

use App\Models\SoldProduct;
use App\Models\Sale;
use App\Models\ProductUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SoldProduct>
 */
class SoldProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SoldProduct::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(2, 1, 100);
        $price = $this->faker->randomFloat(2, 100, 5000);
        $discount = $this->faker->randomFloat(2, 0, $price * 0.2); // Max 20% discount
        $total = ($quantity * $price) - $discount;

        return [
            'sale_id' => Sale::factory(),
            'product_unit_id' => ProductUnit::factory(),
            'quantity' => $quantity,
            'price' => $price,
            'discount' => $discount,
            'total' => $total,
            'data' => null,
        ];
    }
}
