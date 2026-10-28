<?php

namespace App\Console\Commands;

use App\Actions\CreateOrderAction;
use App\DTOs\OrderData;
use App\DTOs\OrderLineData;
use App\Exceptions\InsufficientStockException;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class PlaceOrderCommand extends Command
{
    /**
     * Exit code for an order refused for lack of stock (as opposed to a crash).
     */
    public const REJECTED = 2;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:place
        {product : Product id}
        {quantity=1 : Units to order}
        {--email=race@example.com : Customer email}
        {--name=Race Tester : Customer name, used if the customer is new}
        {--start-at= : Unix timestamp (float) to wait for, so parallel processes fire together}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Place a single-line order through CreateOrderAction (used by orders:race)';

    public function handle(CreateOrderAction $createOrder): int
    {
        if ($startAt = (float) $this->option('start-at')) {
            while (microtime(true) < $startAt) {
                usleep(500);
            }
        }

        $data = new OrderData(
            email: (string) $this->option('email'),
            name: (string) $this->option('name'),
            lines: [new OrderLineData((int) $this->argument('product'), (int) $this->argument('quantity'), 0)],
        );

        try {
            $order = $createOrder->execute($data);
        } catch (InsufficientStockException $e) {
            $this->line(json_encode(['status' => 'rejected', 'message' => Arr::first($e->errors)]));

            return self::REJECTED;
        }

        $this->line(json_encode(['status' => 'created', 'order_number' => $order->order_number]));

        return self::SUCCESS;
    }
}
