<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_orders', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('user_address_id')->nullable()->after('warehouse_id')->constrained('user_addresses')->nullOnDelete();
            $table->foreignId('shipping_method_id')->nullable()->after('user_address_id')->constrained()->nullOnDelete();
            $table->string('payment_method', 20)->default('online')->after('payment_status');
            $table->decimal('cargo_shipping_fee_tjs', 12, 2)->default(0)->after('shipping_fee_cny');
            $table->decimal('cargo_shipping_fee_cny', 12, 2)->default(0)->after('cargo_shipping_fee_tjs');
            $table->json('warehouse_snapshot')->nullable()->after('receiver_address');
            $table->json('address_snapshot')->nullable()->after('warehouse_snapshot');
            $table->boolean('is_demo_order')->default(false)->after('elim_create_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('customer_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropConstrainedForeignId('user_address_id');
            $table->dropConstrainedForeignId('shipping_method_id');
            $table->dropColumn([
                'payment_method',
                'cargo_shipping_fee_tjs',
                'cargo_shipping_fee_cny',
                'warehouse_snapshot',
                'address_snapshot',
                'is_demo_order',
            ]);
        });
    }
};
