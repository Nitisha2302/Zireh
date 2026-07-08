<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_orders', function (Blueprint $table) {
            $table->decimal('package_length_cm', 10, 2)->nullable()->after('cargo_shipping_fee_cny');
            $table->decimal('package_width_cm', 10, 2)->nullable()->after('package_length_cm');
            $table->decimal('package_height_cm', 10, 2)->nullable()->after('package_width_cm');
            $table->decimal('package_weight_kg', 10, 2)->nullable()->after('package_height_cm');
            $table->decimal('package_volume_m3', 12, 6)->nullable()->after('package_weight_kg');
            $table->decimal('pickup_shipping_fee_tjs', 12, 2)->nullable()->after('package_volume_m3');
            $table->decimal('pickup_shipping_weight_fee_tjs', 12, 2)->nullable()->after('pickup_shipping_fee_tjs');
            $table->decimal('pickup_shipping_volume_fee_tjs', 12, 2)->nullable()->after('pickup_shipping_weight_fee_tjs');
            $table->string('pickup_shipping_calculation_method', 20)->nullable()->after('pickup_shipping_volume_fee_tjs');
            $table->json('pickup_shipping_snapshot')->nullable()->after('pickup_shipping_calculation_method');
            $table->string('pickup_payment_status', 20)->nullable()->after('pickup_shipping_snapshot');
            $table->timestamp('pickup_paid_at')->nullable()->after('pickup_payment_status');
            $table->foreignId('pickup_wallet_transaction_id')->nullable()->after('pickup_paid_at')->constrained('wallet_transactions')->nullOnDelete();
            $table->uuid('pickup_token')->nullable()->unique()->after('pickup_wallet_transaction_id');
            $table->timestamp('pickup_confirmed_at')->nullable()->after('pickup_token');
            $table->foreignId('pickup_confirmed_by')->nullable()->after('pickup_confirmed_at')->constrained('admins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pickup_wallet_transaction_id');
            $table->dropConstrainedForeignId('pickup_confirmed_by');
            $table->dropColumn([
                'package_length_cm',
                'package_width_cm',
                'package_height_cm',
                'package_weight_kg',
                'package_volume_m3',
                'pickup_shipping_fee_tjs',
                'pickup_shipping_weight_fee_tjs',
                'pickup_shipping_volume_fee_tjs',
                'pickup_shipping_calculation_method',
                'pickup_shipping_snapshot',
                'pickup_payment_status',
                'pickup_paid_at',
                'pickup_token',
                'pickup_confirmed_at',
            ]);
        });
    }
};
