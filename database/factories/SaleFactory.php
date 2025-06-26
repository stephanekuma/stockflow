<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\Store;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Sale::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'customer_id' => Customer::factory(),
            'invoice_number' => 'INV-' . str_pad($this->faker->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'sold_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'subtotal' => $this->faker->randomFloat(2, 1000, 50000),
            'discount' => $this->faker->randomFloat(2, 0, 5000),
            'total' => function (array $attributes) {
                return $attributes['subtotal'] - $attributes['discount'];
            },
            'data' => null,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
