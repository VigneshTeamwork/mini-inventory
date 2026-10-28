<?php

use App\Models\Product;
use Illuminate\Support\Facades\Queue;

it('shows the new-order counter screen', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Store Billing')
        ->assertSee('Low Stock Alert')
        ->assertSee('Amount Given by Customer')
        ->assertSee('Generate Bill');
});

it('shows a bill with lines, totals and change', function () {
    Queue::fake();
    $product = Product::factory()->create(['name' => 'Colgate Toothpaste', 'price' => '50.00', 'tax_percent' => 8, 'stock_on_hand' => 10]);
    $order = createOrder([[$product, 2]], tendered: '120');

    $this->get("/orders/{$order->order_number}?new=1")
        ->assertOk()
        ->assertSee($order->order_number)
        ->assertSee('Colgate Toothpaste')
        ->assertSee('₹108.00')
        ->assertSee('₹12.00')
        ->assertSee('1×10 + 1×2')
        ->assertSee('confirmation email');
});

it('downloads the bill as a PDF', function () {
    Queue::fake();
    $product = Product::factory()->create(['stock_on_hand' => 10]);
    $order = createOrder([[$product, 1]]);

    $response = $this->get("/orders/{$order->order_number}/pdf");

    $response->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF');
});

it('returns 404 for an unknown bill', function () {
    $this->get('/orders/ORD-20260101-999999')->assertNotFound();
});
