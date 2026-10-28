<?php

use App\Services\OrderCalculator;

beforeEach(fn () => $this->calculator = new OrderCalculator);

it('prices a line as tax-exclusive price times quantity plus tax', function () {
    $line = $this->calculator->line('50.00', '8.00', 2);

    expect($line->subtotal)->toBe(10000)
        ->and($line->tax)->toBe(800)
        ->and($line->total)->toBe(10800);
});

it('rounds tax half-up per line', function (string $price, string $tax, int $qty, int $expectedTax) {
    expect($this->calculator->line($price, $tax, $qty)->tax)->toBe($expectedTax);
})->with([
    '33.33 x 3 @ 18% = 17.9982 → 18.00' => ['33.33', '18.00', 3, 1800],
    '0.25 @ 10% = 0.025 → 0.03 (half rounds up)' => ['0.25', '10.00', 1, 3],
    '0.24 @ 10% = 0.024 → 0.02' => ['0.24', '10.00', 1, 2],
    '19.99 x 7 @ 12% = 16.7916 → 16.79' => ['19.99', '12.00', 7, 1679],
    'fractional rate 2.5%' => ['100.00', '2.50', 1, 250],
]);

it('charges no tax at 0%', function () {
    $line = $this->calculator->line('45.00', '0', 4);

    expect($line->tax)->toBe(0)->and($line->total)->toBe(18000);
});

it('sums order totals from the rounded lines', function () {
    $totals = $this->calculator->totals([
        ['unit_price' => '50.00', 'tax_percent' => '8', 'quantity' => 2],
        ['unit_price' => '10.00', 'tax_percent' => '8', 'quantity' => 5],
        ['unit_price' => '33.33', 'tax_percent' => '18', 'quantity' => 3],
    ]);

    expect($totals->lines)->toHaveCount(3)
        ->and($totals->subtotal)->toBe(10000 + 5000 + 9999)
        ->and($totals->tax)->toBe(800 + 400 + 1800)
        ->and($totals->grandTotal)->toBe($totals->subtotal + $totals->tax);
});

it('avoids float drift on prices that are not exact in binary', function () {
    // 0.1 + 0.2 style errors would show up here with float maths.
    $totals = $this->calculator->totals(array_fill(0, 10, ['unit_price' => '0.10', 'tax_percent' => '0', 'quantity' => 3]));

    expect($totals->grandTotal)->toBe(300);
});
