<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elim_api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('method', 10);
            $table->string('endpoint', 500);
            $table->string('source', 50)->default('api');
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->boolean('is_successful')->default(false);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_body')->nullable();
            $table->boolean('response_truncated')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'id']);
            $table->index('endpoint');
            $table->index('is_successful');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elim_api_logs');
    }
};
