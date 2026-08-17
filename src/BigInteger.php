<?php

declare(strict_types=1);

namespace TinyBlocks\Math;

use TinyBlocks\Math\Exceptions\BaseOutOfRange;
use TinyBlocks\Math\Exceptions\DivisionByZero;
use TinyBlocks\Math\Exceptions\ExponentOutOfRange;
use TinyBlocks\Math\Exceptions\InexactConversion;
use TinyBlocks\Math\Exceptions\IntegerOverflow;
use TinyBlocks\Math\Exceptions\NegativeExponent;
use TinyBlocks\Math\Exceptions\NegativeRoot;
use TinyBlocks\Math\Exceptions\NumberNotWellFormed;
use TinyBlocks\Math\Internal\Bases\PositionalBase;
use TinyBlocks\Math\Internal\Decimals\Digits;
use TinyBlocks\Math\Internal\NumberComparison;
use TinyBlocks\Math\Internal\StructuralHash;

/**
 * Arbitrary-precision integer.
 *
 * <p>Exists so that problems without a scale (identifiers, counters, combinatorics, modular
 * arithmetic) never carry one. Division returns a {@see BigRational}, so it is exact for every
 * pair of operands and raises only on a zero divisor. Truncating division is available separately
 * as <code>{@see BigInteger::quotient()}</code> and <code>{@see BigInteger::remainder()}</code>.</p>
 */
