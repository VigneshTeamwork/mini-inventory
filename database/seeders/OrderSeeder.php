<?php

namespace Database\Seeders;

use App\Actions\CreateOrderAction;
use App\DTOs\OrderData;
use App\Models\Product;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * A few past orders so the history endpoint has data straight away.
     * They go through CreateOrderAction, so stock and the ledger stay consistent.
     */
    public function run(CreateOrderAction $createOrder): void
    {
        $ids = Product::query()->pluck('id', 'code');

        $orders = [
            ['thomas@example.com', [['COL-100', 2], ['PAR-010', 5]], '200'],
            ['thomas@example.com', [['RIC-005', 1], ['SUG-001', 2], ['TEA-250', 1]], null],
            ['priya@example.com', [['MLK-001', 1], ['NOO-070', 6]], '200'],
        ];

        foreach ($orders as [$email, $items, $tendered]) {
            $createOrder->execute(OrderData::fromArray([
                'customer' => ['email' => $email],
                'items' => array_map(fn ($item) => ['product_id' => $ids[$item[0]], 'quantity' => $item[1]], $items),
                'amount_tendered' => $tendered,
            ]));
        }
    }
}
