<?php

namespace App\DTOs;

use App\Models\Customer;

final readonly class OrderData
{
    /**
     * @param  list<OrderLineData>  $lines
     */
    public function __construct(
        public string $email,
        public ?string $name,
        public array $lines,
        public ?string $amountTendered = null,
    ) {}

    /**
     * Build from validated input. Repeated product lines are merged into
     * the first occurrence so stock is checked against the combined quantity.
     *
     * @param  array{customer: array{email: string, name?: string|null}, items: list<array{product_id: int|string, quantity: int|string}>, amount_tendered?: int|float|string|null}  $input
     */
    public static function fromArray(array $input): self
    {
        $lines = [];

        foreach (array_values($input['items']) as $index => $item) {
            $productId = (int) $item['product_id'];
            $quantity = (int) $item['quantity'];

            $lines[$productId] = isset($lines[$productId])
                ? new OrderLineData($productId, $lines[$productId]->quantity + $quantity, $lines[$productId]->index)
                : new OrderLineData($productId, $quantity, $index);
        }

        $name = trim((string) ($input['customer']['name'] ?? ''));
        $tendered = $input['amount_tendered'] ?? null;

        return new self(
            email: Customer::normalizeEmail($input['customer']['email']),
            name: $name === '' ? null : $name,
            lines: array_values($lines),
            amountTendered: $tendered === null ? null : (string) $tendered,
        );
    }

    /**
     * @return list<int>
     */
    public function productIds(): array
    {
        return array_map(fn (OrderLineData $line) => $line->productId, $this->lines);
    }
}
