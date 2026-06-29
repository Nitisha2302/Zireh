<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('platforms', 'commission')) {
            Schema::table('platforms', function (Blueprint $table) {
                $table->dropColumn('commission');
            });
        }
    }

    public function down(): void
    {
        Schema::table('platforms', function (Blueprint $table) {
            $table->double('commission')->default(0)->after('code');
        });
    }
};
