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
        Schema::create('units', function (Blueprint $table) {
            $table->id();

            // Units only exist as part of an order line, so go with it.
            $table->foreignId('order_item_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('status', 32)->default('waiting');

            // Optional, as the unit's product may not be in stock anywhere yet.
            // Restricted so a location can't be deleted while units point at it.
            $table->foreignId('location_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
