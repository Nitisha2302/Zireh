<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_orders', function (Blueprint $table): void {
            $table->foreignId('cancellation_refund_transaction_id')
                ->nullable()
                ->after('wallet_transaction_id')
                ->constrained('wallet_transactions')
                ->nullOnDelete();
            $table->foreignId('cancelled_by_admin_id')
                ->nullable()
                ->after('cancellation_refund_transaction_id')
                ->constrained('admins')
                ->nullOnDelete();
            $table->timestamp('cancelled_at')
                ->nullable()
                ->after('cancelled_by_admin_id');
        });
    }

    public function down(): void
    {
        Schema::table('customer_orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cancellation_refund_transaction_id');
            $table->dropConstrainedForeignId('cancelled_by_admin_id');
            $table->dropColumn('cancelled_at');
        });
    }
};
