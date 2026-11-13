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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            // Kept as strings so leading zeros survive, e.g. bay "03".
            $table->string('aisle', 16);
            $table->string('bay', 16);
            $table->string('shelf', 16);
            $table->timestamps();

            // Each aisle/bay/shelf combination is one physical place.
            $table->unique(['aisle', 'bay', 'shelf']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
