<?php

namespace App\Support\Money\Rules;

use App\Support\Money\Enums\Currency;
use App\Support\Money\Money as SupportMoney;
use Closure;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;

readonly class Money implements ValidationRule
{
    public function __construct(protected Currency $currency = Currency::EGP) {}
   
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Handle null and empty values
        if ($value === null || $value === '') {
            $fail("The :attribute field is required.", null);
            return;
        }

        try {
            // Attempt to parse the money value
            $money = SupportMoney::parse($value, $this->currency);

            // Validate currency matches
            if (! $money->currency()->equals($this->currency)) {
                $fail("The :attribute must be in {$this->currency->value} currency.", null);
                return;
            }

            // Validate amount is non-negative
            if ($money->isNegative()) {
                $fail("The :attribute must be a positive amount.", null);
                return;
            }

        } catch (Exception $e) {
            // Catch all exceptions from Money::parse()
            $fail("The :attribute must be a valid money amount.", null);
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        $message = trans('validation.money');

        return $message === 'validation.money'
            ? 'The :attribute is not a valid money.'
            : $message;
    }
}