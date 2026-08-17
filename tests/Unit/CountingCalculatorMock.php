<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Math\Unit;

use TinyBlocks\Math\Calculator;
use TinyBlocks\Math\Internal\Backends\BcMathCalculator;

final class CountingCalculatorMock implements Calculator
{
    private int $additions = 0;

    private BcMathCalculator $delegate;

    public function __construct()
    {
        $this->delegate = new BcMathCalculator();
    }

    public function add(string $left, string $right): string
    {
        $this->additions++;

        return $this->delegate->add(left: $left, right: $right);
    }

    public function power(string $base, int $exponent): string
    {
        return $this->delegate->power(base: $base, exponent: $exponent);
    }

    public function compare(string $left, string $right): int
    {
        return $this->delegate->compare(left: $left, right: $right);
    }

    public function multiply(string $left, string $right): string
    {
        return $this->delegate->multiply(left: $left, right: $right);
    }

    public function quotient(string $numerator, string $denominator): string
    {
        return $this->delegate->quotient(numerator: $numerator, denominator: $denominator);
    }

    public function subtract(string $minuend, string $subtrahend): string
    {
        return $this->delegate->subtract(minuend: $minuend, subtrahend: $subtrahend);
    }

    public function additions(): int
    {
        return $this->additions;
    }

    public function remainder(string $numerator, string $denominator): string
    {
        return $this->delegate->remainder(numerator: $numerator, denominator: $denominator);
    }

    public function squareRoot(string $radicand): string
    {
        return $this->delegate->squareRoot(radicand: $radicand);
    }

    public function isAvailable(): bool
    {
        return $this->delegate->isAvailable();
    }
}
