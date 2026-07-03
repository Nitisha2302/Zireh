<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse_name');
            $table->string('warehouse_code')->unique();
            $table->string('contact_person');
            $table->string('contact_number', 30);
            $table->string('email')->nullable();
            $table->string('country')->default('Tajikistan');
            $table->string('state');
            $table->string('city');
            $table->text('address');
            $table->string('postal_code', 20)->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index(['country', 'city']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
