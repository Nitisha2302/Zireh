<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_commission_slabs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_id')->constrained('platforms')->cascadeOnDelete();
            $table->decimal('min_amount', 15, 2);
            $table->decimal('max_amount', 15, 2)->nullable();
            $table->decimal('commission_percentage', 5, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['platform_id', 'is_active', 'min_amount']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_commission_slabs');
    }
};
