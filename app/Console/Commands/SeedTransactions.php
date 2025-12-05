<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;

class SeedTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:seed 
                            {--count=100000 : Number of records to seed}
                            {--workers=10 : Number of concurrent workers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed transaction records efficiently using factory and concurrency';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = (int) $this->option('count');
        $chunkSize = 10000;
        $maxWorkers = 10;

        $this->info("Starting to seed {$count} transaction records...");
        $this->info("Max concurrent workers: {$maxWorkers}");

        $startTime = microtime(true);
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $totalChunks = (int) ceil($count / $chunkSize);
        $processedChunks = 0;
        $seeded = 0;

        // Process chunks in batches with concurrency
        while ($processedChunks < $totalChunks) {
            $batchSize = min($maxWorkers, $totalChunks - $processedChunks);
            $tasks = [];

            // Create concurrent tasks for this batch
            for ($i = 0; $i < $batchSize; $i++) {
                $chunkIndex = $processedChunks + $i;
                $currentChunkSize = min($chunkSize, $count - ($chunkIndex * $chunkSize));
                
                if ($currentChunkSize > 0) {
                    // Capture variables directly to avoid serializing $this
                    $tasks[] = static function () use ($currentChunkSize) {
                        return \App\Console\Commands\SeedTransactions::seedChunkStatic($currentChunkSize);
                    };
                }
            }

            // Execute tasks concurrently
            $results = Concurrency::run($tasks);

            // Update progress
            foreach ($results as $result) {
                $seeded += $result;
                $bar->advance($result);
            }

            $processedChunks += $batchSize;

            // Show progress update
            $this->newLine();
            $this->info("Progress: {$seeded}/{$count} records seeded ({$processedChunks}/{$totalChunks} chunks)");
        }

        $bar->finish();
        $this->newLine(2);

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        $this->info("✓ Successfully seeded {$count} transaction records in {$duration} seconds");
        $this->info("Average: " . round($count / $duration, 0) . " records/second");

        return Command::SUCCESS;
    }

    /**
     * Seed a chunk of transactions using the factory.
     *
     * @param int $chunkSize
     * @param int $chunkIndex
     * @return int Number of records seeded
     */
    private function seedChunk(int $chunkSize, int $chunkIndex): int
    {
        return self::seedChunkStatic($chunkSize);
    }

    /**
     * Static method to seed a chunk of transactions using the factory.
     * This avoids serialization issues with Command instance.
     *
     * @param int $chunkSize
     * @return int Number of records seeded
     */
    public static function seedChunkStatic(int $chunkSize): int
    {
        // Use factory to generate data, then bulk insert for better performance
        $data = Transaction::factory()->count($chunkSize)->make()->map(function ($transaction) {
            return [
                'cost' => $transaction->cost->amount(),
                'price' => $transaction->price->amount(),
                'quantity' => $transaction->quantity,
            ];
        })->toArray();

        // Bulk insert the chunk
        DB::table('transactions')->insert($data);

        return $chunkSize;
    }
}

