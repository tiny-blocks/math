<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Backends;

use TinyBlocks\Math\Calculator;
use TinyBlocks\Math\Exceptions\DivisionByZero;
use TinyBlocks\Math\Exceptions\NegativeExponent;
use TinyBlocks\Math\Exceptions\NegativeRoot;

final readonly class BcMathCalculator implements Calculator
{
    private const string MINUS = '-';
    private const string EXTENSION = 'bcmath';
    private const int EXACT_SCALE = 0;
    private const string SIGNED_ZERO = '-0';

    public function add(string $left, string $right): string
    {
        return bcadd($left, $right, self::EXACT_SCALE);
    }

    public function power(string $base, int $exponent): string
    {
        if ($exponent < 0) {
            throw NegativeExponent::becauseExponentIsNegative(exponent: $exponent);
        }

        return bcpow($base, (string)$exponent, self::EXACT_SCALE);
    }

    public function compare(string $left, string $right): int
    {
        return bccomp($left, $right, self::EXACT_SCALE);
    }

    private function nonZero(string $numerator, string $denominator): string
    {
        if (ltrim($denominator, self::SIGNED_ZERO) === '') {
            throw DivisionByZero::becauseDivisorIsZero(dividend: $numerator);
        }

        return $denominator;
    }

    public function multiply(string $left, string $right): string
    {
        return bcmul($left, $right, self::EXACT_SCALE);
    }

    public function quotient(string $numerator, string $denominator): string
    {
        return bcdiv($numerator, $this->nonZero(numerator: $numerator, denominator: $denominator), self::EXACT_SCALE);
    }

    public function subtract(string $minuend, string $subtrahend): string
    {
        return bcsub($minuend, $subtrahend, self::EXACT_SCALE);
    }

    public function remainder(string $numerator, string $denominator): string
    {
        return bcmod($numerator, $this->nonZero(numerator: $numerator, denominator: $denominator), self::EXACT_SCALE);
    }

    public function squareRoot(string $radicand): string
    {
        if (str_starts_with($radicand, self::MINUS) && ltrim($radicand, self::SIGNED_ZERO) !== '') {
            throw NegativeRoot::becauseRadicandIsNegative(radicand: $radicand);
        }

        return bcsqrt($radicand, self::EXACT_SCALE);
    }

    public function isAvailable(): bool
    {
        return extension_loaded(self::EXTENSION);
    }
}
