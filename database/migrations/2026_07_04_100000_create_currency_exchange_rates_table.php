<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currency_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 10)->default('CNY');
            $table->string('to_currency', 10)->default('TJS');
            $table->decimal('exchange_rate', 16, 6);
            $table->boolean('auto_refresh_enabled')->default(false);
            $table->unsignedTinyInteger('refresh_interval_hours')->default(1);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['from_currency', 'to_currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_exchange_rates');
    }
};
