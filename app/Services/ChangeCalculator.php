<?php

namespace App\Services;

/**
 * Works out the balance to return to a cash customer and how to pay it
 * out in Indian notes and coins.
 */
class ChangeCalculator
{
    /** @var list<int> Rupee denominations, largest first. */
    public const DENOMINATIONS = [500, 200, 100, 50, 20, 10, 5, 2, 1];

    public function changeDue(int $tenderedPaise, int $grandTotalPaise): int
    {
        return $tenderedPaise - $grandTotalPaise;
    }

    /**
     * Cash change is rounded to the nearest rupee (e.g. ₹22.80 → ₹23),
     * then split greedily into denominations.
     *
     * @return list<array{denomination: int, count: int}>
     */
    public function breakdown(int $changePaise): array
    {
        $rupees = intdiv(max($changePaise, 0) + 50, 100);
        $breakdown = [];

        foreach (self::DENOMINATIONS as $denomination) {
            if ($rupees < $denomination) {
                continue;
            }

            $breakdown[] = ['denomination' => $denomination, 'count' => intdiv($rupees, $denomination)];
            $rupees %= $denomination;
        }

        return $breakdown;
    }
}
