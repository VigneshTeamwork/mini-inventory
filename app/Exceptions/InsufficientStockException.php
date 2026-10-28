<?php

namespace App\Exceptions;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    /**
     * @param  array<string, string>  $errors  Messages keyed by request field, e.g. items.1.quantity.
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('Insufficient stock for one or more items.');
    }

    public static function messageFor(Product $product, int $requested): string
    {
        if ($product->stock_on_hand === 0) {
            return "{$product->name} ({$product->code}) is out of stock; {$requested} requested.";
        }

        $units = $product->stock_on_hand === 1 ? 'unit' : 'units';

        return "Only {$product->stock_on_hand} {$units} of {$product->name} ({$product->code}) available; {$requested} requested.";
    }

    /**
     * A rejected sale is an expected outcome, so log it as a warning instead of an error with a trace.
     */
    public function report(): void
    {
        Log::warning('order.stock_rejected', ['errors' => $this->errors]);
    }

    /**
     * Same envelope as Laravel's validation errors so clients handle one format.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => array_map(fn (string $message) => [$message], $this->errors),
        ], 422);
    }
}
