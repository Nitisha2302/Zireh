<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('news')) {
            return;
        }

        foreach (['title', 'description'] as $column) {
            if (! Schema::hasColumn('news', $column)) {
                continue;
            }

            foreach (DB::table('news')->get(['id', $column]) as $item) {
                $value = $item->{$column};
                $decoded = json_decode($value, true);

                if (is_array($decoded)) {
                    continue;
                }

                DB::table('news')->where('id', $item->id)->update([
                    $column => json_encode([
                        'en' => $value,
                        'ru' => $value,
                        'tg' => $value,
                    ]),
                ]);
            }

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE news MODIFY `{$column}` JSON NOT NULL");
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('news')) {
            return;
        }

        foreach (['title', 'description'] as $column) {
            if (! Schema::hasColumn('news', $column)) {
                continue;
            }

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $type = $column === 'title' ? 'VARCHAR(255) NOT NULL' : 'TEXT NOT NULL';
                DB::statement("ALTER TABLE news MODIFY `{$column}` {$type}");
            }

            foreach (DB::table('news')->get(['id', $column]) as $item) {
                $decoded = json_decode($item->{$column}, true);

                if (! is_array($decoded)) {
                    continue;
                }

                DB::table('news')->where('id', $item->id)->update([
                    $column => $decoded['en'] ?? reset($decoded) ?: '',
                ]);
            }
        }
    }
};
