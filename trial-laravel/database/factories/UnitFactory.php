<?php

namespace Database\Factories;

use App\Enums\UnitStatus;
use App\Models\OrderItem;
use App\Models\Stock;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'status' => UnitStatus::Waiting,
            'location_id' => null,
        ];
    }

    /**
     * Indicate that the unit is at a new location stocking its order item's product.
     */
    public function withLocation(): static
    {
        return $this->state(fn () => [
            'location_id' => fn (array $attributes) => Stock::factory()
                ->create(['product_id' => OrderItem::findOrFail($attributes['order_item_id'])->product_id])
                ->location_id,
        ]);
    }
}
