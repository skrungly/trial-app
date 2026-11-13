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
        Schema::create('pick_lists', function (Blueprint $table) {
            $table->id();
            // Who is picking this list, once someone has taken it.
            $table->string('picker_name', 128)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('units', function (Blueprint $table) {
            // The pick list a unit has been batched into, if any.
            // Units go back to being unbatched if their pick list is deleted.
            $table->foreignId('pick_list_id')
                ->nullable()
                ->after('location_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pick_list_id');
        });

        Schema::dropIfExists('pick_lists');
    }
};
