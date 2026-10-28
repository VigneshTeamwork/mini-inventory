<?php

use App\Jobs\SendOrderConfirmation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();

    $this->toothpaste = Product::factory()->create([
        'code' => 'COL-100', 'name' => 'Colgate Toothpaste', 'price' => '50.00', 'tax_percent' => 8, 'stock_on_hand' => 10,
    ]);
    $this->biscuit = Product::factory()->create([
        'code' => 'PAR-010', 'name' => 'Parle-G Biscuit', 'price' => '10.00', 'tax_percent' => 8, 'stock_on_hand' => 5,
    ]);
    $this->thomas = Customer::factory()->create(['name' => 'Thomas', 'email' => 'thomas@example.com']);
});

it('creates an order, computes totals, deducts stock and queues the confirmation', function () {
    $response = placeOrder([[$this->toothpaste, 2], [$this->biscuit, 5]], extra: ['amount_tendered' => 200]);

    $response->assertCreated()
        ->assertJsonStructure(['data' => [
            'order_number', 'status', 'placed_at', 'bill_url',
            'customer' => ['id', 'name', 'email'],
            'items' => [['product_id', 'code', 'name', 'unit_price', 'tax_percent', 'quantity', 'line_subtotal', 'line_tax', 'line_total']],
            'subtotal', 'tax_total', 'grand_total', 'amount_tendered', 'change_due', 'change_breakdown',
        ]])
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.customer.email', 'thomas@example.com')
        ->assertJsonPath('data.items.0.line_subtotal', '100.00')
        ->assertJsonPath('data.items.0.line_tax', '8.00')
        ->assertJsonPath('data.items.0.line_total', '108.00')
        ->assertJsonPath('data.items.1.line_total', '54.00')
        ->assertJsonPath('data.subtotal', '150.00')
        ->assertJsonPath('data.tax_total', '12.00')
        ->assertJsonPath('data.grand_total', '162.00')
        ->assertJsonPath('data.amount_tendered', '200.00')
        ->assertJsonPath('data.change_due', '38.00')
        ->assertJsonPath('data.change_breakdown', [
            ['denomination' => 20, 'count' => 1], ['denomination' => 10, 'count' => 1],
            ['denomination' => 5, 'count' => 1], ['denomination' => 2, 'count' => 1], ['denomination' => 1, 'count' => 1],
        ]);

    $order = Order::sole();
    expect($response->json('data.order_number'))->toBe($order->order_number)
        ->and($order->order_number)->toMatch('/^ORD-\d{8}-\d{6}$/');

    expect($this->toothpaste->fresh()->stock_on_hand)->toBe(8)
        ->and($this->biscuit->fresh()->stock_on_hand)->toBe(0); // ordering exactly what is left is allowed

    $this->assertDatabaseCount('order_items', 2);
    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $this->biscuit->id, 'order_id' => $order->id, 'change' => -5, 'balance_after' => 0, 'reason' => StockMovement::REASON_SALE,
    ]);

    Queue::assertPushed(SendOrderConfirmation::class, fn ($job) => $job->order->is($order));
});

it('creates a new customer when the email is unknown', function () {
    placeOrder([[$this->toothpaste, 1]], email: 'New.Person@Example.com', name: 'New Person')
        ->assertCreated()
        ->assertJsonPath('data.customer.email', 'new.person@example.com')
        ->assertJsonPath('data.customer.name', 'New Person');

    $this->assertDatabaseHas('customers', ['email' => 'new.person@example.com', 'name' => 'New Person']);
});

it('reuses an existing customer without overwriting their name', function () {
    placeOrder([[$this->toothpaste, 1]], email: 'THOMAS@example.com', name: 'Somebody Else')
        ->assertCreated()
        ->assertJsonPath('data.customer.id', $this->thomas->id)
        ->assertJsonPath('data.customer.name', 'Thomas');

    expect(Customer::count())->toBe(1);
});

it('lets returning customers order without sending a name', function () {
    placeOrder([[$this->toothpaste, 1]], name: null)->assertCreated();
});

it('merges repeated product lines into one order line', function () {
    placeOrder([[$this->toothpaste, 2], [$this->toothpaste, 3]])
        ->assertCreated()
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.quantity', 5);

    expect($this->toothpaste->fresh()->stock_on_hand)->toBe(5);
});

it('rounds tax per line, half-up', function () {
    $odd = Product::factory()->create(['price' => '33.33', 'tax_percent' => 18, 'stock_on_hand' => 10]);

    placeOrder([[$odd, 3]])
        ->assertCreated()
        ->assertJsonPath('data.items.0.line_subtotal', '99.99')
        ->assertJsonPath('data.items.0.line_tax', '18.00')
        ->assertJsonPath('data.grand_total', '117.99');
});

it('accepts an exact cash payment with no change', function () {
    placeOrder([[$this->toothpaste, 1]], extra: ['amount_tendered' => '54.00'])
        ->assertCreated()
        ->assertJsonPath('data.change_due', '0.00')
        ->assertJsonPath('data.change_breakdown', []);
});

it('leaves payment fields empty when no cash amount is given', function () {
    placeOrder([[$this->toothpaste, 1]])
        ->assertCreated()
        ->assertJsonPath('data.amount_tendered', null)
        ->assertJsonPath('data.change_due', null)
        ->assertJsonPath('data.change_breakdown', null);
});

it('snapshots prices so later catalog changes do not alter past orders', function () {
    placeOrder([[$this->toothpaste, 2]])->assertCreated();

    $this->toothpaste->update(['price' => '99.00', 'tax_percent' => 18, 'name' => 'Renamed']);

    $this->getJson('/api/customers/thomas@example.com/orders')
        ->assertOk()
        ->assertJsonPath('data.0.items.0.name', 'Colgate Toothpaste')
        ->assertJsonPath('data.0.items.0.unit_price', '50.00')
        ->assertJsonPath('data.0.items.0.tax_percent', '8.00')
        ->assertJsonPath('data.0.grand_total', '108.00');
});
