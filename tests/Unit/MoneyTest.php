<?php

use App\Support\Money;
use App\Support\OrderNumber;

it('converts amounts to integer paise without float errors', function (string|int|float $amount, int $paise) {
    expect(Money::toPaise($amount))->toBe($paise);
})->with([
    ['10.10', 1010],
    ['0.29', 29],     // 0.29 * 100 = 28.999… in floats
    ['1.005', 101],
    [50, 5000],
    ['0', 0],
]);

it('converts percentages to basis points', function () {
    expect(Money::toBasisPoints('18.00'))->toBe(1800)
        ->and(Money::toBasisPoints('2.5'))->toBe(250);
});

it('formats paise as a 2-decimal string', function (int $paise, string $formatted) {
    expect(Money::format($paise))->toBe($formatted);
})->with([
    [16200, '162.00'],
    [5, '0.05'],
    [0, '0.00'],
    [-150, '-1.50'],
    [123456789, '1234567.89'],
]);

it('builds a readable order number from the id and date', function () {
    expect(OrderNumber::make(42, new DateTimeImmutable('2026-10-28')))->toBe('ORD-20261028-000042');
});
