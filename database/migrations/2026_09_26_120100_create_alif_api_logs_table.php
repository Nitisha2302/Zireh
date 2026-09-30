<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alif_api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 20)->nullable();
            $table->string('payment_id', 64)->nullable();
            $table->string('account', 64)->nullable();
            $table->decimal('amount', 14, 2)->nullable();
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->boolean('authorized')->default(false);
            $table->boolean('is_successful')->default(false);
            $table->decimal('duration_ms', 10, 3)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_body')->nullable();
            $table->boolean('response_truncated')->default(false);
            $table->text('error_message')->nullable();
            $table->foreignId('alif_payment_id')->nullable()
                ->constrained('alif_payments')->nullOnDelete();
            $table->timestamps();

            $table->index(['created_at', 'id']);
            $table->index('action');
            $table->index('payment_id');
            $table->index('response_code');
            $table->index('is_successful');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alif_api_logs');
    }
};
