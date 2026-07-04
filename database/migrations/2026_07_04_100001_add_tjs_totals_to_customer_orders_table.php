<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_orders', function (Blueprint $table) {
            $table->decimal('exchange_rate', 16, 6)->nullable()->after('customer_total_cny');
            $table->decimal('customer_total_tjs', 14, 2)->nullable()->after('exchange_rate');
        });
    }

    public function down(): void
    {
        Schema::table('customer_orders', function (Blueprint $table) {
            $table->dropColumn(['exchange_rate', 'customer_total_tjs']);
        });
    }
};
