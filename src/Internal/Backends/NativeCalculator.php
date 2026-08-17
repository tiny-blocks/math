<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Backends;

use TinyBlocks\Math\Calculator;
use TinyBlocks\Math\Exceptions\DivisionByZero;
use TinyBlocks\Math\Exceptions\NegativeExponent;
use TinyBlocks\Math\Exceptions\NegativeRoot;

final readonly class NativeCalculator implements Calculator
{
    private const int ONE = 1;
    private const string ZERO = '0';
    private const string MINUS = '-';
    private const string SET_BIT = '1';
    private const int REQUIRED_INTEGER_SIZE = 8;

    public function add(string $left, string $right): string
    {
        $augend = Signed::of(value: $left);
        $addend = Signed::of(value: $right);

        if ($augend->isNegative === $addend->isNegative) {
            return $this->signed(
                magnitude: $augend->magnitude->plus(other: $addend->magnitude),
                isNegative: $augend->isNegative
            );
        }

        $difference = $augend->magnitude->differenceFrom(other: $addend->magnitude);

        return $this->signed(
            magnitude: $difference->magnitude,
            isNegative: $difference->isNegative ? $addend->isNegative : $augend->isNegative
        );
    }

    public function power(string $base, int $exponent): string
    {
        if ($exponent < 0) {
            throw NegativeExponent::becauseExponentIsNegative(exponent: $exponent);
        }

        $power = (string)self::ONE;

        foreach (str_split(decbin($exponent)) as $bit) {
            $squared = $this->multiply(left: $power, right: $power);
            $power = $bit === self::SET_BIT ? $this->multiply(left: $squared, right: $base) : $squared;
        }

        return $power;
    }

    private function signed(Magnitude $magnitude, bool $isNegative): string
    {
        if ($magnitude->isZero()) {
            return self::ZERO;
        }

        $template = '%s%s';

        return sprintf($template, $isNegative ? self::MINUS : '', $magnitude->toText());
    }

    public function compare(string $left, string $right): int
    {
        $first = Signed::of(value: $left);
        $second = Signed::of(value: $right);

        if ($first->isNegative !== $second->isNegative) {
            return $first->isNegative ? (0 - self::ONE) : self::ONE;
        }

        $order = $first->magnitude->compareTo(other: $second->magnitude);

        return $first->isNegative ? (0 - $order) : $order;
    }

    private function nonZero(string $numerator, string $denominator): Signed
    {
        $divisor = Signed::of(value: $denominator);

        if ($divisor->magnitude->isZero()) {
            throw DivisionByZero::becauseDivisorIsZero(dividend: $numerator);
        }

        return $divisor;
    }

    public function multiply(string $left, string $right): string
    {
        $multiplicand = Signed::of(value: $left);
        $multiplier = Signed::of(value: $right);

        return $this->signed(
            magnitude: $multiplicand->magnitude->multipliedBy(other: $multiplier->magnitude),
            isNegative: $multiplicand->isNegative !== $multiplier->isNegative
        );
    }

    public function quotient(string $numerator, string $denominator): string
    {
        $dividend = Signed::of(value: $numerator);
        $divisor = $this->nonZero(numerator: $numerator, denominator: $denominator);

        return $this->signed(
            magnitude: $dividend->magnitude->dividedBy(divisor: $divisor->magnitude)->quotient,
            isNegative: $dividend->isNegative !== $divisor->isNegative
        );
    }

    public function subtract(string $minuend, string $subtrahend): string
    {
        $template = '-%s';
        $negated = str_starts_with($subtrahend, self::MINUS)
            ? ltrim($subtrahend, self::MINUS)
            : sprintf($template, $subtrahend);

        return $this->add(left: $minuend, right: $negated);
    }

    public function remainder(string $numerator, string $denominator): string
    {
        $dividend = Signed::of(value: $numerator);
        $divisor = $this->nonZero(numerator: $numerator, denominator: $denominator);

        return $this->signed(
            magnitude: $dividend->magnitude->dividedBy(divisor: $divisor->magnitude)->remainder,
            isNegative: $dividend->isNegative
        );
    }

    public function squareRoot(string $radicand): string
    {
        $signedRadicand = Signed::of(value: $radicand);

        if ($signedRadicand->isNegative) {
            throw NegativeRoot::becauseRadicandIsNegative(radicand: $radicand);
        }

        return $signedRadicand->magnitude->squareRoot()->toText();
    }

    public function isAvailable(): bool
    {
        return PHP_INT_SIZE >= self::REQUIRED_INTEGER_SIZE;
    }
}
