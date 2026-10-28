<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\Process;

class RaceOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:race
        {product : Product id}
        {--processes=5 : Number of parallel orders}
        {--quantity=1 : Units per order}
        {--email= : Use one customer email for every order (default: a different email per process)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fire parallel orders for one product to show that stock is never oversold';

    public function handle(): int
    {
        $product = Product::findOrFail($this->argument('product'));
        $processes = max(1, (int) $this->option('processes'));
        $quantity = max(1, (int) $this->option('quantity'));

        $this->info("{$product->name} ({$product->code}): {$product->stock_on_hand} in stock. Launching {$processes} parallel orders of {$quantity}...");

        // Every child boots first, then waits for the same instant before ordering.
        $startAt = microtime(true) + 2 + $processes * 0.15;

        $results = Process::pool(function (Pool $pool) use ($product, $processes, $quantity, $startAt) {
            foreach (range(1, $processes) as $i) {
                $pool->path(base_path())->timeout(120)->command([
                    PHP_BINARY, 'artisan', 'orders:place', $product->id, $quantity,
                    '--email='.($this->option('email') ?: "race{$i}@example.com"),
                    '--name=Race Tester '.$i,
                    '--start-at='.sprintf('%.4f', $startAt),
                ]);
            }
        })->start()->wait();

        $counts = ['created' => 0, 'rejected' => 0, 'errors' => 0];
        $rows = [];

        foreach ($results->collect()->values() as $i => $result) {
            $outcome = match ($result->exitCode()) {
                self::SUCCESS => 'created',
                PlaceOrderCommand::REJECTED => 'rejected',
                default => 'errors',
            };
            $counts[$outcome]++;
            $rows[] = [$i + 1, $outcome, trim($result->output()) ?: trim($result->errorOutput())];
        }

        $this->table(['#', 'Outcome', 'Output'], $rows);
        $this->info(sprintf(
            '%d created, %d rejected, %d errors. Final stock: %d.',
            $counts['created'], $counts['rejected'], $counts['errors'], $product->refresh()->stock_on_hand,
        ));

        return $counts['errors'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
