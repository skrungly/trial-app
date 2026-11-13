<?php

namespace Database\Factories;

use App\Models\Operative;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Operative>
 */
class OperativeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
        ];
    }
}
