<?php

namespace Database\Factories;

use App\Models\ProformaInvoice;
use App\Models\Store;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProformaInvoice>
 */
class ProformaInvoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProformaInvoice::class;

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
            'invoice_number' => 'PRO-' . str_pad($this->faker->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'issued_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'valid_until' => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
            'subtotal' => $this->faker->randomFloat(2, 1000, 50000),
            'discount' => $this->faker->randomFloat(2, 0, 5000),
            'total' => function (array $attributes) {
                return $attributes['subtotal'] - $attributes['discount'];
            },
            'data' => null,
            'notes' => $this->faker->optional()->sentence(),
            'status' => $this->faker->randomElement([
                ProformaInvoice::STATUS_DRAFT,
                ProformaInvoice::STATUS_SENT,
                ProformaInvoice::STATUS_ACCEPTED,
                ProformaInvoice::STATUS_REJECTED,
            ]),
        ];
    }
}
