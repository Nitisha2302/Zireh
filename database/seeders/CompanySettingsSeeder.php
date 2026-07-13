<?php

namespace Database\Seeders;

use App\Helpers\SettingHelper;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class CompanySettingsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::updateOrCreate(
            ['key' => 'company_name'],
            ['value' => SettingHelper::DEFAULT_COMPANY_NAME]
        );

        SettingHelper::clearCache();
    }
}
