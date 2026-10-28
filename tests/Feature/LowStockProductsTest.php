<?php

use App\Models\Product;

beforeEach(function () {
    Product::factory()->create(['name' => 'Eggs', 'stock_on_hand' => 2]);
    Product::factory()->create(['name' => 'Milk', 'stock_on_hand' => 9]);
    Product::factory()->create(['name' => 'Jam', 'stock_on_hand' => 10]);
    Product::factory()->create(['name' => 'Rice', 'stock_on_hand' => 50]);
    Product::factory()->outOfStock()->create(['name' => 'Chocolate']);
});

it('lists products strictly below the configured threshold, lowest stock first', function () {
    $this->getJson('/api/products/low-stock')
        ->assertOk()
        ->assertJsonPath('meta.threshold', 10)
        ->assertJsonPath('data.*.name', ['Chocolate', 'Eggs', 'Milk'])
        ->assertJsonPath('data.*.stock_on_hand', [0, 2, 9])
        ->assertJsonStructure(['data' => [['id', 'code', 'name', 'price', 'tax_percent', 'stock_on_hand']]]);
});

it('reads the default threshold from config', function () {
    config(['inventory.low_stock_threshold' => 3]);

    $this->getJson('/api/products/low-stock')
        ->assertOk()
        ->assertJsonPath('meta.threshold', 3)
        ->assertJsonPath('data.*.name', ['Chocolate', 'Eggs']);
});

it('lets the caller override the threshold per request', function () {
    $this->getJson('/api/products/low-stock?threshold=11')
        ->assertOk()
        ->assertJsonPath('meta.threshold', 11)
        ->assertJsonPath('data.*.name', ['Chocolate', 'Eggs', 'Milk', 'Jam']);
});

it('returns nothing for a threshold of zero', function () {
    $this->getJson('/api/products/low-stock?threshold=0')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('rejects an invalid threshold', function (mixed $threshold) {
    $this->getJson("/api/products/low-stock?threshold={$threshold}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['threshold']);
})->with([-1, 'ten', 2.5, 100001]);

it('reflects stock sold through orders', function () {
    $rice = Product::where('name', 'Rice')->sole();

    createOrder([[$rice, 45]]);

    $this->getJson('/api/products/low-stock')
        ->assertJsonPath('data.*.name', ['Chocolate', 'Eggs', 'Rice', 'Milk']);
});
