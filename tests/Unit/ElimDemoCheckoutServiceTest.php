<?php

use App\Helpers\SettingHelper;
use App\Models\Setting;
use App\Services\Elim\ElimDemoCheckoutService;
use App\Support\Elim\ElimApiConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('detects demo mode from common config values', function (mixed $value, bool $expected) {
    config(['services.elim.demo_mode' => $value]);

    expect(app(ElimDemoCheckoutService::class)->isEnabled())->toBe($expected);
})->with([
    'boolean true' => [true, true],
    'boolean false' => [false, false],
    'string true' => ['true', true],
    'string false' => ['false', false],
    'string 1' => ['1', true],
    'string 0' => ['0', false],
    'integer 1' => [1, true],
    'integer 0' => [0, false],
]);

it('prefers admin demo setting over env config', function () {
    config(['services.elim.demo_mode' => false]);

    Setting::create([
        'key' => ElimApiConfig::SETTING_DEMO_MODE,
        'value' => '1',
    ]);
    SettingHelper::clearCache();

    expect(app(ElimDemoCheckoutService::class)->isEnabled())->toBeTrue();
});

it('uses env fallback when admin demo setting is not saved', function () {
    config(['services.elim.demo_mode' => true]);

    expect(app(ElimDemoCheckoutService::class)->isEnabled())->toBeTrue();
});
