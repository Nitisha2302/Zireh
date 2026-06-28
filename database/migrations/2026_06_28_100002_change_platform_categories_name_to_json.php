<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_categories') || ! Schema::hasColumn('platform_categories', 'name')) {
            return;
        }

        foreach (DB::table('platform_categories')->get(['id', 'name']) as $category) {
            $decoded = json_decode($category->name, true);

            if (is_array($decoded)) {
                continue;
            }

            DB::table('platform_categories')->where('id', $category->id)->update([
                'name' => json_encode([
                    'en' => $category->name,
                    'ru' => $category->name,
                    'tg' => $category->name,
                ]),
            ]);
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE platform_categories MODIFY `name` JSON NOT NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('platform_categories') || ! Schema::hasColumn('platform_categories', 'name')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE platform_categories MODIFY `name` VARCHAR(255) NOT NULL');
        }

        foreach (DB::table('platform_categories')->get(['id', 'name']) as $category) {
            $decoded = json_decode($category->name, true);

            if (! is_array($decoded)) {
                continue;
            }

            DB::table('platform_categories')->where('id', $category->id)->update([
                'name' => $decoded['en'] ?? reset($decoded) ?: '',
            ]);
        }
    }
};
