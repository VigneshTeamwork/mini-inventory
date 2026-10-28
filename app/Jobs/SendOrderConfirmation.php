<?php

namespace App\Jobs;

use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmation implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    public function __construct(public Order $order) {}

    /**
     * Seconds to wait before each retry.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(): void
    {
        $order = $this->order->loadMissing(['customer', 'items']);

        Mail::to($order->customer->email, $order->customer->name)
            ->send(new OrderConfirmationMail($order));

        Log::info('order.confirmation_sent', [
            'order_number' => $order->order_number,
            'email' => $order->customer->email,
            'grand_total' => $order->grand_total,
        ]);
    }
}
