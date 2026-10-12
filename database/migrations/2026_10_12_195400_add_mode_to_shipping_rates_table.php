<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->string('mode', 16)->default('weight')->after('warehouse_id');
            $table->decimal('min_weight', 10, 3)->change();
            $table->decimal('max_weight', 10, 3)->change();
            $table->index(['shipping_method_id', 'mode', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->dropIndex(['shipping_method_id', 'mode', 'is_active']);
            $table->dropColumn('mode');
            $table->decimal('min_weight', 10, 2)->change();
            $table->decimal('max_weight', 10, 2)->change();
        });
    }
};
