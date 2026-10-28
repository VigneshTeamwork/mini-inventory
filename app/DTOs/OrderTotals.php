<?php

namespace App\DTOs;

/**
 * Computed totals for a whole order, in paise.
 */
final readonly class OrderTotals
{
    /**
     * @param  list<LineAmounts>  $lines
     */
    public function __construct(
        public array $lines,
        public int $subtotal,
        public int $tax,
        public int $grandTotal,
    ) {}
}
