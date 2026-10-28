<?php

use App\Actions\CreateOrderAction;
use App\DTOs\OrderData;
use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->toothpaste = Product::factory()->create([
        'code' => 'COL-100', 'name' => 'Colgate Toothpaste', 'price' => '50.00', 'tax_percent' => 8, 'stock_on_hand' => 10,
    ]);
    $this->biscuit = Product::factory()->create([
        'code' => 'PAR-010', 'name' => 'Parle-G Biscuit', 'price' => '10.00', 'tax_percent' => 8, 'stock_on_hand' => 5,
    ]);
});

/**
 * Nothing may change when an order is refused.
 */
function assertNothingPersisted(array $stock): void
{
    test()->assertDatabaseCount('orders', 0);
    test()->assertDatabaseCount('order_items', 0);
    test()->assertDatabaseCount('stock_movements', 0);

    foreach ($stock as [$product, $expected]) {
        expect($product->fresh()->stock_on_hand)->toBe($expected);
    }
}

it('rejects an order for more than the stock on hand', function () {
    Queue::fake();

    placeOrder([[$this->biscuit, 6]])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Only 5 units of Parle-G Biscuit (PAR-010) available; 6 requested.')
        ->assertJsonValidationErrors(['items.0.quantity' => 'Only 5 units of Parle-G Biscuit (PAR-010) available; 6 requested.']);

    assertNothingPersisted([[$this->biscuit, 5]]);
    Queue::assertNothingPushed();
});

it('rejects the whole order when only one line is short', function () {
    Queue::fake();

    placeOrder([[$this->toothpaste, 2], [$this->biscuit, 6]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items.1.quantity'])
        ->assertJsonMissingValidationErrors(['items.0.quantity']);

    assertNothingPersisted([[$this->toothpaste, 10], [$this->biscuit, 5]]);
    Queue::assertNothingPushed();
});

it('reports an out-of-stock product clearly', function () {
    $chocolate = Product::factory()->outOfStock()->create(['code' => 'CHO-050', 'name' => 'Chocolate Bar']);

    placeOrder([[$chocolate, 1]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items.0.quantity' => 'Chocolate Bar (CHO-050) is out of stock; 1 requested.']);
});

it('checks repeated product lines against their combined quantity', function () {
    placeOrder([[$this->biscuit, 3], [$this->toothpaste, 1], [$this->biscuit, 3]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items.0.quantity' => 'Only 5 units of Parle-G Biscuit (PAR-010) available; 6 requested.']);

    assertNothingPersisted([[$this->biscuit, 5], [$this->toothpaste, 10]]);
});

it('re-checks stock under the row lock when validation passed on stale data', function () {
    Queue::fake();

    // Simulates another till selling the last units after this request was
    // validated but before CreateOrderAction locked the row.
    $this->app->afterResolving(StoreOrderRequest::class, function () {
        Product::whereKey($this->biscuit->id)->update(['stock_on_hand' => 1]);
    });

    placeOrder([[$this->biscuit, 2]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items.0.quantity' => 'Only 1 unit of Parle-G Biscuit (PAR-010) available; 2 requested.']);

    assertNothingPersisted([[$this->biscuit, 1]]);
    Queue::assertNothingPushed();
});

it('throws from the action itself when stock is short, whatever the caller', function () {
    $data = OrderData::fromArray(orderPayload([[$this->biscuit, 99]]));

    expect(fn () => app(CreateOrderAction::class)->execute($data))
        ->toThrow(InsufficientStockException::class);

    assertNothingPersisted([[$this->biscuit, 5]]);
});

it('rolls everything back if a later step fails mid-transaction', function () {
    Queue::fake();
    OrderItem::created(function (OrderItem $item) {
        if ($item->product_id === $this->biscuit->id) {
            throw new RuntimeException('Simulated failure while writing the second line');
        }
    });

    expect(fn () => createOrder([[$this->toothpaste, 2], [$this->biscuit, 1]], email: 'brand.new@example.com', name: 'Brand New'))
        ->toThrow(RuntimeException::class);

    // The first line's stock deduction and the new customer are undone too.
    assertNothingPersisted([[$this->toothpaste, 10], [$this->biscuit, 5]]);
    expect(Customer::count())->toBe(0);
    Queue::assertNothingPushed();
});

it('only queues the confirmation once the transaction commits', function () {
    config(['queue.default' => 'database']);

    // An enclosing transaction that rolls back must take the queued job with it.
    try {
        DB::transaction(function () {
            createOrder([[$this->toothpaste, 1]]);
            throw new RuntimeException('Outer failure');
        });
    } catch (RuntimeException) {
        // expected
    }

    $this->assertDatabaseCount('jobs', 0);
    assertNothingPersisted([[$this->toothpaste, 10]]);

    createOrder([[$this->toothpaste, 1]]);

    $this->assertDatabaseCount('jobs', 1);
});

it('rejects a cash amount below the grand total and persists nothing', function () {
    placeOrder([[$this->toothpaste, 2]], extra: ['amount_tendered' => 100])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount_tendered' => 'Amount given (100.00) is less than the grand total (108.00).']);

    assertNothingPersisted([[$this->toothpaste, 10]]);
});
