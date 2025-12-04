<?php

namespace App\Support\Money\Enums;

enum Currency: string
{
    case EGP = 'EGP';
    case SAR = 'SAR';
    case KWD = 'KWD';

    public function decimalPlaces(): int
    {
        return match ($this) {
            Currency::EGP, Currency::SAR => 2,
            Currency::KWD => 3,
        };
    }

    public function smallestUnit(): int
    {
        return match ($this) {
            Currency::EGP, Currency::SAR => 1000,
            Currency::KWD => 10000,
        };
    }

    public function equals(Currency $currency): bool
    {
        return $this->value === $currency->value;
    }

    public function label(): string
    {
        return match ($this) {
            Currency::EGP => __('enum.' . $this->value),
            Currency::SAR => __('enum.' . $this->value),
            Currency::KWD => __('enum.' . $this->value),
        };
    }
}
