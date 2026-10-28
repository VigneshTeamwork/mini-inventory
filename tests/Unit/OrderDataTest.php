<?php

use App\DTOs\OrderData;

it('merges repeated products into the first line and keeps its index', function () {
    $data = OrderData::fromArray([
        'customer' => ['email' => 'a@example.com', 'name' => 'A'],
        'items' => [
            ['product_id' => 7, 'quantity' => 2],
            ['product_id' => 3, 'quantity' => 1],
            ['product_id' => '7', 'quantity' => '4'],
        ],
    ]);

    expect($data->lines)->toHaveCount(2)
        ->and($data->lines[0]->productId)->toBe(7)
        ->and($data->lines[0]->quantity)->toBe(6)
        ->and($data->lines[0]->index)->toBe(0)
        ->and($data->lines[1]->index)->toBe(1)
        ->and($data->productIds())->toBe([7, 3]);
});

it('normalizes the email and treats a blank name as missing', function () {
    $data = OrderData::fromArray([
        'customer' => ['email' => '  Thomas@Example.COM ', 'name' => '   '],
        'items' => [['product_id' => 1, 'quantity' => 1]],
    ]);

    expect($data->email)->toBe('thomas@example.com')
        ->and($data->name)->toBeNull()
        ->and($data->amountTendered)->toBeNull();
});

it('keeps the amount tendered as a string', function () {
    $data = OrderData::fromArray([
        'customer' => ['email' => 'a@example.com'],
        'items' => [['product_id' => 1, 'quantity' => 1]],
        'amount_tendered' => 250,
    ]);

    expect($data->amountTendered)->toBe('250');
});
