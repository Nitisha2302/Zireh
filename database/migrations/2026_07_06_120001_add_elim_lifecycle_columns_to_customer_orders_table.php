<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_orders', function (Blueprint $table) {
            $table->json('elim_detail_snapshot')->nullable()->after('elim_create_snapshot');
            $table->timestamp('paid_at')->nullable()->after('payment_status');
            $table->foreignId('wallet_transaction_id')->nullable()->after('paid_at')
                ->constrained('wallet_transactions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('wallet_transaction_id');
            $table->dropColumn(['elim_detail_snapshot', 'paid_at']);
        });
    }
};
