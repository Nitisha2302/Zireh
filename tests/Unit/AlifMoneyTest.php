<?php

use App\Support\Alif\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('normalizes every accepted amount shape to two decimals', function (mixed $input, string $expected) {
    expect(Money::tryParse($input)?->value())->toBe($expected);
})->with([
    'documented float' => [100.50, '100.50'],
    'integer' => [100, '100.00'],
    'string with two decimals' => ['100.50', '100.50'],
    'string with one decimal' => ['100.5', '100.50'],
    'padded string' => ['  10.25  ', '10.25'],
    'zero' => ['0', '0.00'],
    'database style balance' => ['1234.07', '1234.07'],
]);

it('rejects anything that is not a two-decimal money amount', function (mixed $input) {
    expect(Money::tryParse($input))->toBeNull();
})->with([
    'three decimals' => ['10.123'],
    'words' => ['a lot'],
    'empty string' => [''],
    'null' => [null],
    'boolean' => [true],
    'array' => [[10]],
    'scientific notation' => ['1e5'],
    'thousands separator' => ['1,000.00'],
    'trailing dot' => ['10.'],
]);

it('adds decimal strings without float drift', function () {
    $total = Money::zero();

    foreach (['0.10', '0.20', '0.07'] as $amount) {
        $total = $total->plus(Money::of($amount));
    }

    expect($total->value())->toBe('0.37');
});

it('stays exact across a hundred one-diram additions', function () {
    $total = Money::zero();

    for ($i = 0; $i < 100; $i++) {
        $total = $total->plus(Money::of('0.01'));
    }

    expect($total->value())->toBe('1.00')
        ->and($total->equals(Money::of('1.00')))->toBeTrue();
});

it('compares amounts without converting to float', function () {
    $ten = Money::of('10.00');
    $twenty = Money::of('20.00');

    expect($ten->isLessThan($twenty))->toBeTrue()
        ->and($twenty->isGreaterThan($ten))->toBeTrue()
        ->and($ten->equals(Money::of('10.0')))->toBeTrue()
        ->and($ten->equals(Money::of('10.01')))->toBeFalse();
});

it('knows whether an amount falls inside the configured range', function () {
    $min = Money::of('1.00');
    $max = Money::of('20000.00');

    expect(Money::of('1.00')->isWithin($min, $max))->toBeTrue()
        ->and(Money::of('20000.00')->isWithin($min, $max))->toBeTrue()
        ->and(Money::of('0.99')->isWithin($min, $max))->toBeFalse()
        ->and(Money::of('20000.01')->isWithin($min, $max))->toBeFalse();
});

it('treats zero and negative amounts as not positive', function () {
    expect(Money::of('0.00')->isPositive())->toBeFalse()
        ->and(Money::of('-5.00')->isPositive())->toBeFalse()
        ->and(Money::of('0.01')->isPositive())->toBeTrue();
});

it('throws when constructed from an unusable value', function () {
    expect(fn () => Money::of('not money'))->toThrow(InvalidArgumentException::class);
});
