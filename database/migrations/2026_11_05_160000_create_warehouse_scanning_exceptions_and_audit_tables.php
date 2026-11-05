<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('customer_orders', 'parcel_code')) {
            Schema::table('customer_orders', function (Blueprint $table): void {
                $table->string('parcel_code', 64)
                    ->nullable()
                    ->unique()
                    ->after('elim_order_id');
            });

            $year = date('Y');
            DB::table('customer_orders')
                ->whereNull('parcel_code')
                ->orderBy('id')
                ->chunkById(200, function ($orders) use ($year): void {
                    foreach ($orders as $order) {
                        $code = sprintf('PCL-%s-%06d-%s', $year, $order->id, strtoupper(Str::random(4)));
                        DB::table('customer_orders')
                            ->where('id', $order->id)
                            ->update(['parcel_code' => $code]);
                    }
                });
        }

        if (! Schema::hasTable('order_status_histories')) {
            Schema::create('order_status_histories', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('customer_order_id')
                    ->constrained('customer_orders')
                    ->cascadeOnDelete();
                $table->string('from_status', 50)->nullable();
                $table->string('to_status', 50);
                $table->string('source', 32)->index();
                $table->foreignId('admin_id')
                    ->nullable()
                    ->constrained('admins')
                    ->nullOnDelete();
                $table->string('notes', 500)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['customer_order_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('order_exceptions')) {
            Schema::create('order_exceptions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('customer_order_id')
                    ->nullable()
                    ->constrained('customer_orders')
                    ->nullOnDelete();
                $table->foreignId('shipment_id')
                    ->nullable()
                    ->constrained('shipments')
                    ->nullOnDelete();
                $table->foreignId('warehouse_id')
                    ->nullable()
                    ->constrained('warehouses')
                    ->nullOnDelete();
                $table->string('type', 40)->index();
                $table->string('status', 24)->default('open')->index();
                $table->string('origin_panel', 24)->default('china')->index();
                $table->string('scanned_code', 120)->nullable()->index();
                $table->string('summary', 255);
                $table->text('details')->nullable();
                $table->json('error_payload')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->foreignId('reported_by_admin_id')
                    ->nullable()
                    ->constrained('admins')
                    ->nullOnDelete();
                $table->foreignId('resolved_by_admin_id')
                    ->nullable()
                    ->constrained('admins')
                    ->nullOnDelete();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_exceptions');
        Schema::dropIfExists('order_status_histories');

        if (Schema::hasColumn('customer_orders', 'parcel_code')) {
            Schema::table('customer_orders', function (Blueprint $table): void {
                $table->dropUnique(['parcel_code']);
                $table->dropColumn('parcel_code');
            });
        }
    }
};
