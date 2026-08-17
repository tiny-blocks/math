<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Math\Unit;

use TinyBlocks\Math\Calculator;

final readonly class UnavailableCalculatorMock implements Calculator
{
    public function add(string $left, string $right): string
    {
        return $left;
    }

    public function power(string $base, int $exponent): string
    {
        return $base;
    }

    public function compare(string $left, string $right): int
    {
        return 0;
    }

    public function multiply(string $left, string $right): string
    {
        return $left;
    }

    public function quotient(string $numerator, string $denominator): string
    {
        return $numerator;
    }

    public function subtract(string $minuend, string $subtrahend): string
    {
        return $minuend;
    }

    public function remainder(string $numerator, string $denominator): string
    {
        return $numerator;
    }

    public function squareRoot(string $radicand): string
    {
        return $radicand;
    }

    public function isAvailable(): bool
    {
        return false;
    }
}
