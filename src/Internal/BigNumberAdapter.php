<?php

namespace TinyBlocks\Math\Internal;

use TinyBlocks\Math\BigNumber;
use TinyBlocks\Math\Internal\Exceptions\DivisionByZero;
use TinyBlocks\Math\Internal\Operations\Extension\ExtensionAdapter;
use TinyBlocks\Math\Internal\Operations\MathOperations;
use TinyBlocks\Math\Internal\Operations\MathOperationsFactory;
use TinyBlocks\Math\Internal\Operations\Rounding\Rounder;
use TinyBlocks\Math\RoundingMode;

abstract class BigNumberAdapter
{
    private MathOperations $mathOperations;

    public function __construct(protected readonly Number $number, protected readonly Scale $scale)
    {
        $extension = new ExtensionAdapter();
        $operationsFactory = new MathOperationsFactory(extension: $extension);
        $this->mathOperations = $operationsFactory->build();
    }

    abstract public static function from(float|string $value, ?int $scale = BigNumber::AUTOMATIC_SCALE): BigNumber;

    public function add(BigNumber $addend): BigNumber
    {
        $result = $this->mathOperations->add(augend: $this, addend: $addend);

        return $this::from(value: $result->number->value, scale: $result->scale->value);
    }

    public function subtract(BigNumber $subtrahend): BigNumber
    {
        $result = $this->mathOperations->subtract(minuend: $this, subtrahend: $subtrahend);

        return $this::from(value: $result->number->value, scale: $result->scale->value);
    }

    public function multiply(BigNumber $multiplier): BigNumber
    {
        $result = $this->mathOperations->multiply(multiplicand: $this, multiplier: $multiplier);

        return $this::from(value: $result->number->value, scale: $result->scale->value);
    }

    public function divide(BigNumber $divisor): BigNumber
    {
        if ($divisor->isZero()) {
            throw new DivisionByZero(dividend: $this->number->value, divisor: $divisor->number->value);
        }

        $result = $this->mathOperations->divide(dividend: $this, divisor: $divisor);

        return $this::from(value: $result->number->value, scale: $result->scale->value);
    }

    public function withRounding(RoundingMode $mode): BigNumber
    {
        $rounder = new Rounder(mode: $mode, bigNumber: $this);
        $rounded = $rounder->round();

        return $this::from(value: $rounded->value, scale: $this->scale->value);
    }

    public function withScale(int $scale): BigNumber
    {
        $number = $this->scale->numberWithScale(number: $this->number, scale: $scale);

        return $this::from(value: $number->value, scale: $scale);
    }

    public function negate(): BigNumber
    {
        $multiplicand = $this->from(value: -1);

        return $this->multiply(multiplier: $multiplicand);
    }

    public function getScale(): ?int
    {
        return $this->scale->value;
    }

    public function isZero(): bool
    {
        return $this->number->isZero();
    }

    public function isNegative(): bool
    {
        return $this->number->isNegative();
    }

    public function isPositive(): bool
    {
        return !$this->isNegativeOrZero();
    }

    public function isNegativeOrZero(): bool
    {
        return $this->number->isNegativeOrZero();
    }

    public function isPositiveOrZero(): bool
    {
        return $this->isPositive() || $this->isZero();
    }

    public function isLessThan(BigNumber $other): bool
    {
        return $this->number->isLessThan(other: $other->number);
    }

    public function isGreaterThan(BigNumber $other): bool
    {
        return $this->number->isGreaterThan(other: $other->number);
    }

    public function isLessThanOrEqual(BigNumber $other): bool
    {
        return $this->number->isLessThanOrEqual(other: $other->number);
    }

    public function isGreaterThanOrEqual(BigNumber $other): bool
    {
        return $this->number->isGreaterThanOrEqual(other: $other->number);
    }

    public function toFloat(): float
    {
        return $this->toString();
    }

    public function toString(): string
    {
        return $this->number->value;
    }
}
