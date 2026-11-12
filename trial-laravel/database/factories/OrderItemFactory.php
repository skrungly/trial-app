<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            // Copy the product's name and price, as placing an order does.
            'product_name' => fn (array $attributes) => Product::find($attributes['product_id'])->name,
            'unit_price' => fn (array $attributes) => Product::find($attributes['product_id'])->price,
            'quantity' => fake()->numberBetween(1, 5),
        ];
    }
}
