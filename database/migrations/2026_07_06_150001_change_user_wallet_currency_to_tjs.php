<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_wallets', function (Blueprint $table) {
            $table->string('currency', 10)->default('TJS')->change();
        });

        DB::table('user_wallets')->where('currency', 'CNY')->update(['currency' => 'TJS']);
        DB::table('wallet_transactions')->where('currency', 'CNY')->update(['currency' => 'TJS']);
    }

    public function down(): void
    {
        DB::table('user_wallets')->where('currency', 'TJS')->update(['currency' => 'CNY']);
        DB::table('wallet_transactions')->where('currency', 'TJS')->update(['currency' => 'CNY']);

        Schema::table('user_wallets', function (Blueprint $table) {
            $table->string('currency', 10)->default('CNY')->change();
        });
    }
};
