<?php

declare(strict_types=1);

namespace TinyBlocks\Math;

use TinyBlocks\Math\Exceptions\DivisionByZero;
use TinyBlocks\Math\Exceptions\ExponentOutOfRange;
use TinyBlocks\Math\Exceptions\InexactConversion;
use TinyBlocks\Math\Exceptions\IntegerOverflow;
use TinyBlocks\Math\Exceptions\NonTerminatingDecimal;
use TinyBlocks\Math\Exceptions\NumberNotWellFormed;
use TinyBlocks\Math\Exceptions\ScaleOutOfRange;
use TinyBlocks\Math\Internal\Decimals\Scale;
use TinyBlocks\Math\Internal\Fractions\Fraction;
use TinyBlocks\Math\Internal\NumberComparison;
use TinyBlocks\Math\Internal\StructuralHash;

/**
 * Exact fraction, always in the lowest terms with a strictly positive denominator.
 *
 * <p>This is the type that lets division stay total. Every quotient of two numbers is a rational,
 * so <code>dividedBy</code> never rounds and never raises for inexactness. The caller leaves
 * exactness behind when ready, by naming a scale and a rounding mode, or by asking for an exact
 * decimal and handling the case where none exists.</p>
 */
final readonly class BigRational implements Number
{
    use NumberComparison;

    private const string ONE = '1';
    private const string ZERO = '0';
    private const string SEPARATOR = '/';
    private const int FLOAT_SCALE = 341;

    private function __construct(private Fraction $fraction)
    {
    }

    /**
     * Creates a BigRational from a literal.
     *
     * <p>Accepts a fraction such as <code>'3/4'</code>, a decimal such as <code>'0.75'</code>, and
     * an integer such as <code>'3'</code>.</p>
     *
     * @param string|int $value The literal to read.
     * @return BigRational The created instance.
     * @throws NumberNotWellFormed If the literal is not a fraction, a decimal, or an integer, or if its
     *                            exponent exceeds 10000 in magnitude.
     * @throws InexactConversion If either term of a fraction carries a fractional part.
     * @throws DivisionByZero If the denominator is zero.
     */
    public static function of(string|int $value): BigRational
    {
        $literal = (string)$value;
        $parts = explode(self::SEPARATOR, $literal);

        if (count($parts) > 2) {
            throw NumberNotWellFormed::becauseValueIsNotNumeric(value: $literal);
        }

        if (count($parts) === 1) {
            return BigDecimal::of(value: $value)->toBigRational();
        }

        return BigRational::ofFraction(
            numerator: BigInteger::of(value: $parts[0]),
            denominator: BigInteger::of(value: $parts[1])
        );
    }

    /**
     * Creates a BigRational holding one.
     *
     * @return BigRational The created instance.
     */
    public static function one(): BigRational
    {
        return BigRational::of(value: self::ONE);
    }

    /**
     * Creates a BigRational holding zero.
     *
     * @return BigRational The created instance.
     */
    public static function zero(): BigRational
    {
        return BigRational::of(value: self::ZERO);
    }

    /**
     * Creates a BigRational from a numerator and a denominator.
     *
     * <p>The result is reduced to the lowest terms and the sign is carried by the numerator.</p>
     *
     * @param BigInteger $numerator The numerator.
     * @param BigInteger $denominator The denominator, never zero.
     * @return BigRational The created instance.
     * @throws DivisionByZero If the denominator is zero.
     */
    public static function ofFraction(BigInteger $numerator, BigInteger $denominator): BigRational
    {
        return new BigRational(
            fraction: Fraction::of(numerator: $numerator->toString(), denominator: $denominator->toString())
        );
    }

    /**
     * Adds another fraction to this one.
     *
     * @param BigRational $addend The fraction to add.
     * @return BigRational A new instance holding the exact sum.
     */
    public function plus(BigRational $addend): BigRational
    {
        return new BigRational(fraction: $this->fraction->plus(other: $addend->fraction));
    }

    /**
     * Subtracts another fraction from this one.
     *
     * @param BigRational $subtrahend The fraction to subtract.
     * @return BigRational A new instance holding the exact difference.
     */
    public function minus(BigRational $subtrahend): BigRational
    {
        return new BigRational(fraction: $this->fraction->minus(other: $subtrahend->fraction));
    }

    /**
     * Raises this fraction to a power.
     *
     * <p>Unlike {@see BigInteger} and {@see BigDecimal}, a negative exponent is supported, because
     * the rationals are closed under it.</p>
     *
     * @param int $exponent The exponent, positive, zero, or negative.
     * @return BigRational A new instance holding the exact power.
     * @throws ExponentOutOfRange If the exponent is beyond the supported magnitude.
     * @throws DivisionByZero If the exponent is negative and this fraction is zero.
     */
    public function power(int $exponent): BigRational
    {
        return new BigRational(fraction: $this->fraction->power(exponent: $exponent));
    }

    /**
     * Tells whether this fraction holds the same numerator and denominator as another fraction.
     *
     * <p>Structural equality. Because a fraction is always stored in lowest terms, two instances
     * holding the same value are always equal. To compare against a number of another type, use
     * <code>{@see Number::isEqualTo()}</code>.</p>
     *
     * @param BigRational $other The fraction to compare against.
     * @return bool True when both hold the same fraction.
     */
    public function equals(BigRational $other): bool
    {
        return $other->fraction->numerator === $this->fraction->numerator
            && $other->fraction->denominator === $this->fraction->denominator;
    }

    /**
     * Tells whether this fraction is zero.
     *
     * @return bool True when the numerator is zero.
     */
    public function isZero(): bool
    {
        return $this->fraction->isZero();
    }

    /**
     * Returns this fraction with the opposite sign.
     *
     * @return BigRational A new instance with the sign flipped.
     */
    public function negated(): BigRational
    {
        return new BigRational(fraction: $this->fraction->negated());
    }

    /**
     * Returns this fraction as a native float.
     *
     * <p>The expansion runs past the smallest positive float before it is read, so a fraction of any
     * magnitude keeps every significant digit a float can hold. One smaller than the smallest
     * positive float reads back as zero, because no float represents it.</p>
     *
     * @return float The nearest float, which may lose precision.
     * @throws IntegerOverflow If the value is outside the native float range.
     */
    public function toFloat(): float
    {
        return $this->toDecimal(scale: self::FLOAT_SCALE, rounding: RoundingMode::HalfEven)->toFloat();
    }

    /**
     * Returns the magnitude of this fraction.
     *
     * @return BigRational A new instance with the sign removed.
     */
    public function absolute(): BigRational
    {
        return new BigRational(fraction: $this->fraction->absolute());
    }

    /**
     * Returns a deterministic hash of this fraction.
     *
     * @return string The structural hash.
     */
    public function hashCode(): string
    {
        return new StructuralHash()->of(type: BigRational::class, representation: $this->toString());
    }

    /**
     * Returns this fraction as a string.
     *
     * @return string The numerator and denominator separated by a slash, or just the numerator
     *                when the denominator is one.
     */
    public function toString(): string
    {
        if ($this->fraction->denominator === self::ONE) {
            return $this->fraction->numerator;
        }

        $template = '%s/%s';

        return sprintf($template, $this->fraction->numerator, $this->fraction->denominator);
    }

    /**
     * Compares this fraction with another number.
     *
     * @param Number $other The number to compare against.
     * @return int A negative number, zero, or a positive number.
     */
    public function compareTo(Number $other): int
    {
        return $this->fraction->compareTo(other: $other->toBigRational()->fraction);
    }

    /**
     * Divides this fraction by another, exactly.
     *
     * @param BigRational $divisor The fraction to divide by, never zero.
     * @return BigRational A new instance holding the exact quotient.
     * @throws DivisionByZero If the divisor is zero.
     */
    public function dividedBy(BigRational $divisor): BigRational
    {
        if ($divisor->isZero()) {
            throw DivisionByZero::becauseDivisorIsZero(dividend: $this->toString());
        }

        return new BigRational(fraction: $this->fraction->dividedBy(other: $divisor->fraction));
    }

    /**
     * Returns the numerator, carrying the sign of the fraction.
     *
     * @return BigInteger The numerator.
     */
    public function numerator(): BigInteger
    {
        return BigInteger::of(value: $this->fraction->numerator);
    }

    /**
     * Returns this fraction as a decimal, rounded to the given scale.
     *
     * @param int $scale The number of digits after the decimal point.
     * @param RoundingMode $rounding The policy applied to the discarded digits.
     * @return BigDecimal The rounded decimal.
     * @throws ScaleOutOfRange If the scale is negative or beyond the supported range.
     */
    public function toDecimal(int $scale, RoundingMode $rounding): BigDecimal
    {
        return BigDecimal::of(
            value: $this->fraction->toDigits(scale: Scale::of(value: $scale), roundingMode: $rounding)->value()
        );
    }

    /**
     * Tells whether this fraction is strictly less than zero.
     *
     * @return bool True when the value is negative.
     */
    public function isNegative(): bool
    {
        return $this->fraction->isNegative();
    }

    /**
     * Returns the reciprocal of this fraction.
     *
     * @return BigRational A new instance with the numerator and denominator swapped.
     * @throws DivisionByZero If this fraction is zero.
     */
    public function reciprocal(): BigRational
    {
        return new BigRational(fraction: $this->fraction->reciprocal());
    }

    /**
     * Returns the denominator, always strictly positive.
     *
     * @return BigInteger The denominator.
     */
    public function denominator(): BigInteger
    {
        return BigInteger::of(value: $this->fraction->denominator);
    }

    /**
     * Multiplies this fraction by another.
     *
     * @param BigRational $multiplier The fraction to multiply by.
     * @return BigRational A new instance holding the exact product.
     */
    public function multipliedBy(BigRational $multiplier): BigRational
    {
        return new BigRational(fraction: $this->fraction->multipliedBy(other: $multiplier->fraction));
    }

    /**
     * Returns this fraction as an integer.
     *
     * @return BigInteger The integer value.
     * @throws InexactConversion If the denominator is not one.
     */
    public function toBigInteger(): BigInteger
    {
        if ($this->fraction->denominator !== self::ONE) {
            throw InexactConversion::becauseFractionalPartWouldBeLost(value: $this->toString());
        }

        return $this->numerator();
    }

    /**
     * Returns this fraction as a JSON string.
     *
     * @return string The canonical string form.
     */
    public function jsonSerialize(): string
    {
        return $this->toString();
    }

    /**
     * Returns this fraction unchanged.
     *
     * @return BigRational This instance.
     */
    public function toBigRational(): BigRational
    {
        return $this;
    }

    /**
     * Returns this fraction as a decimal without rounding.
     *
     * @return BigDecimal The exact decimal, at the smallest scale that represents it.
     * @throws NonTerminatingDecimal If the decimal expansion repeats forever.
     */
    public function toDecimalExact(): BigDecimal
    {
        return BigDecimal::of(
            value: $this->fraction
                ->toDigits(scale: $this->fraction->exactScale(), roundingMode: RoundingMode::Down)
                ->value()
        );
    }

    /**
     * Tells whether this fraction has a terminating decimal expansion.
     *
     * <p>True when the reduced denominator is a product of twos and fives. Asking this is the
     * cheap alternative to calling <code>{@see BigRational::toDecimalExact()}</code> and catching
     * the failure.</p>
     *
     * @return bool True when an exact decimal exists.
     */
    public function hasTerminatingDecimal(): bool
    {
        return $this->fraction->hasTerminatingDecimal();
    }
}
