<?php

namespace App\Support\Money;

use App\Support\Money\Enums\Currency;
use Exception;

class Money
{
    public function __construct(
        protected int|float $amount,
        protected Currency $currency = Currency::EGP) {}

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function formatted(): string
    {
        return match ($this->currency()) {
            Currency::SAR => number_format($this->amount() / 100, 2).' '. $this->currency()->label(),
            Currency::KWD => number_format($this->amount() / 1000, 3).' '. $this->currency()->label(),
            Currency::EGP => number_format($this->amount() / 100, 2).' '. $this->currency()->label(),
            default => throw new Exception(__('error.unsupported_currency')),
        };
    }

    public function decimal(): string
    {
        return match ($this->currency()) {
            Currency::EGP,Currency::SAR => number_format($this->amount() / 100, 2),
            Currency::KWD => number_format($this->amount() / 1000, 3),
            default => throw new Exception(__('error.unsupported_currency')),
        };
    }

    /**
     * @throws Exception
     */
    public static function parse(int|float|string $amount, Currency $currency = Currency::EGP): Money
    {
        // Normalize the input amount
        $normalizedAmount = self::normalizeAmount($amount);

        // Convert to float for processing
        $floatAmount = (float) $normalizedAmount;

        // Convert to smallest currency unit
        $smallestUnitAmount = match ($currency) {
            Currency::KWD => (int) round($floatAmount * 1000),
            Currency::EGP, Currency::SAR => (int) round($floatAmount * 100),
            default => throw new Exception(__('error.unsupported_currency')),
        };

        return new self($smallestUnitAmount, $currency);
    }

    /**
     * Normalize amount input by handling commas, trailing dots, and whitespace.
     */
    private static function normalizeAmount(int|float|string $amount): string
    {
        // Convert to string for processing
        $amountString = (string) $amount;

        // Remove whitespace
        $amountString = trim($amountString);

        // Handle trailing dots (e.g., "10." => "10")
        $amountString = rtrim($amountString, '.');

        $hasComma = str_contains($amountString, ',');
        $hasDot = str_contains($amountString, '.');

        if ($hasDot) {
            // Dot exists - it's always the decimal separator
            // Commas before the dot are thousands separators - remove them
            // Examples: "1,000.50", "1000,000.25" => remove commas
            $parts = explode('.', $amountString, 2);
            $integerPart = str_replace(',', '', $parts[0]);
            $decimalPart = $parts[1] ?? '';
            $amountString = $decimalPart !== '' ? $integerPart . '.' . $decimalPart : $integerPart;
        } elseif ($hasComma) {
            // Only comma present - check if it's European decimal format
            // Pattern: digits, comma, 2-3 digits at end (e.g., "1000,00" => "1000.00")
            if (preg_match('/^(\d+),(\d{2,3})$/', $amountString, $matches)) {
                // European format: comma is decimal separator
                $amountString = $matches[1] . '.' . $matches[2];
            } else {
                // Comma is thousands separator - remove it
                $amountString = str_replace(',', '', $amountString);
            }
        }

        // Validate the result is numeric
        if (! is_numeric($amountString)) {
            throw new Exception(__('error.invalid_amount_format'));
        }

        return $amountString;
    }

    public function multiply(int|float|string $multiplier): Money
    {
        return new self($this->amount() * $multiplier, $this->currency());
    }

    /**
     * @throws Exception
     */
    public function add(Money $money): static
    {
        if (! $this->currency()->equals($money->currency())) {
            throw new Exception(__('error.currencies_must_be_same'));
        }

        $this->amount += $money->amount();

        return $this;
    }

    /**
     * @throws Exception
     */
    public function divide(int|float|string $divisor): static
    {
        if ($divisor == 0) {
            throw new Exception(__('error.division_by_zero'));
        }

        $this->amount /= $divisor;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function subtract(Money $money): static
    {
        if (! $this->currency()->equals($money->currency())) {
            throw new Exception(__('error.currencies_must_be_same'));
        }

        $this->amount -= $money->amount();

        return $this;
    }

    public function isNegative(): bool
    {
        return $this->amount() < 0;
    }
}