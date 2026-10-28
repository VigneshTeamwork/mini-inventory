<?php

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    $this->product = Product::factory()->create(['stock_on_hand' => 10]);
    Customer::factory()->create(['email' => 'thomas@example.com']);
});

it('rejects malformed orders', function (Closure $payload, string $field) {
    $this->postJson('/api/orders', $payload($this->product))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);

    $this->assertDatabaseCount('orders', 0);
    expect($this->product->fresh()->stock_on_hand)->toBe(10);
    Queue::assertNothingPushed();
})->with([
    'no customer' => [fn ($p) => ['items' => [['product_id' => $p->id, 'quantity' => 1]]], 'customer'],
    'no email' => [fn ($p) => ['customer' => ['name' => 'X'], 'items' => [['product_id' => $p->id, 'quantity' => 1]]], 'customer.email'],
    'invalid email' => [fn ($p) => orderPayload([[$p, 1]], email: 'not-an-email'), 'customer.email'],
    'new customer without a name' => [fn ($p) => orderPayload([[$p, 1]], email: 'stranger@example.com', name: null), 'customer.name'],
    'new customer with a blank name' => [fn ($p) => orderPayload([[$p, 1]], email: 'stranger@example.com', name: '   '), 'customer.name'],
    'name too long' => [fn ($p) => orderPayload([[$p, 1]], email: 'stranger@example.com', name: str_repeat('a', 151)), 'customer.name'],
    'no items' => [fn ($p) => orderPayload([]), 'items'],
    'items is not a list' => [fn ($p) => ['customer' => ['email' => 'thomas@example.com'], 'items' => 'abc'], 'items'],
    'more than 50 lines' => [fn ($p) => orderPayload(array_fill(0, 51, [$p, 1])), 'items'],
    'unknown product' => [fn ($p) => ['customer' => ['email' => 'thomas@example.com'], 'items' => [['product_id' => 999999, 'quantity' => 1]]], 'items.0.product_id'],
    'missing product id' => [fn ($p) => ['customer' => ['email' => 'thomas@example.com'], 'items' => [['quantity' => 1]]], 'items.0.product_id'],
    'missing quantity' => [fn ($p) => ['customer' => ['email' => 'thomas@example.com'], 'items' => [['product_id' => $p->id]]], 'items.0.quantity'],
    'zero quantity' => [fn ($p) => orderPayload([[$p, 0]]), 'items.0.quantity'],
    'negative quantity' => [fn ($p) => orderPayload([[$p, -2]]), 'items.0.quantity'],
    'decimal quantity' => [fn ($p) => ['customer' => ['email' => 'thomas@example.com'], 'items' => [['product_id' => $p->id, 'quantity' => 1.5]]], 'items.0.quantity'],
    'non-numeric quantity' => [fn ($p) => ['customer' => ['email' => 'thomas@example.com'], 'items' => [['product_id' => $p->id, 'quantity' => 'two']]], 'items.0.quantity'],
    'quantity above the per-line maximum' => [fn ($p) => orderPayload([[$p, 1001]]), 'items.0.quantity'],
    'negative amount tendered' => [fn ($p) => orderPayload([[$p, 1]], extra: ['amount_tendered' => -5]), 'amount_tendered'],
    'non-numeric amount tendered' => [fn ($p) => orderPayload([[$p, 1]], extra: ['amount_tendered' => 'lots']), 'amount_tendered'],
    'amount tendered with 3 decimals' => [fn ($p) => orderPayload([[$p, 1]], extra: ['amount_tendered' => '100.005']), 'amount_tendered'],
]);

it('reports the error on the offending line only', function () {
    $other = Product::factory()->create();

    $this->postJson('/api/orders', [
        'customer' => ['email' => 'thomas@example.com'],
        'items' => [['product_id' => $other->id, 'quantity' => 1], ['product_id' => $this->product->id, 'quantity' => 0]],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items.1.quantity'])
        ->assertJsonMissingValidationErrors(['items.0.quantity', 'items.0.product_id']);
});

it('answers with JSON even when the client does not ask for it', function () {
    $this->post('/api/orders', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['customer', 'items']);
});