final readonly class BigInteger implements Number
{
    use NumberComparison;

    private const string ONE = '1';
    private const string ZERO = '0';

    private function __construct(private Digits $digits)
    {
    }

    /**
     * Creates a BigInteger from an integer literal.
     *
     * <p>A literal with a zero fractional part, such as <code>'5.00'</code>, is accepted. A
     * literal with a non-zero fractional part is not.</p>
     *
     * @param string|int $value The literal to read.
     * @return BigInteger The created instance.
     * @throws NumberNotWellFormed If the literal is not a number, or its exponent exceeds 10000 in magnitude.
     * @throws InexactConversion If the literal carries a non-zero fractional part.
     */
    public static function of(string|int $value): BigInteger
    {
        $digits = Digits::from(value: $value)->withoutTrailingZeros();

        if ($digits->scale->value > 0) {
            throw InexactConversion::becauseFractionalPartWouldBeLost(value: (string)$value);
        }

        return new BigInteger(digits: $digits);
    }

    /**
     * Creates a BigInteger holding one.
     *
     * @return BigInteger The created instance.
     */
    public static function one(): BigInteger
    {
        return BigInteger::of(value: self::ONE);
    }

    /**
     * Creates a BigInteger holding zero.
     *
     * @return BigInteger The created instance.
     */
    public static function zero(): BigInteger
    {
        return BigInteger::of(value: self::ZERO);
    }

    /**
     * Creates a BigInteger from its representation in another base.
     *
     * @param int $base The base of the literal, from 2 to 36.
     * @param string $value The literal, case-insensitive, with an optional leading minus.
     * @return BigInteger The created instance.
     * @throws BaseOutOfRange If the base is outside 2 to 36.
     * @throws NumberNotWellFormed If the literal carries a character the base does not define.
     */
    public static function fromBase(int $base, string $value): BigInteger
    {
        return new BigInteger(digits: PositionalBase::of(base: $base)->toDigits(value: $value));
    }

    /**
     * Adds another integer to this one.
     *
     * @param BigInteger $addend The integer to add.
     * @return BigInteger A new instance holding the sum.
     */
    public function plus(BigInteger $addend): BigInteger
    {
        return new BigInteger(digits: $this->digits->plus(other: $addend->digits));
    }

    /**
     * Tells whether this integer is odd.
     *
     * @return bool True when the value is not divisible by two.
     */
    public function isOdd(): bool
    {
        return !$this->isEven();
    }

    /**
     * Subtracts another integer from this one.
     *
     * @param BigInteger $subtrahend The integer to subtract.
     * @return BigInteger A new instance holding the difference.
     */
    public function minus(BigInteger $subtrahend): BigInteger
    {
        return new BigInteger(digits: $this->digits->minus(other: $subtrahend->digits));
    }

    /**
     * Raises this integer to a non-negative power.
     *
     * @param int $exponent The exponent, never negative.
     * @return BigInteger A new instance holding the power.
     * @throws NegativeExponent If the exponent is negative.
     * @throws ExponentOutOfRange If the exponent is beyond the supported magnitude.
     */
    public function power(int $exponent): BigInteger
    {
        if ($exponent < 0) {
            throw NegativeExponent::becauseExponentIsNegative(exponent: $exponent);
        }

        return new BigInteger(digits: $this->digits->power(exponent: $exponent));
    }

    /**
     * Returns this integer as a native integer.
     *
     * @return int The native integer.
     * @throws IntegerOverflow If the value is outside the native integer range.
     */
    public function toInt(): int
    {
        return $this->digits->toInt();
    }

    /**
     * Tells whether this integer holds the same digits as another integer.
     *
     * <p>Structural equality. Two BigInteger instances holding the same value are always equal,
     * because an integer has a single representation. To compare against a number of another type,
     * use <code>{@see Number::isEqualTo()}</code>.</p>
     *
     * @param BigInteger $other The integer to compare against.
     * @return bool True when both hold the same value.
     */
    public function equals(BigInteger $other): bool
    {
        return $other->digits->unscaled === $this->digits->unscaled;
    }

    /**
     * Tells whether this integer is even.
     *
     * @return bool True when the value is divisible by two.
     */
    public function isEven(): bool
    {
        return $this->digits->isEven();
    }

    /**
     * Tells whether this integer is zero.
     *
     * @return bool True when the value is zero.
     */
    public function isZero(): bool
    {
        return $this->digits->isZero();
    }

    /**
     * Returns the remainder of a floored division, never negative.
     *
     * <p>This is the mathematical modulo. Its sign convention differs from
     * <code>{@see BigInteger::remainder()}</code>, which follows the dividend.</p>
     *
     * @param BigInteger $modulus The modulus, never zero.
     * @return BigInteger A new instance holding the non-negative remainder.
     * @throws DivisionByZero If the modulus is zero.
     */
    public function modulo(BigInteger $modulus): BigInteger
    {
        $remainder = $this->remainder(divisor: $modulus);

        return $remainder->isNegative() ? $remainder->plus(addend: $modulus->absolute()) : $remainder;
    }

    /**
     * Returns this integer written in another base.
     *
     * @param int $base The target base, from 2 to 36.
     * @return string The representation, lowercase for bases above ten.
     * @throws BaseOutOfRange If the base is outside 2 to 36.
     */
    public function toBase(int $base): string
    {
        return PositionalBase::of(base: $base)->toText(digits: $this->digits);
    }

    /**
     * Returns this integer with the opposite sign.
     *
     * @return BigInteger A new instance with the sign flipped.
     */
    public function negated(): BigInteger
    {
        return new BigInteger(digits: $this->digits->negated());
    }

    /**
     * Returns the magnitude of this integer.
     *
     * @return BigInteger A new instance with the sign removed.
     */
    public function absolute(): BigInteger
    {
        return new BigInteger(digits: $this->digits->absolute());
    }

    /**
     * Returns a deterministic hash of this integer.
     *
     * @return string The structural hash.
     */
    public function hashCode(): string
    {
        return new StructuralHash()->of(type: BigInteger::class, representation: $this->toString());
    }

    /**
     * Divides this integer by another, truncating toward zero.
     *
     * @param BigInteger $divisor The integer to divide by, never zero.
     * @return BigInteger A new instance holding the truncated quotient.
     * @throws DivisionByZero If the divisor is zero.
     */
    public function quotient(BigInteger $divisor): BigInteger
    {
        return new BigInteger(digits: $this->digits->quotient(divisor: $divisor->digits));
    }

    /**
     * Returns this integer as a string.
     *
     * @return string The canonical string form.
     */
    public function toString(): string
    {
        return $this->digits->value();
    }

    /**
     * Compares this integer with another number.
     *
     * @param Number $other The number to compare against.
     * @return int A negative number, zero, or a positive number.
     */
    public function compareTo(Number $other): int
    {
        return $other instanceof BigInteger
            ? $this->digits->compareTo(other: $other->digits)
            : $this->toBigRational()->compareTo(other: $other);
    }

    /**
     * Divides this integer by another, exactly.
     *
     * @param BigInteger $divisor The integer to divide by, never zero.
     * @return BigRational The exact quotient.
     * @throws DivisionByZero If the divisor is zero.
     */
    public function dividedBy(BigInteger $divisor): BigRational
    {
        return $this->toBigRational()->dividedBy(divisor: $divisor->toBigRational());
    }

    /**
     * Returns the remainder of a truncated division, carrying the sign of this integer.
     *
     * @param BigInteger $divisor The integer to divide by, never zero.
     * @return BigInteger A new instance holding the remainder.
     * @throws DivisionByZero If the divisor is zero.
     */
    public function remainder(BigInteger $divisor): BigInteger
    {
        return new BigInteger(digits: $this->digits->remainder(divisor: $divisor->digits));
    }

    /**
     * Tells whether this integer is strictly less than zero.
     *
     * @return bool True when the value is negative.
     */
    public function isNegative(): bool
    {
        return $this->digits->isNegative();
    }

    /**
     * Returns the integer square root, truncated toward zero.
     *
     * @return BigInteger A new instance holding the truncated root.
     * @throws NegativeRoot If this integer is negative.
     */
    public function squareRoot(): BigInteger
    {
        return new BigInteger(digits: $this->digits->squareRoot());
    }

    /**
     * Multiplies this integer by another.
     *
     * @param BigInteger $multiplier The integer to multiply by.
     * @return BigInteger A new instance holding the product.
     */
    public function multipliedBy(BigInteger $multiplier): BigInteger
    {
        return new BigInteger(digits: $this->digits->multipliedBy(other: $multiplier->digits));
    }

    /**
     * Returns this integer as a decimal of scale zero.
     *
     * @return BigDecimal The decimal representation.
     */
    public function toBigDecimal(): BigDecimal
    {
        return BigDecimal::of(value: $this->digits->value());
    }

    /**
     * Returns this integer as a JSON string.
     *
     * @return string The canonical string form.
     */
    public function jsonSerialize(): string
    {
        return $this->toString();
    }

    /**
     * Returns this integer as an exact fraction with a denominator of one.
     *
     * @return BigRational The exact rational representation.
     */
    public function toBigRational(): BigRational
    {
        return BigRational::ofFraction(numerator: $this, denominator: BigInteger::one());
    }

    /**
     * Returns the largest integer dividing both this integer and another, never negative.
     *
     * @param BigInteger $other The integer to share a divisor with.
     * @return BigInteger A new instance holding the greatest common divisor.
     */
    public function greatestCommonDivisor(BigInteger $other): BigInteger
    {
        return new BigInteger(digits: $this->digits->greatestCommonDivisor(other: $other->digits));
    }
}
