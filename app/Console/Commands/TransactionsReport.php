<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Support\Money\Money;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Concurrency;

class TransactionsReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:report 
                            {--count= : Number of records to report (default: all transactions)}
                            {--workers=10 : Number of concurrent workers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a report of all transactions with total cost, price*quantity, and difference';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $chunkSize = 10000;
        $maxConcurrentProcesses = 10;
        $count = $this->option('count');
        
        $this->info('Generating transactions report...');
        $this->info("Max concurrent processes: {$maxConcurrentProcesses}");
        $this->newLine();

        $startTime = microtime(true);
        
        $totalCount = $count ?? Transaction::count();
        $totalChunks = (int) ceil($totalCount / $chunkSize);
        $chunks = min($totalChunks, $maxConcurrentProcesses);

        $this->info("Total transactions: " . number_format($totalCount));
        $this->info("Total chunks: {$totalChunks}");
        $this->newLine();

        // Create progress bar
        $bar = $this->output->createProgressBar($totalChunks);
        $bar->start();

        // Process in batches if we have more chunks than max processes
        $results = [];
        $processedChunks = 0;

        while ($processedChunks < $totalChunks) {
            $batchTasks = [];
            $batchSize = min($chunks, $totalChunks - $processedChunks);

            for ($i = 0; $i < $batchSize; $i++) {
                $offset = ($processedChunks + $i) * $chunkSize;
                // Use static closure to avoid serialization issues
                $batchTasks[] = static function () use ($offset, $chunkSize) {
                    return \App\Console\Commands\TransactionsReport::processChunkStatic($offset, $chunkSize);
                };
            }

            $batchResults = Concurrency::run($batchTasks);
            $results = array_merge($results, $batchResults);
            $processedChunks += $batchSize;
            
            $bar->advance($batchSize);
        }

        $bar->finish();
        $this->newLine(2);

        // Combine results from all chunks
        $totalCost = Money::parse(0);
        $totalPriceAndQuantity = Money::parse(0);

        foreach ($results as $result) {
            $totalCost->add($result['cost']);
            $totalPriceAndQuantity->add($result['priceAndQuantity']);
        }

        $costClone = clone $totalCost;
        $priceAndQuantityClone = clone $totalPriceAndQuantity;
        $difference = $costClone->subtract($priceAndQuantityClone);

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        // Display results in a table
        $this->info('Report Results:');
        $this->newLine();
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Cost', $totalCost->formatted()],
                ['Total Price × Quantity', $totalPriceAndQuantity->formatted()],
                ['Difference', $difference->formatted()],
                ['', ''],
                ['===================', '==================='],
                ['Total Transactions', number_format($totalCount)],
                ['', ''],
                ['Chunk Size', number_format($chunkSize)],
                ['Total Chunks', $totalChunks],
                ['Processed Chunks', $processedChunks],
                ['Max Concurrent Processes', $maxConcurrentProcesses],
                ['Processing Time', "{$duration} seconds"],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Process a chunk of transactions using chunkById for memory efficiency.
     * Static method to avoid serialization issues with Command instance.
     *
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public static function processChunkStatic(int $offset, int $limit): array
    {
        $cost = Money::parse(0);
        $priceAndQuantity = Money::parse(0);

        // Get starting ID for this offset
        $startId = Transaction::query()
            ->orderBy('id')
            ->offset($offset)
            ->limit(1)
            ->value('id');

        if ($startId === null) {
            return [
                'cost' => $cost,
                'priceAndQuantity' => $priceAndQuantity,
            ];
        }

        $processed = 0;
        $shouldStop = false;

        Transaction::query()
            ->where('id', '>=', $startId)
            ->orderBy('id')
            ->chunkById(10000, function ($transactions) use (&$cost, &$priceAndQuantity, &$processed, $limit, &$shouldStop) {
                if ($shouldStop) {
                    return false;
                }

                foreach ($transactions as $transaction) {
                    if ($processed >= $limit) {
                        $shouldStop = true;
                        return false; // Stop processing
                    }

                    $cost->add($transaction->cost);
                    $priceAndQuantity->add($transaction->price->multiply($transaction->quantity));
                    $processed++;
                }
            });

        return [
            'cost' => $cost,
            'priceAndQuantity' => $priceAndQuantity,
        ];
    }
}
