<?php

namespace App\DTOs;

final readonly class OrderLineData
{
    /**
     * @param  int  $index  Position of the line in the request, used for error keys (items.N.quantity).
     */
    public function __construct(
        public int $productId,
        public int $quantity,
        public int $index,
    ) {}
}
