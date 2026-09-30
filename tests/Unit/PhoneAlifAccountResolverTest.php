<?php

use App\Models\User;
use App\Services\Alif\PhoneAlifAccountResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function alifResolver(): PhoneAlifAccountResolver
{
    return app(PhoneAlifAccountResolver::class);
}

it('resolves every shape a customer might type at an alif terminal', function (string $account) {
    $user = User::factory()->create([
        'phone' => '+992901234567',
        'status' => User::STATUS_ACTIVE,
    ]);

    expect(alifResolver()->resolve($account)?->id)->toBe($user->id);
})->with([
    '+992901234567',
    '992901234567',
    '901234567',
    '+992 90 123 45 67',
    '992-90-123-45-67',
    '(992) 901 234 567',
]);

it('resolves against a phone stored without the country code', function (string $account) {
    $user = User::factory()->create([
        'phone' => '901234567',
        'status' => User::STATUS_ACTIVE,
    ]);

    expect(alifResolver()->resolve($account)?->id)->toBe($user->id);
})->with([
    '901234567',
    '992901234567',
    '+992901234567',
]);

it('returns nothing for an account nobody owns', function () {
    User::factory()->create(['phone' => '+992901234567']);

    expect(alifResolver()->resolve('992555555555'))->toBeNull();
});

it('returns nothing for blocked or inactive customers', function (string $status) {
    User::factory()->create([
        'phone' => '+992901234567',
        'status' => $status,
    ]);

    expect(alifResolver()->resolve('992901234567'))->toBeNull();
})->with([
    User::STATUS_BLOCKED,
    User::STATUS_INACTIVE,
]);

it('returns nothing for input that holds no digits', function (string $account) {
    User::factory()->create(['phone' => '+992901234567']);

    expect(alifResolver()->resolve($account))->toBeNull();
})->with([
    '',
    '   ',
    'not-a-number',
]);

it('does not treat a partial number as a match', function () {
    User::factory()->create([
        'phone' => '+992901234567',
        'status' => User::STATUS_ACTIVE,
    ]);

    expect(alifResolver()->resolve('9012345'))->toBeNull()
        ->and(alifResolver()->resolve('9920901234567'))->toBeNull();
});
