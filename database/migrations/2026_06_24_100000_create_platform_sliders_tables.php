<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_sliders', function (Blueprint $table) {
            $table->id();
            $table->string('heading');
            $table->string('link')->nullable();
            $table->string('image');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('platform_slider_platform', function (Blueprint $table) {
            $table->foreignId('platform_slider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('platform_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['platform_slider_id', 'platform_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_slider_platform');
        Schema::dropIfExists('platform_sliders');
    }
};
