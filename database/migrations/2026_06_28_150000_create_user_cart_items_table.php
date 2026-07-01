<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 20);
            $table->string('product_id');
            $table->string('marketplace_id')->nullable();
            $table->string('sku_id')->default('');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->json('product_snapshot');
            $table->json('selected_attributes')->nullable();
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->unique(['user_id', 'platform', 'product_id', 'sku_id']);
            $table->index(['user_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_cart_items');
    }
};
