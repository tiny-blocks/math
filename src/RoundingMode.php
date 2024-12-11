<?php

declare(strict_types=1);

namespace TinyBlocks\Math;

use TinyBlocks\Math\Internal\Number;
use TinyBlocks\Math\Internal\Scale;

/**
 * Use one of the following constants to specify the mode in which rounding occurs.
 *
 * @see https://www.php.net/manual/en/function.round.php
 */
enum RoundingMode: int
{
    case HALF_UP = 1;
    case HALF_ODD = 4;
    case HALF_EVEN = 3;
    case HALF_DOWN = 2;

    private const int ODD_CORRECTION = 1;
    private const int EVEN_CHECK_MODULO = 2;
    private const float HALF_ODD_EVEN_THRESHOLD = 0.5;

    public function round(BigNumber $bigNumber): Number
    {
        $precision = $bigNumber->getScale() ?? Scale::MINIMUM;
        $factor = 10 ** $precision;
        $value = $bigNumber->toFloat();

        $roundedValue = match ($this) {
            self::HALF_UP   => $this->roundHalfUp(value: $value, precision: $precision),
            self::HALF_ODD  => $this->roundHalfOdd(value: $value, factor: $factor),
            self::HALF_EVEN => $this->roundHalfEven(value: $value, precision: $precision),
            self::HALF_DOWN => $this->roundHalfDown(value: $value, factor: $factor)
        };

        return Number::from($roundedValue);
    }

    private function roundHalfUp(float $value, int $precision): float
    {
        return round($value, $precision);
    }

    private function roundHalfOdd(float $value, int $factor): float
    {
        $scaledValue = $value * $factor;
        $rounded = round($scaledValue);

        if ($rounded % self::EVEN_CHECK_MODULO === 0) {
            $rounded += ($scaledValue > $rounded) ? self::ODD_CORRECTION : -self::ODD_CORRECTION;
        }

        return $rounded / $factor;
    }

    private function roundHalfEven(float $value, int $precision): float
    {
        return round($value, $precision, PHP_ROUND_HALF_EVEN);
    }

    private function roundHalfDown(float $value, int $factor): float
    {
        $value = ($value * $factor - self::HALF_ODD_EVEN_THRESHOLD) / $factor;

        return floor($value * $factor) / $factor;
    }
}
