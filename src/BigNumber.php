<?php

declare(strict_types=1);

namespace TinyBlocks\Math;

use TinyBlocks\Math\Internal\Exceptions\DivisionByZero;

interface BigNumber
{
    public const AUTOMATIC_SCALE = null;

    /**
     * Adds the current BigNumber (augend) with another BigNumber (addend).
     *
     * @param BigNumber $addend The BigNumber to be added to the current BigNumber.
     * @return BigNumber A new BigNumber representing the sum of the two numbers.
     */
    public function add(BigNumber $addend): BigNumber;

    /**
     * Subtracts another BigNumber (subtrahend) from the current BigNumber (minuend).
     *
     * @param BigNumber $subtrahend The BigNumber to be subtracted from the current BigNumber.
     * @return BigNumber A new BigNumber representing the difference between the two numbers.
     */
    public function subtract(BigNumber $subtrahend): BigNumber;

    /**
     * Multiplies the current BigNumber (multiplicand) with another BigNumber (multiplier).
     *
     * @param BigNumber $multiplier The BigNumber to multiply the current BigNumber by.
     * @return BigNumber A new BigNumber representing the product of the two numbers.
     */
    public function multiply(BigNumber $multiplier): BigNumber;

    /**
     * Divides the current BigNumber (dividend) by another BigNumber (divisor).
     *
     * @param BigNumber $divisor The BigNumber to divide the current BigNumber by.
     * @return BigNumber A new BigNumber representing the quotient of the division.
     * @throws DivisionByZero If the divisor is zero, this exception is thrown.
     */
    public function divide(BigNumber $divisor): BigNumber;

    /**
     * Returns a new BigNumber after applying the specified rounding mode.
     *
     * @param RoundingMode $mode The rounding mode to apply.
     * @return BigNumber A new BigNumber rounded according to the specified mode.
     */
    public function withRounding(RoundingMode $mode): BigNumber;

    /**
     * Returns a new BigNumber with the specified scale.
     *
     * @param int $scale The scale to apply to the BigNumber.
     * @return BigNumber A new BigNumber with the specified scale.
     */
    public function withScale(int $scale): BigNumber;

    /**
     * Returns a new BigNumber with the negated value of the current BigNumber.
     *
     * @return BigNumber A new BigNumber representing the negated value of the current number.
     */
    public function negate(): BigNumber;

    /**
     * Retrieves the scale of the current BigNumber.
     *
     * @return int|null The scale of the current BigNumber, or null if no scale is applied.
     */
    public function getScale(): ?int;

    /**
     * Determines if the current BigNumber is equal to zero.
     *
     * @return bool True if the BigNumber is zero, otherwise false.
     */
    public function isZero(): bool;

    /**
     * Determines if the current BigNumber is negative.
     *
     * @return bool True if the BigNumber is negative, otherwise false.
     */
    public function isNegative(): bool;

    /**
     * Determines if the current BigNumber is positive.
     *
     * @return bool True if the BigNumber is positive, otherwise false.
     */
    public function isPositive(): bool;

    /**
     * Determines if the current BigNumber is either negative or zero.
     *
     * @return bool True if the BigNumber is negative or zero, otherwise false.
     */
    public function isNegativeOrZero(): bool;

    /**
     * Determines if the current BigNumber is either positive or zero.
     *
     * @return bool True if the BigNumber is positive or zero, otherwise false.
     */
    public function isPositiveOrZero(): bool;

    /**
     * Compares the current BigNumber with another BigNumber to determine if it is less.
     *
     * @param BigNumber $other The BigNumber to compare with.
     * @return bool True if the current BigNumber is less than the other BigNumber, otherwise false.
     */
    public function isLessThan(BigNumber $other): bool;

    /**
     * Compares the current BigNumber with another BigNumber to determine if it is greater.
     *
     * @param BigNumber $other The BigNumber to compare with.
     * @return bool True if the current BigNumber is greater than the other BigNumber, otherwise false.
     */
    public function isGreaterThan(BigNumber $other): bool;

    /**
     * Compares the current BigNumber with another BigNumber to determine if it is less than or equal.
     *
     * @param BigNumber $other The BigNumber to compare with.
     * @return bool True if the current BigNumber is less than or equal to the other BigNumber, otherwise false.
     */
    public function isLessThanOrEqual(BigNumber $other): bool;

    /**
     * Compares the current BigNumber with another BigNumber to determine if it is greater than or equal.
     *
     * @param BigNumber $other The BigNumber to compare with.
     * @return bool True if the current BigNumber is greater than or equal to the other BigNumber, otherwise false.
     */
    public function isGreaterThanOrEqual(BigNumber $other): bool;

    /**
     * Converts the current BigNumber to a floating-point number.
     * Note: Precision may be lost during conversion.
     *
     * @return float The floating-point representation of the BigNumber.
     */
    public function toFloat(): float;

    /**
     * Converts the current BigNumber to a string representation.
     *
     * @return string The string representation of the BigNumber.
     */
    public function toString(): string;
}
