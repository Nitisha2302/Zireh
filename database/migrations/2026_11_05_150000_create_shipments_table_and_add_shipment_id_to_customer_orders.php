<?php

use App\Models\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shipments')) {
            Schema::create('shipments', function (Blueprint $table): void {
                $table->id();
                $table->string('shipment_code', 120)->unique();
                $table->string('status', 50)->default(OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE)->index();
                $table->text('notes')->nullable();
                $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
                $table->timestamp('received_in_tajikistan_at')->nullable();
                $table->foreignId('received_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('customer_orders') && ! Schema::hasColumn('customer_orders', 'shipment_id')) {
            Schema::table('customer_orders', function (Blueprint $table): void {
                $table->foreignId('shipment_id')
                    ->nullable()
                    ->after('warehouse_id')
                    ->constrained('shipments')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customer_orders') && Schema::hasColumn('customer_orders', 'shipment_id')) {
            Schema::table('customer_orders', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('shipment_id');
            });
        }

        Schema::dropIfExists('shipments');
    }
};
