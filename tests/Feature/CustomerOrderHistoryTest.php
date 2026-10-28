<?php

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    $this->product = Product::factory()->create(['price' => '10.00', 'tax_percent' => 0, 'stock_on_hand' => 100]);
    $this->thomas = Customer::factory()->create(['name' => 'Thomas', 'email' => 'thomas@example.com']);
});

it('returns a customer\'s orders newest first, with their lines', function () {
    $this->travelTo(now()->subDays(2));
    $older = createOrder([[$this->product, 1]]);
    $this->travelBack();
    $newer = createOrder([[$this->product, 3]]);

    $this->getJson('/api/customers/thomas@example.com/orders')
        ->assertOk()
        ->assertJsonPath('customer.email', 'thomas@example.com')
        ->assertJsonPath('customer.name', 'Thomas')
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.order_number', $newer->order_number)
        ->assertJsonPath('data.0.items.0.quantity', 3)
        ->assertJsonPath('data.0.grand_total', '30.00')
        ->assertJsonPath('data.1.order_number', $older->order_number)
        ->assertJsonPath('meta.total', 2);
});

it('does not include other customers\' orders', function () {
    createOrder([[$this->product, 1]]);
    createOrder([[$this->product, 1]], email: 'priya@example.com', name: 'Priya');

    $this->getJson('/api/customers/priya@example.com/orders')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('customer.name', 'Priya');
});

it('matches the email case-insensitively', function () {
    createOrder([[$this->product, 1]]);

    $this->getJson('/api/customers/'.rawurlencode('Thomas@EXAMPLE.com').'/orders')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('handles emails with plus signs and dots', function () {
    createOrder([[$this->product, 1]], email: 'first.last+shop@example.co.in', name: 'Plus');

    $this->getJson('/api/customers/'.rawurlencode('first.last+shop@example.co.in').'/orders')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('returns an empty list for a customer with no orders yet', function () {
    $this->getJson('/api/customers/thomas@example.com/orders')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('returns 404 for an unknown email', function () {
    $this->getJson('/api/customers/nobody@example.com/orders')
        ->assertNotFound()
        ->assertJsonPath('message', 'Customer not found.');
});

it('paginates', function () {
    foreach (range(1, 3) as $i) {
        createOrder([[$this->product, 1]]);
    }

    $this->getJson('/api/customers/thomas@example.com/orders?per_page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.last_page', 2);

    $this->getJson('/api/customers/thomas@example.com/orders?per_page=2&page=2')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('rejects an invalid page size', function (mixed $perPage) {
    $this->getJson("/api/customers/thomas@example.com/orders?per_page={$perPage}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['per_page']);
})->with([0, 51, 'all']);
