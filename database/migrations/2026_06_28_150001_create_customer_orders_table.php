<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('platform_id')->nullable()->constrained('platforms')->nullOnDelete();
            $table->string('platform', 20);
            $table->string('elim_order_id')->nullable()->unique();
            $table->string('status', 50)->default('creating');
            $table->string('payment_status', 50)->default('unpaid');
            $table->decimal('goods_subtotal_cny', 12, 2)->default(0);
            $table->decimal('shipping_fee_cny', 12, 2)->default(0);
            $table->decimal('elim_service_fee_cny', 12, 2)->nullable();
            $table->foreignId('commission_slab_id')->nullable()->constrained('platform_commission_slabs')->nullOnDelete();
            $table->decimal('commission_percentage', 8, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('customer_total_cny', 12, 2)->default(0);
            $table->json('receiver_address')->nullable();
            $table->text('remark')->nullable();
            $table->json('elim_preview_snapshot')->nullable();
            $table->json('elim_create_snapshot')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'platform']);
            $table->index(['platform', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_orders');
    }
};
