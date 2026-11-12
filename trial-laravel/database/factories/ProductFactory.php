<?php

namespace Database\Factories;

use App\Models\Manufacturer;
use App\Models\Material;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
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
            'name' => ucfirst(fake()->words(3, true)),
            'colour' => fake()->safeColorName(),
            'size' => fake()->randomElement(['XS', 'S', 'M', 'L', 'XL', 'XXL']),
            'price' => fake()->randomFloat(2, 1, 500),
            'material_id' => Material::factory(),
            'manufacturer_id' => Manufacturer::factory(),
        ];
    }
}
