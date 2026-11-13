<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Manufacturer;
use App\Models\Material;
use App\Models\Operative;
use App\Models\Order;
use App\Models\Packaging;
use App\Models\Pigeonhole;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * How many orders hold a single unit, and how many hold more than one.
     */
    private const SINGLE_UNIT_ORDERS = 15;

    private const MULTI_UNIT_ORDERS = 15;

    /**
     * Seed the application's database.
     *
     * Model events stay on so orders get their paid date and units are checked against stock.
     */
    public function run(): void
    {
        $manufacturers = Manufacturer::factory()->count(5)->create();
        $materials = Material::factory()->count(3)->create();

        // Assign each product a random existing manufacturer and material
        // instead of letting the factory create new ones.
        $products = Product::factory()
            ->count(10)
            ->recycle($manufacturers)
            ->recycle($materials)
            ->create();

        // Leave the last product out of stock, so there's a product with nowhere to pick it from.
        $stockedProducts = $products->slice(0, -1)->values();

        // Each location stocks one product; cycling through the rest means each of them is stocked somewhere.
        Stock::factory()
            ->count(20)
            ->state(new Sequence(fn (Sequence $sequence) => [
                'product_id' => $stockedProducts[$sequence->index % $stockedProducts->count()]->id,
            ]))
            ->create();

        Pigeonhole::factory()->count(12)->create();

        Packaging::create(['name' => 'Small mailer', 'max_units' => 1, 'max_weight' => 1000]);
        Packaging::create(['name' => 'Medium box', 'max_units' => 5, 'max_weight' => 5000]);
        Packaging::create(['name' => 'Large box', 'max_units' => 20, 'max_weight' => 20000]);

        Operative::factory()->count(5)->create();

        for ($i = 0; $i < self::SINGLE_UNIT_ORDERS; $i++) {
            $this->createOrder([[$products->random(), 1]]);
        }

        for ($i = 0; $i < self::MULTI_UNIT_ORDERS; $i++) {
            $this->createOrder($this->multiUnitLines($products));
        }
    }

    /**
     * Pick one to three different products with quantities adding up to more than one unit.
     *
     * @param  Collection<int, Product>  $products
     * @return list<array{0: Product, 1: int}>
     */
    private function multiUnitLines(Collection $products): array
    {
        $lines = $products->random(fake()->numberBetween(1, 3))
            ->map(fn (Product $product) => [$product, fake()->numberBetween(1, 3)])
            ->values()
            ->all();

        // A lone line of one unit would be a single-unit order, so bump it.
        if (count($lines) === 1 && $lines[0][1] === 1) {
            $lines[0][1] = 2;
        }

        return $lines;
    }

    /**
     * Create a paid order with a line per product, its total and a unit per item, as placing one does.
     *
     * @param  list<array{0: Product, 1: int}>  $lines  Each product with the quantity ordered.
     */
    private function createOrder(array $lines): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Paid]);
        $total = '0';

        foreach ($lines as [$product, $quantity]) {
            $line = $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'unit_price' => $product->price,
                'quantity' => $quantity,
            ]);

            $line->createUnits();

            $total = bcadd($total, $line->line_total, 2);
        }

        $order->update(['total' => $total]);
    }
}
