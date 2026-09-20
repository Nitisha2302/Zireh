<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->string('contact_person')->nullable()->change();
            $table->string('contact_number', 30)->nullable()->change();
            $table->string('country')->nullable()->default(null)->change();
            $table->string('state')->nullable()->change();
            $table->string('city')->nullable()->change();
            $table->decimal('latitude', 10, 7)->nullable()->change();
            $table->decimal('longitude', 10, 7)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->string('contact_person')->nullable(false)->change();
            $table->string('contact_number', 30)->nullable(false)->change();
            $table->string('country')->nullable(false)->default('Tajikistan')->change();
            $table->string('state')->nullable(false)->change();
            $table->string('city')->nullable(false)->change();
            $table->decimal('latitude', 10, 7)->nullable(false)->change();
            $table->decimal('longitude', 10, 7)->nullable(false)->change();
        });
    }
};
