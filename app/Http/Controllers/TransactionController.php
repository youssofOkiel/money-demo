<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\MoneyResource;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Support\Money\Money;
use Illuminate\Support\Facades\Concurrency;

class TransactionController extends Controller
{

    public function index()
    {
        $chunkSize = 10000;
        $maxConcurrentProcesses = 10;
        $totalCount = Transaction::count();
        $totalChunks = (int) ceil($totalCount / $chunkSize);
        $batchSize = 10;

        // Limit the number of concurrent processes
        $chunks = min($totalChunks, $maxConcurrentProcesses);

        // Create concurrent tasks for each chunk
        $tasks = [];
        for ($i = 0; $i < $chunks; $i++) {
            $offset = $i * $chunkSize;
            $tasks[] = fn() => $this->processChunk($offset, $chunkSize);
        }

        // Process in batches if we have more chunks than max processes
        $results = [];
        $processedChunks = 0;

        while ($processedChunks < $totalChunks) {
            $batchTasks = [];
            $batchSize = min($chunks, $totalChunks - $processedChunks);

            for ($i = 0; $i < $batchSize; $i++) {
                $offset = ($processedChunks + $i) * $chunkSize;
                $batchTasks[] = fn() => $this->processChunk($offset, $chunkSize);
            }

            $batchResults = Concurrency::run($batchTasks);
            $results = array_merge($results, $batchResults);
            $processedChunks += $batchSize;
        }

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

        return response()->json([
            'total_cost' => new MoneyResource($totalCost),
            'total_price_and_quantity' => new MoneyResource($totalPriceAndQuantity),
            'difference' => new MoneyResource($difference),
            'total_chunks' => $totalChunks,
            'chunks' => $chunks,
            'batch_size' => $batchSize,
            'processed_chunks' => $processedChunks,
            'total_count' => number_format($totalCount),
            'chunk_size' => $chunkSize,
            'max_concurrent_processes' => $maxConcurrentProcesses,
        ]);
    }

    /**
     * Process a chunk of transactions using chunkById for memory efficiency
     */
    private function processChunk(int $offset, int $limit): array
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


    public function store(StoreTransactionRequest $request)
    {
        $transaction = Transaction::create([
            'cost' => $request->validated('cost'),
            'price' => $request->validated('price'),
            'quantity' => $request->validated('quantity'),
        ]);

        return new TransactionResource($transaction);
    }
}

