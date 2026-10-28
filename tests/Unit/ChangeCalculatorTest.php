<?php

use App\Services\ChangeCalculator;

beforeEach(fn () => $this->change = new ChangeCalculator);

it('computes the change due in paise', function () {
    expect($this->change->changeDue(25000, 22720))->toBe(2280);
});

it('breaks change into notes and coins, rounding to the nearest rupee', function (int $paise, array $expected) {
    $breakdown = collect($this->change->breakdown($paise))
        ->mapWithKeys(fn ($c) => [$c['denomination'] => $c['count']])
        ->all();

    expect($breakdown)->toBe($expected);
})->with([
    'wireframe: ₹22.80 → 20 + 2 + 1' => [2280, [20 => 1, 2 => 1, 1 => 1]],
    '₹38.00' => [3800, [20 => 1, 10 => 1, 5 => 1, 2 => 1, 1 => 1]],
    'every denomination once: ₹888' => [88800, [500 => 1, 200 => 1, 100 => 1, 50 => 1, 20 => 1, 10 => 1, 5 => 1, 2 => 1, 1 => 1]],
    'multiples: ₹1,000' => [100000, [500 => 2]],
    'exact payment' => [0, []],
    '₹0.49 rounds down to nothing' => [49, []],
    '₹0.50 rounds up to ₹1' => [50, [1 => 1]],
]);

it('never returns change for a negative balance', function () {
    expect($this->change->breakdown(-500))->toBe([]);
});
