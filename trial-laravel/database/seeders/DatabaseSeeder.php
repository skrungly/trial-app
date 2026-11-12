<?php

namespace Database\Seeders;

use App\Models\Manufacturer;
use App\Models\Material;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $manufacturers = Manufacturer::factory()->count(5)->create();
        $materials = Material::factory()->count(3)->create();

        // Assign each product a random existing manufacturer and material
        // instead of letting the factory create new ones.
        Product::factory()
            ->count(10)
            ->recycle($manufacturers)
            ->recycle($materials)
            ->create();
    }
}
