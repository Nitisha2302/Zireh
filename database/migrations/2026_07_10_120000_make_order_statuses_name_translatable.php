<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_statuses') || ! Schema::hasColumn('order_statuses', 'name')) {
            return;
        }

        foreach (DB::table('order_statuses')->get(['id', 'name']) as $status) {
            $decoded = json_decode($status->name, true);

            if (is_array($decoded)) {
                continue;
            }

            DB::table('order_statuses')->where('id', $status->id)->update([
                'name' => json_encode([
                    'en' => $status->name,
                    'ru' => $status->name,
                    'tg' => $status->name,
                ]),
            ]);
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE order_statuses MODIFY `name` JSON NOT NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_statuses') || ! Schema::hasColumn('order_statuses', 'name')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE order_statuses MODIFY `name` VARCHAR(255) NOT NULL');
        }

        foreach (DB::table('order_statuses')->get(['id', 'name']) as $status) {
            $decoded = json_decode($status->name, true);

            if (! is_array($decoded)) {
                continue;
            }

            DB::table('order_statuses')->where('id', $status->id)->update([
                'name' => $decoded['en'] ?? reset($decoded) ?: '',
            ]);
        }
    }
};
