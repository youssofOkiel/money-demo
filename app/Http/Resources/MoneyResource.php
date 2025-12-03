<?php

namespace App\Http\Resources;

use App\Support\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MoneyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'amount' => $this->amount(),
            'currency' => $this->currency()->value,
            'formatted' => $this->formatted(),
            'decimal' => $this->decimal(),
        ];
    }
}

