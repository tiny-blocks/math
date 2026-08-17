<?php

declare(strict_types=1);

namespace TinyBlocks\Math;

use TinyBlocks\Math\Exceptions\DivisionByZero;
use TinyBlocks\Math\Exceptions\NegativeExponent;
use TinyBlocks\Math\Exceptions\NegativeRoot;

/**
 * Arbitrary-precision integer engine backing every value type in this library.
 *
 * <p>The contract is integer only, because that is the boundary every candidate backend shares:
 * GMP has no fractional type at all, and a decimal is an integer plus a scale. Scale bookkeeping
 * therefore stays inside the library and never reaches an implementation, so two backends cannot
 * disagree about a result.</p>
 *
 * <p>Every operand is a plain decimal integer string: optional leading <code>-</code>, then digits,
 * with no leading zero and no decimal point. Implementations may assume that shape.</p>
 *
 * <p>An operand the contract forbids is refused rather than answered, so the shipped backends are
 * indistinguishable on every input, valid or not. This is the only validation an implementation
 * owes: scale, range and shape are settled before a value reaches here.</p>
 */
interface Calculator
{
    /**
     * Adds two integers.
     *
     * @param string $left The first operand.
     * @param string $right The second operand.
     * @return string The sum.
     */
    public function add(string $left, string $right): string;

    /**
     * Raises an integer to a non-negative power.
     *
     * @param string $base The value to raise.
     * @param int $exponent The exponent, never negative.
     * @return string The power.
     * @throws NegativeExponent If the exponent is negative.
     */
    public function power(string $base, int $exponent): string;

    /**
     * Compares two integers.
     *
     * @param string $left The first operand.
     * @param string $right The second operand.
     * @return int Exactly -1, 0, or 1.
     */
    public function compare(string $left, string $right): int;

    /**
     * Multiplies two integers.
     *
     * @param string $left The first operand.
     * @param string $right The second operand.
     * @return string The product.
     */
    public function multiply(string $left, string $right): string;

    /**
     * Divides two integers, truncating toward zero.
     *
     * @param string $numerator The value being divided.
     * @param string $denominator The value to divide by, never zero.
     * @return string The truncated quotient.
     * @throws DivisionByZero If the denominator is zero.
     */
    public function quotient(string $numerator, string $denominator): string;

    /**
     * Subtracts one integer from another.
     *
     * @param string $minuend The value to subtract from.
     * @param string $subtrahend The value to subtract.
     * @return string The difference.
     */
    public function subtract(string $minuend, string $subtrahend): string;

    /**
     * Returns the remainder of a truncated division, carrying the sign of the numerator.
     *
     * @param string $numerator The value being divided.
     * @param string $denominator The value to divide by, never zero.
     * @return string The remainder.
     * @throws DivisionByZero If the denominator is zero.
     */
    public function remainder(string $numerator, string $denominator): string;

    /**
     * Returns the integer square root, truncated toward zero.
     *
     * @param string $radicand The value whose root is taken, never negative.
     * @return string The truncated square root.
     * @throws NegativeRoot If the radicand is negative.
     */
    public function squareRoot(string $radicand): string;

    /**
     * Tells whether this backend can run in the current process.
     *
     * @return bool True when whatever this backend needs is present, which for the shipped backends
     *              means the <code>bcmath</code> extension in one case and 64-bit integers in the other.
     */
    public function isAvailable(): bool;
}
