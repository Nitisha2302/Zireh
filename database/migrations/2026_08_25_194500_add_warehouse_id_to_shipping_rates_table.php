<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->foreignId('warehouse_id')
                ->nullable()
                ->after('shipping_method_id')
                ->constrained('warehouses')
                ->nullOnDelete();

            $table->index(['shipping_method_id', 'warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->dropIndex(['shipping_method_id', 'warehouse_id']);
            $table->dropConstrainedForeignId('warehouse_id');
        });
    }
};
