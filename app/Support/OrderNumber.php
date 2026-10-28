<?php

namespace App\Support;

use DateTimeInterface;

final class OrderNumber
{
    /**
     * Human-readable order number, e.g. ORD-20261028-000042.
     */
    public static function make(int $id, DateTimeInterface $placedAt): string
    {
        return sprintf('ORD-%s-%06d', $placedAt->format('Ymd'), $id);
    }
}
