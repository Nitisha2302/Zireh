<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dc_next_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 32)->default('wallet_topup');
            $table->string('order_id')->unique();
            $table->string('invoice_id')->nullable()->index();
            $table->decimal('amount_tjs', 12, 2);
            $table->string('status', 32)->default('pending');
            $table->string('payid')->nullable()->unique();
            $table->timestamp('paid_at')->nullable();
            $table->json('callback_payload')->nullable();
            $table->foreignId('wallet_transaction_id')->nullable()
                ->constrained('wallet_transactions')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dc_next_payments');
    }
};
