<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('alif_payments');
        Schema::enableForeignKeyConstraints();

        Schema::create('alif_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('order_id', 64)->unique();
            $table->string('purpose', 32)->default('wallet_topup');
            $table->decimal('amount', 14, 2);
            $table->string('currency', 10)->default('TJS');
            $table->string('status', 20)->default('pending');
            $table->string('gate', 32)->default('korti_milli');
            $table->text('payment_url')->nullable();
            $table->string('alif_transaction_id', 128)->nullable();
            $table->json('callback_payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('wallet_transaction_id')->nullable()
                ->constrained('wallet_transactions')->nullOnDelete();
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
