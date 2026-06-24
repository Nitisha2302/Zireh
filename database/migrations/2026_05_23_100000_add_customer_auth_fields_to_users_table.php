<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->unique()->after('name');
            $table->string('profile_photo')->nullable()->after('email');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            $table->string('preferred_language', 10)->nullable()->after('password');
            $table->text('device_token')->nullable()->after('preferred_language');
            $table->boolean('location_permission')->default(false)->after('device_token');
            $table->string('referral_code', 30)->nullable()->unique()->after('location_permission');
            $table->string('referred_by_code', 30)->nullable()->after('referral_code');
            $table->string('google_id')->nullable()->unique()->after('referred_by_code');
            $table->string('apple_id')->nullable()->unique()->after('google_id');
            $table->timestamp('last_login_at')->nullable()->after('apple_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'profile_photo',
                'phone_verified_at',
                'preferred_language',
                'device_token',
                'location_permission',
                'referral_code',
                'referred_by_code',
                'google_id',
                'apple_id',
                'last_login_at',
            ]);
        });
    }
};
