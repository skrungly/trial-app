<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock', function (Blueprint $table) {
            $table->id();

            // Restricted so a product or location can't be deleted while stock records point at it.
            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            // Unique: a location holds a single stock record, so one product.
            $table->foreignId('location_id')
                ->unique()
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock');
    }
};
