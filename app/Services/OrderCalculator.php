<?php

namespace App\Services;

use App\DTOs\LineAmounts;
use App\DTOs\OrderTotals;
use App\Support\Money;

/**
 * Pure pricing rules: prices are tax-exclusive, tax is rounded half-up
 * per line, and order totals are the sums of the line amounts.
 */
class OrderCalculator
{
    public function line(string|int|float $unitPrice, string|int|float $taxPercent, int $quantity): LineAmounts
    {
        $subtotal = Money::toPaise($unitPrice) * $quantity;

        // Half-up rounding of subtotal * bp / 10000 using integers only.
        $tax = intdiv($subtotal * Money::toBasisPoints($taxPercent) + 5000, 10000);

        return new LineAmounts($subtotal, $tax, $subtotal + $tax);
    }

    /**
     * @param  iterable<array{unit_price: string|int|float, tax_percent: string|int|float, quantity: int}>  $lines
     */
    public function totals(iterable $lines): OrderTotals
    {
        $amounts = [];
        $subtotal = 0;
        $tax = 0;

        foreach ($lines as $line) {
            $amount = $this->line($line['unit_price'], $line['tax_percent'], $line['quantity']);

            $amounts[] = $amount;
            $subtotal += $amount->subtotal;
            $tax += $amount->tax;
        }

        return new OrderTotals($amounts, $subtotal, $tax, $subtotal + $tax);
    }
}
