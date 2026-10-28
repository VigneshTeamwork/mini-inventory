<?php

namespace App\DTOs;

/**
 * Computed amounts for one order line, in paise.
 */
final readonly class LineAmounts
{
    public function __construct(
        public int $subtotal,
        public int $tax,
        public int $total,
    ) {}
}
