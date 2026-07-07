<?php

use App\Services\Elim\ElimDemoCheckoutService;
use Tests\TestCase;

uses(TestCase::class);

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
