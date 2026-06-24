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
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('authenticatable');
            $table->string('guard', 50)->index();
            $table->string('login')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable()->index();
            $table->timestamp('login_at')->nullable()->index();
            $table->timestamp('logout_at')->nullable()->index();
            $table->timestamp('last_seen_at')->nullable();
            $table->text('last_activity_url')->nullable();
            $table->boolean('successful')->default(true)->index();
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['guard', 'authenticatable_type', 'authenticatable_id'], 'login_logs_guard_authenticatable_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};
