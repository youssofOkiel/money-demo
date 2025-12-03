<?php

namespace App\Http\Requests;

use App\Support\Money\Enums\Currency;
use App\Support\Money\Rules\Money;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'cost' => ['required', new Money(Currency::EGP)],
        ];
    }
}

