<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate the whole code uniquely so no two locations share an aisle, bay and shelf.
        $code = fake()->unique()->bothify(implode(Location::CODE_SEPARATOR, ['?', '##', '#']));

        [$aisle, $bay, $shelf] = explode(Location::CODE_SEPARATOR, strtoupper($code));

        return [
            'aisle' => $aisle,
            'bay' => $bay,
            'shelf' => $shelf,
        ];
    }
}
