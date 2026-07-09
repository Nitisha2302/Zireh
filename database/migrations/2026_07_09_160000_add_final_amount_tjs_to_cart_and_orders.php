<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_cart_items', function (Blueprint $table) {
            $table->decimal('final_amount_tjs', 12, 2)->nullable()->after('unit_price');
        });

        Schema::table('customer_order_items', function (Blueprint $table) {
            $table->decimal('final_amount_tjs', 12, 2)->nullable()->after('line_subtotal');
        });

        Schema::table('customer_orders', function (Blueprint $table) {
            $table->decimal('final_amount_tjs', 12, 2)->nullable()->after('customer_total_tjs');
        });

        if (Schema::hasColumn('customer_orders', 'customer_total_tjs')) {
            DB::table('customer_orders')
                ->whereNull('final_amount_tjs')
                ->update([
                    'final_amount_tjs' => DB::raw('customer_total_tjs'),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('user_cart_items', function (Blueprint $table) {
            $table->dropColumn('final_amount_tjs');
        });

        Schema::table('customer_order_items', function (Blueprint $table) {
            $table->dropColumn('final_amount_tjs');
        });

        Schema::table('customer_orders', function (Blueprint $table) {
            $table->dropColumn('final_amount_tjs');
        });
    }
};
