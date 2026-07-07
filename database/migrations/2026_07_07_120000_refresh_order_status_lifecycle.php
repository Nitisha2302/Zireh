<?php

use App\Models\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $statusMap = [
            'creating' => OrderStatus::CODE_PAID,
            'pending_payment' => OrderStatus::CODE_PAID,
            'shipped' => OrderStatus::CODE_SHIPPED_TO_TAJIKISTAN,
            'completed' => OrderStatus::CODE_DELIVERED_TO_CUSTOMER,
            'paid' => OrderStatus::CODE_PAID,
            'cancelled' => OrderStatus::CODE_CANCELLED,
        ];

        foreach ($statusMap as $from => $to) {
            DB::table('customer_orders')
                ->where('status', $from)
                ->update(['status' => $to]);
        }

        DB::table('customer_orders')
            ->whereNotIn('status', OrderStatus::SYSTEM_CODES)
            ->update(['status' => OrderStatus::CODE_PAID]);

        if (Schema::hasTable('order_statuses')) {
            DB::table('order_statuses')
                ->whereIn('code', ['creating', 'pending_payment', 'shipped', 'completed'])
                ->update(['is_active' => false]);
        }
    }

    public function down(): void
    {
        // Irreversible data migration.
    }
};
