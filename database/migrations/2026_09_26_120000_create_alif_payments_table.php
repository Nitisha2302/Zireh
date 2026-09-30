<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alif_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_id', 64)->unique();
            $table->string('response_id', 64)->nullable()->unique();
            $table->string('account', 64);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 10)->default('TJS');
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('code')->nullable();
            $table->string('srv_id', 64)->nullable();
            $table->boolean('is_commercial')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('wallet_transaction_id')->nullable()
                ->constrained('wallet_transactions')->nullOnDelete();
            $table->json('request_payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alif_payments');
    }
};
