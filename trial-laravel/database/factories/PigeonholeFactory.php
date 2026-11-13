<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Pigeonhole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pigeonhole>
 */
class PigeonholeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate the whole code uniquely so no two pigeonholes share an aisle and shelf.
        $code = fake()->unique()->bothify(implode(Pigeonhole::CODE_SEPARATOR, ['?', '##']));

        [$aisle, $shelf] = explode(Pigeonhole::CODE_SEPARATOR, strtoupper($code));

        return [
            'aisle' => $aisle,
            'shelf' => $shelf,
            'order_id' => null,
        ];
    }

    /**
     * Indicate that the pigeonhole holds an order.
     */
    public function withOrder(): static
    {
        return $this->state(fn () => ['order_id' => Order::factory()]);
    }
}
