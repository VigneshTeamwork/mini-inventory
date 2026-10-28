<?php

use App\Actions\CreateOrderAction;
use App\DTOs\OrderData;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests run inside a transaction that is rolled back after each
| test. Concurrency tests need committed data that other processes can
| see, so they manage the database themselves.
|
*/

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');
pest()->extend(TestCase::class)->in('Concurrency');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * @param  list<array{0: Product, 1: int}>  $lines
 * @param  array<string, mixed>  $extra
 */
function orderPayload(array $lines, string $email = 'thomas@example.com', ?string $name = 'Thomas', array $extra = []): array
{
    return [
        'customer' => ['email' => $email, 'name' => $name],
        'items' => array_map(fn (array $line) => ['product_id' => $line[0]->id, 'quantity' => $line[1]], $lines),
        ...$extra,
    ];
}

/**
 * @param  list<array{0: Product, 1: int}>  $lines
 * @param  array<string, mixed>  $extra
 */
function placeOrder(array $lines, string $email = 'thomas@example.com', ?string $name = 'Thomas', array $extra = []): TestResponse
{
    return test()->postJson('/api/orders', orderPayload($lines, $email, $name, $extra));
}

/**
 * Place an order through the action directly, bypassing HTTP validation.
 *
 * @param  list<array{0: Product, 1: int}>  $lines
 */
function createOrder(array $lines, string $email = 'thomas@example.com', ?string $name = 'Thomas', ?string $tendered = null): Order
{
    return app(CreateOrderAction::class)->execute(
        OrderData::fromArray(orderPayload($lines, $email, $name, ['amount_tendered' => $tendered]))
    );
}
