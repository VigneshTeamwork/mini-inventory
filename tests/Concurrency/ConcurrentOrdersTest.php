<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

/*
| These tests start real, separate PHP processes that place orders at the
| same instant through `orders:race`. They need a database with row locks
| that every process can see (MySQL/MariaDB), so data is committed for real
| instead of wrapped in a test transaction. Run with: composer test:mysql
*/

beforeEach(function () {
    if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
        $this->markTestSkipped('Needs MySQL/MariaDB: run `composer test:mysql`.');
    }

    $this->artisan('migrate:fresh');
});

afterEach(function () {
    if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
        $this->artisan('migrate:fresh');
    }
});

it('sells the last unit exactly once when orders arrive together', function () {
    $product = Product::factory()->create(['stock_on_hand' => 1]);

    $this->artisan('orders:race', ['product' => $product->id, '--processes' => 5])
        ->expectsOutputToContain('1 created, 4 rejected, 0 errors. Final stock: 0.')
        ->assertSuccessful();

    expect($product->fresh()->stock_on_hand)->toBe(0)
        ->and(Order::count())->toBe(1)
        ->and(StockMovement::where('reason', StockMovement::REASON_SALE)->count())->toBe(1);
});

it('never oversells when many orders compete for a few units', function () {
    $product = Product::factory()->create(['stock_on_hand' => 3]);

    $this->artisan('orders:race', ['product' => $product->id, '--processes' => 8])
        ->expectsOutputToContain('3 created, 5 rejected, 0 errors. Final stock: 0.')
        ->assertSuccessful();

    expect($product->fresh()->stock_on_hand)->toBe(0)
        ->and(Order::count())->toBe(3)
        ->and((int) DB::table('order_items')->sum('quantity'))->toBe(3);
});

it('creates a new customer only once when their first orders race', function () {
    $product = Product::factory()->create(['stock_on_hand' => 10]);

    $this->artisan('orders:race', ['product' => $product->id, '--processes' => 4, '--email' => 'same.person@example.com'])
        ->expectsOutputToContain('4 created, 0 rejected, 0 errors. Final stock: 6.')
        ->assertSuccessful();

    expect(Customer::where('email', 'same.person@example.com')->count())->toBe(1)
        ->and(Order::count())->toBe(4);
});
