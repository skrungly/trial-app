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
        Schema::create('packaging', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128)->unique();
            // The most units that fit in one package.
            $table->unsignedInteger('max_units');
            // The most a package can hold, in grams like product weight.
            $table->unsignedInteger('max_weight');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packaging');
    }
};
