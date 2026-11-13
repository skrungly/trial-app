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
        // Add the columns as nullable first so existing orders can be backfilled.
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status', 32)->default('paid')->after('total');
            $table->timestamp('paid_at')->nullable()->after('status');
            $table->string('customer_name', 128)->nullable()->after('paid_at');
            $table->text('address')->nullable()->after('customer_name');
        });

        // Existing orders were all paid when placed, and have no customer details.
        DB::table('orders')->update([
            'paid_at' => DB::raw('created_at'),
            'customer_name' => '',
            'address' => '',
        ]);

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable(false)->change();
            $table->string('customer_name', 128)->nullable(false)->change();
            $table->text('address')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['status', 'paid_at', 'customer_name', 'address']);
        });
    }
};
