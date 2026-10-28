<?php

namespace App\Http\Resources;

use App\Models\Order;
use App\Services\ChangeCalculator;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'order_number' => $this->order_number,
            'status' => $this->status,
            'placed_at' => $this->placed_at->toIso8601String(),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'subtotal' => $this->subtotal,
            'tax_total' => $this->tax_total,
            'grand_total' => $this->grand_total,
            'amount_tendered' => $this->amount_tendered,
            'change_due' => $this->change_due,
            'change_breakdown' => $this->change_due === null
                ? null
                : app(ChangeCalculator::class)->breakdown(Money::toPaise($this->change_due)),
            'bill_url' => route('bills.show', $this->resource),
        ];
    }
}
