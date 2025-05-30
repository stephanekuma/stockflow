<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
            'name' => $this->faker->name,
            'sku' => $this->faker->unique()->ean13,
            'description' => $this->faker->text,
            'image' => $this->faker->imageUrl(),
            'data' => [],
        ];
    }
}
