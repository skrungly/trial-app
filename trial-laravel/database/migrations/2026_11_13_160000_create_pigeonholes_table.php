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
        Schema::create('pigeonholes', function (Blueprint $table) {
            $table->id();
            // Kept as strings so leading zeros survive, e.g. shelf "02".
            $table->string('aisle', 16);
            $table->string('shelf', 16);

            // Optional, and unique so an order sits in at most one pigeonhole.
            // Emptied rather than deleted when its order is deleted.
            $table->foreignId('order_id')
                ->nullable()
                ->unique()
                ->constrained()
                ->nullOnDelete();

            $table->timestamps();

            // Each aisle/shelf combination is one physical pigeonhole.
            $table->unique(['aisle', 'shelf']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pigeonholes');
    }
};
