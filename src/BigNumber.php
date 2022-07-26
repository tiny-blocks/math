<?php

namespace TinyBlocks\Math;

use TinyBlocks\Math\Internal\Exceptions\DivisionByZero;

interface BigNumber
{
    public const AUTOMATIC_SCALE = null;

    /**
     * Performs an addition operation between two values, augend ($this) and addend ($addend).
     * @param BigNumber $addend
     * @return BigNumber
     */
    public function add(BigNumber $addend): BigNumber;

    /**
     * Performs a subtraction operation between two values, minuend ($this) and subtrahend ($subtrahend).
     * @param BigNumber $subtrahend
     * @return BigNumber
     */
    public function subtract(BigNumber $subtrahend): BigNumber;

    /**
     * Performs a multiplication operation between two values, multiplicand ($this) and multiplier ($multiplier).
     * @param BigNumber $multiplier
     * @return BigNumber
     */
    public function multiply(BigNumber $multiplier): BigNumber;

    /**
     * Performs a division operation between two values, dividend ($this) and divisor ($divisor).
     * @param BigNumber $divisor
     * @return BigNumber
     * @throws DivisionByZero — If the divisor has a value of zero.
     */
    public function divide(BigNumber $divisor): BigNumber;

    /**
     * Returns a BigNumber with the given rounding.
     * @param RoundingMode $mode
     * @return BigNumber
     */
    public function withRounding(RoundingMode $mode): BigNumber;

    /**
     * Returns a BigNumber with the given scale.
     * @param int $scale
     * @return BigNumber
     */
    public function withScale(int $scale): BigNumber;

    /**
     * Returns the current BigNumber with the negated value,
     * from a multiplication operation between two values, multiplying ($this) and multiplier (-1).
     * @return BigNumber
     */
    public function negate(): BigNumber;

    /**
     * Returns the scale value.
     * @return int|null
     */
    public function getScale(): ?int;

    /**
     * Checks if the BigNumber value is zero.
     * @return bool
     */
    public function isZero(): bool;

    /**
     * Checks if the BigNumber value is negative.
     * @return bool
     */
    public function isNegative(): bool;

    /**
     * Checks if the BigNumber value is positive.
     * @return bool
     */
    public function isPositive(): bool;

    /**
     * Checks whether the BigNumber value is negative or zero.
     * @return bool
     */
    public function isNegativeOrZero(): bool;

    /**
     * Checks whether the BigNumber value is positive or zero.
     * @return bool
     */
    public function isPositiveOrZero(): bool;

    /**
     * Checks if the value of ($this) is less than the value of ($other).
     * @param BigNumber $other
     * @return bool
     */
    public function isLessThan(BigNumber $other): bool;

    /**
     * Checks if the value of ($this) is greater than the value of ($other).
     * @param BigNumber $other
     * @return bool
     */
    public function isGreaterThan(BigNumber $other): bool;

    /**
     * Checks if the value of ($this) is less than or equal the value of ($other).
     * @param BigNumber $other
     * @return bool
     */
    public function isLessThanOrEqual(BigNumber $other): bool;

    /**
     * Checks if the value of ($this) is greater than or equal the value of ($other).
     * @param BigNumber $other
     * @return bool
     */
    public function isGreaterThanOrEqual(BigNumber $other): bool;

    /**
     * Converts this BigNumber to a float. Note that even when the return value is finite,
     * this conversion can lose information about the precision of the BigNumber value.
     * @return float
     */
    public function toFloat(): float;

    /**
     * Converts this BigNumber to a string.
     * @return string
     */
    public function toString(): string;
}
