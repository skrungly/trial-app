<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stock>
 */
class StockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'location_id' => Location::factory(),
            // At least one, so units can always be given a factory-made location.
            'quantity' => fake()->numberBetween(1, 100),
        ];
    }
}
