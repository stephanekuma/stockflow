<?php

namespace Database\Factories;

use App\Models\ProformaInvoiceItem;
use App\Models\ProformaInvoice;
use App\Models\ProductUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProformaInvoiceItem>
 */
class ProformaInvoiceItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProformaInvoiceItem::class;

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
            'proforma_invoice_id' => ProformaInvoice::factory(),
            'product_unit_id' => ProductUnit::factory(),
            'pack_id' => null,
            'quantity' => $quantity,
            'price' => $price,
            'discount' => $discount,
            'total' => $total,
            'data' => null,
        ];
    }
}
