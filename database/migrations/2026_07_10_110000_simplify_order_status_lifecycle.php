<?php

use App\Models\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_statuses')) {
            return;
        }

        $names = [
            OrderStatus::CODE_PAID => 'Order Created',
            OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE => 'Received at China Warehouse',
            OrderStatus::CODE_SHIPPED_TO_TAJIKISTAN => 'In Transit',
            OrderStatus::CODE_ARRIVED_IN_TAJIKISTAN => 'Customs Clearance',
            OrderStatus::CODE_SORTING_CENTER => 'Sorting',
            OrderStatus::CODE_READY_FOR_PICKUP => 'Ready for Pickup',
            OrderStatus::CODE_DELIVERED_TO_CUSTOMER => 'Delivered',
            OrderStatus::CODE_CANCELLED => 'Cancelled',
        ];

        foreach ($names as $code => $name) {
            DB::table('order_statuses')
                ->where('code', $code)
                ->update([
                    'name' => $name,
                    'is_active' => true,
                    'is_system' => true,
                    'updated_at' => now(),
                ]);
        }

        DB::table('order_statuses')
            ->where('code', OrderStatus::CODE_PAID)
            ->update(['sort_order' => 10, 'color' => 'info']);

        DB::table('order_statuses')
            ->where('code', OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE)
            ->update(['sort_order' => 20, 'color' => 'primary']);

        DB::table('order_statuses')
            ->where('code', OrderStatus::CODE_SHIPPED_TO_TAJIKISTAN)
            ->update(['sort_order' => 30, 'color' => 'primary']);

        DB::table('order_statuses')
            ->where('code', OrderStatus::CODE_ARRIVED_IN_TAJIKISTAN)
            ->update(['sort_order' => 40, 'color' => 'warning']);

        DB::table('order_statuses')
            ->where('code', OrderStatus::CODE_SORTING_CENTER)
            ->update(['sort_order' => 50, 'color' => 'secondary']);

        DB::table('order_statuses')
            ->where('code', OrderStatus::CODE_READY_FOR_PICKUP)
            ->update(['sort_order' => 60, 'color' => 'success']);

        DB::table('order_statuses')
            ->where('code', OrderStatus::CODE_DELIVERED_TO_CUSTOMER)
            ->update(['sort_order' => 70, 'color' => 'success']);

        DB::table('order_statuses')
            ->where('code', OrderStatus::CODE_CANCELLED)
            ->update(['sort_order' => 80, 'color' => 'danger']);

        DB::table('order_statuses')
            ->whereIn('code', OrderStatus::LEGACY_INACTIVE_CODES)
            ->update([
                'is_active' => false,
                'is_system' => true,
                'updated_at' => now(),
            ]);

        if (Schema::hasTable('customer_orders')) {
            DB::table('customer_orders')
                ->where('status', OrderStatus::CODE_PREPARING_FOR_SHIPMENT)
                ->update(['status' => OrderStatus::CODE_SHIPPED_TO_TAJIKISTAN]);

            DB::table('customer_orders')
                ->where('status', OrderStatus::CODE_SENT_TO_SELECTED_WAREHOUSE)
                ->update(['status' => OrderStatus::CODE_SORTING_CENTER]);
        }
    }

    public function down(): void
    {
        // Irreversible data migration.
    }
};
