<?php

namespace App\Actions;

use App\DTOs\OrderData;
use App\DTOs\OrderLineData;
use App\DTOs\OrderTotals;
use App\Exceptions\InsufficientStockException;
use App\Jobs\SendOrderConfirmation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\ChangeCalculator;
use App\Services\OrderCalculator;
use App\Support\Money;
use App\Support\OrderNumber;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CreateOrderAction
{
    public function __construct(
        private readonly OrderCalculator $calculator,
        private readonly ChangeCalculator $change,
    ) {}

    /**
     * Check stock, price the order, deduct stock and persist everything in one
     * transaction. Product rows are locked (SELECT ... FOR UPDATE) in id order,
     * so concurrent orders for the same product are serialized and cannot deadlock.
     *
     * @throws InsufficientStockException
     * @throws ValidationException
     */
    public function execute(OrderData $data): Order
    {
        $order = DB::transaction(function () use ($data) {
            $products = Product::query()
                ->whereIn('id', $data->productIds())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $this->ensureStockAvailable($data, $products);

            $totals = $this->calculator->totals(array_map(fn (OrderLineData $line) => [
                'unit_price' => $products[$line->productId]->price,
                'tax_percent' => $products[$line->productId]->tax_percent,
                'quantity' => $line->quantity,
            ], $data->lines));

            $changeDue = $this->changeDue($data, $totals);
            $customer = $this->resolveCustomer($data);
            $placedAt = now();

            $order = $customer->orders()->create([
                'subtotal' => Money::format($totals->subtotal),
                'tax_total' => Money::format($totals->tax),
                'grand_total' => Money::format($totals->grandTotal),
                'amount_tendered' => $data->amountTendered === null ? null : Money::format(Money::toPaise($data->amountTendered)),
                'change_due' => $changeDue === null ? null : Money::format($changeDue),
                'status' => Order::STATUS_CONFIRMED,
                'placed_at' => $placedAt,
            ]);
            $order->update(['order_number' => OrderNumber::make($order->id, $placedAt)]);

            foreach ($data->lines as $i => $line) {
                $product = $products[$line->productId];
                $amounts = $totals->lines[$i];

                $this->deductStock($product, $line);

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_code' => $product->code,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'tax_percent' => $product->tax_percent,
                    'quantity' => $line->quantity,
                    'line_subtotal' => Money::format($amounts->subtotal),
                    'line_tax' => Money::format($amounts->tax),
                    'line_total' => Money::format($amounts->total),
                ]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'order_id' => $order->id,
                    'change' => -$line->quantity,
                    'balance_after' => $product->stock_on_hand,
                    'reason' => StockMovement::REASON_SALE,
                ]);
            }

            // Queued only once the transaction commits; a rolled-back order sends nothing.
            SendOrderConfirmation::dispatch($order)->afterCommit();

            return $order;
        }, attempts: 3);

        Log::info('order.created', [
            'order_number' => $order->order_number,
            'customer_id' => $order->customer_id,
            'grand_total' => $order->grand_total,
        ]);

        return $order->load(['customer', 'items']);
    }

    /**
     * @param  Collection<int, Product>  $products  Locked rows keyed by id.
     *
     * @throws InsufficientStockException
     */
    private function ensureStockAvailable(OrderData $data, Collection $products): void
    {
        $errors = [];

        foreach ($data->lines as $line) {
            $product = $products->get($line->productId);

            if ($product === null) {
                $errors["items.{$line->index}.product_id"] = "Product #{$line->productId} is no longer available.";
            } elseif ($product->stock_on_hand < $line->quantity) {
                $errors["items.{$line->index}.quantity"] = InsufficientStockException::messageFor($product, $line->quantity);
            }
        }

        if ($errors !== []) {
            throw new InsufficientStockException($errors);
        }
    }

    /**
     * Conditional decrement as a safety net: even without the row lock, the
     * UPDATE only succeeds while enough stock remains.
     *
     * @throws InsufficientStockException
     */
    private function deductStock(Product $product, OrderLineData $line): void
    {
        $affected = Product::query()
            ->whereKey($product->id)
            ->where('stock_on_hand', '>=', $line->quantity)
            ->decrement('stock_on_hand', $line->quantity);

        if ($affected !== 1) {
            throw new InsufficientStockException([
                "items.{$line->index}.quantity" => InsufficientStockException::messageFor($product->refresh(), $line->quantity),
            ]);
        }

        $product->stock_on_hand -= $line->quantity;
        $product->syncOriginalAttribute('stock_on_hand');
    }

    /**
     * @throws ValidationException
     */
    private function changeDue(OrderData $data, OrderTotals $totals): ?int
    {
        if ($data->amountTendered === null) {
            return null;
        }

        $tendered = Money::toPaise($data->amountTendered);

        if ($tendered < $totals->grandTotal) {
            throw ValidationException::withMessages([
                'amount_tendered' => sprintf(
                    'Amount given (%s) is less than the grand total (%s).',
                    Money::format($tendered),
                    Money::format($totals->grandTotal),
                ),
            ]);
        }

        return $this->change->changeDue($tendered, $totals->grandTotal);
    }

    /**
     * Existing customers keep their stored name; new ones need a name.
     *
     * @throws ValidationException
     */
    private function resolveCustomer(OrderData $data): Customer
    {
        $customer = Customer::query()->byEmail($data->email)->first();

        if ($customer !== null) {
            return $customer;
        }

        if ($data->name === null) {
            throw ValidationException::withMessages([
                'customer.name' => 'The name field is required for a new customer.',
            ]);
        }

        try {
            // Savepoint, so a duplicate-key failure doesn't abort the outer transaction.
            return DB::transaction(fn () => Customer::create(['email' => $data->email, 'name' => $data->name]));
        } catch (UniqueConstraintViolationException) {
            // A concurrent order created this customer first; a locking read sees the committed row.
            return Customer::query()->byEmail($data->email)->sharedLock()->firstOrFail();
        }
    }
}
