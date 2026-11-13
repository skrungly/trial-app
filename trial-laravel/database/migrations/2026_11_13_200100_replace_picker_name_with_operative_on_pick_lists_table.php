<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pick_lists', function (Blueprint $table) {
            // The operative picking this list, once someone has taken it.
            // Restricted so an operative's pick lists keep a record of who picked them.
            $table->foreignId('operative_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->restrictOnDelete();
        });

        // Keep any picker names already recorded by turning each one into an operative.
        DB::table('pick_lists')
            ->whereNotNull('picker_name')
            ->distinct()
            ->pluck('picker_name')
            ->each(function (string $name) {
                $operativeId = DB::table('operatives')->insertGetId([
                    'name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('pick_lists')->where('picker_name', $name)->update(['operative_id' => $operativeId]);
            });

        Schema::table('pick_lists', function (Blueprint $table) {
            $table->dropColumn('picker_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pick_lists', function (Blueprint $table) {
            $table->string('picker_name', 128)->nullable()->after('id');
        });

        // Copy operatives' names back onto their pick lists.
        DB::table('pick_lists')
            ->whereNotNull('operative_id')
            ->update([
                'picker_name' => DB::table('operatives')
                    ->select('name')
                    ->whereColumn('operatives.id', 'pick_lists.operative_id'),
            ]);

        Schema::table('pick_lists', function (Blueprint $table) {
            $table->dropConstrainedForeignId('operative_id');
        });
    }
};
