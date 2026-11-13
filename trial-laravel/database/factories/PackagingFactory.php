<?php

namespace Database\Factories;

use App\Models\Packaging;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Packaging>
 */
class PackagingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true).' '.fake()->randomElement(['box', 'mailer']),
            'max_units' => fake()->numberBetween(1, 20),
            // Grams, matching product weight.
            'max_weight' => fake()->numberBetween(500, 20000),
        ];
    }
}
