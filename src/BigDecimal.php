<?php

declare(strict_types=1);

namespace TinyBlocks\Math;

use TinyBlocks\Math\Exceptions\DivisionByZero;
use TinyBlocks\Math\Exceptions\ExponentOutOfRange;
use TinyBlocks\Math\Exceptions\InexactConversion;
use TinyBlocks\Math\Exceptions\IntegerOverflow;
use TinyBlocks\Math\Exceptions\NegativeExponent;
use TinyBlocks\Math\Exceptions\NegativeRoot;
use TinyBlocks\Math\Exceptions\NegativeWeight;
use TinyBlocks\Math\Exceptions\NumberNotWellFormed;
use TinyBlocks\Math\Exceptions\ScaleOutOfRange;
use TinyBlocks\Math\Internal\Allocations\Allocation;
use TinyBlocks\Math\Internal\Decimals\Digits;
use TinyBlocks\Math\Internal\Decimals\Scale;
use TinyBlocks\Math\Internal\Decimals\SquareRoot;
use TinyBlocks\Math\Internal\NumberComparison;
use TinyBlocks\Math\Internal\StructuralHash;

/**
 * Arbitrary-precision decimal, held as an unscaled integer and a non-negative scale.
 *
 * <p>Addition, subtraction and multiplication are exact and never round. Division returns a
 * {@see BigRational}, so it is exact for every pair of operands and raises only on a zero divisor.
 * Rounding happens where the caller asks for it, by naming both a scale and a
 * {@see RoundingMode}.</p>
 *
 * <p>Scale propagates the way Java's <code>BigDecimal</code> specifies it: the maximum of the two
 * operands for addition and subtraction, their sum for multiplication, and the scale times the
 * exponent for a power.</p>
 */
final readonly class BigDecimal implements Number
{
    use NumberComparison;

    private const string ONE = '1';
    private const string ZERO = '0';

    private function __construct(private Digits $digits)
    {
    }

    /**
     * Creates a BigDecimal from a literal.
     *
     * <p>Accepts positional notation such as <code>'19.99'</code> and scientific notation such as
     * <code>'1.5e-3'</code>. The scale of the result is the number of digits the literal carries
     * after the decimal point, so <code>'1.50'</code> keeps its trailing zero.</p>
     *
     * @param string|int $value The literal to read.
     * @return BigDecimal The created instance.
     * @throws NumberNotWellFormed If the literal is not a number, or its exponent exceeds 10000 in magnitude.
     * @throws ScaleOutOfRange If the literal implies a scale beyond the supported range.
     */
    public static function of(string|int $value): BigDecimal
    {
        return new BigDecimal(digits: Digits::from(value: $value));
    }

    /**
     * Creates a BigDecimal holding one, at scale zero.
     *
     * @return BigDecimal The created instance.
     */
    public static function one(): BigDecimal
    {
        return BigDecimal::of(value: self::ONE);
    }

    /**
     * Creates a BigDecimal holding zero, at scale zero.
     *
     * @return BigDecimal The created instance.
     */
    public static function zero(): BigDecimal
    {
        return BigDecimal::of(value: self::ZERO);
    }

    /**
     * Creates a BigDecimal from a native float.
     *
     * <p>The only lossy entry point in the library, and named so. The float is read as the
     * shortest decimal that round-trips to the same float, so <code>0.1</code> becomes
     * <code>'0.1'</code> rather than its exact binary expansion. Prefer a string literal whenever
     * one is available.</p>
     *
     * <p>The shortest form is found by widening the significant digits until the decimal casts back
     * to the same float. Nothing here reads an ini directive, so the result is the same in every
     * process.</p>
     *
     * @param float $value The float to read.
     * @return BigDecimal The created instance.
     * @throws NumberNotWellFormed If the float is NAN or infinite, since neither is a number.
     */
    public static function fromFloat(float $value): BigDecimal
    {
        return new BigDecimal(digits: Digits::fromFloat(value: $value));
    }

    /**
     * Creates a BigDecimal from an unscaled integer and a scale.
     *
     * @param int $scale The number of digits after the decimal point.
     * @param BigInteger $unscaled The digits, with the decimal point removed.
     * @return BigDecimal The created instance.
     * @throws ScaleOutOfRange If the scale is negative or beyond the supported range.
     */
    public static function ofUnscaledValue(int $scale, BigInteger $unscaled): BigDecimal
    {
        return new BigDecimal(
            digits: Digits::of(scale: Scale::of(value: $scale), unscaled: $unscaled->toString())
        );
    }

    /**
     * Adds another decimal to this one, exactly.
     *
     * @param BigDecimal $addend The decimal to add.
     * @return BigDecimal A new instance whose scale is the larger of the two.
     */
    public function plus(BigDecimal $addend): BigDecimal
    {
        return new BigDecimal(digits: $this->digits->plus(other: $addend->digits));
    }

    /**
     * Subtracts another decimal from this one, exactly.
     *
     * @param BigDecimal $subtrahend The decimal to subtract.
     * @return BigDecimal A new instance whose scale is the larger of the two.
     */
    public function minus(BigDecimal $subtrahend): BigDecimal
    {
        return new BigDecimal(digits: $this->digits->minus(other: $subtrahend->digits));
    }

    /**
     * Raises this decimal to a non-negative power, exactly.
     *
     * @param int $exponent The exponent, never negative.
     * @return BigDecimal A new instance whose scale is this scale times the exponent.
     * @throws NegativeExponent If the exponent is negative.
     * @throws ExponentOutOfRange If the exponent is beyond the supported magnitude.
     * @throws ScaleOutOfRange If the resulting scale is beyond the supported range.
     */
    public function power(int $exponent): BigDecimal
    {
        if ($exponent < 0) {
            throw NegativeExponent::becauseExponentIsNegative(exponent: $exponent);
        }

        return new BigDecimal(digits: $this->digits->power(exponent: $exponent));
    }

    /**
     * Returns the number of digits after the decimal point.
     *
     * @return int The scale.
     */
    public function scale(): int
    {
        return $this->digits->scale->value;
    }

    /**
     * Tells whether this decimal holds the same digits and scale as another decimal.
     *
     * <p>Structural equality, so <code>1.0</code> does not equal <code>1.00</code>. For arithmetic
     * equality, and for comparing against a number of another type, use
     * <code>{@see Number::isEqualTo()}</code>.</p>
     *
     * @param BigDecimal $other The decimal to compare against.
     * @return bool True when both hold the same digits at the same scale.
     */
    public function equals(BigDecimal $other): bool
    {
        return $other->digits->unscaled === $this->digits->unscaled
            && $other->digits->scale->equals(other: $this->digits->scale);
    }

    /**
     * Tells whether this decimal is zero.
     *
     * @return bool True when the value is zero, at any scale.
     */
    public function isZero(): bool
    {
        return $this->digits->isZero();
    }

    /**
     * Returns this decimal with the opposite sign.
     *
     * @return BigDecimal A new instance with the sign flipped, at the same scale.
     */
    public function negated(): BigDecimal
    {
        return new BigDecimal(digits: $this->digits->negated());
    }

    /**
     * Returns this decimal as a native float.
     *
     * @return float The nearest float, which may lose precision.
     * @throws IntegerOverflow If the value is outside the native float range.
     */
    public function toFloat(): float
    {
        $float = (float)$this->digits->value();

        if (is_infinite($float)) {
            throw IntegerOverflow::becauseValueExceedsFloatRange(value: $this->toString());
        }

        return $float;
    }

    /**
     * Returns this decimal at another scale, rounding as instructed.
     *
     * @param int $scale The target number of digits after the decimal point.
     * @param RoundingMode $rounding The policy applied to the discarded digits.
     * @return BigDecimal A new instance at the requested scale.
     * @throws ScaleOutOfRange If the scale is negative or beyond the supported range.
     */
    public function toScale(int $scale, RoundingMode $rounding): BigDecimal
    {
        return new BigDecimal(
            digits: $this->digits->withScale(scale: Scale::of(value: $scale), roundingMode: $rounding)
        );
    }

    /**
     * Returns the magnitude of this decimal.
     *
     * @return BigDecimal A new instance with the sign removed, at the same scale.
     */
    public function absolute(): BigDecimal
    {
        return new BigDecimal(digits: $this->digits->absolute());
    }

    /**
     * Splits this decimal among weights so that the parts sum back to it exactly.
     *
     * <p>Each part is the exact share truncated toward negative infinity at the given scale, and
     * the units left over are handed out one at a time in descending order of the discarded
     * remainder, ties broken by position. Plain rounding cannot preserve the total, which is why
     * this exists.</p>
     *
     * @param int $scale The number of digits after the decimal point in every part.
     * @param BigDecimals $weights The weights, in order, each of them non-negative.
     * @return BigDecimals The parts, in the order of the weights.
     * @throws InexactConversion If this decimal cannot be represented at the given scale.
     * @throws NegativeWeight If any weight is negative.
     * @throws DivisionByZero If the weights sum to zero.
     * @throws ScaleOutOfRange If the scale is negative or beyond the supported range.
     */
    public function allocate(int $scale, BigDecimals $weights): BigDecimals
    {
        $weighed = [];

        foreach ($weights as $weight) {
            $weighed[] = $weight->digits;
        }

        $parts = [];

        $allocated = new Allocation()->of(
            scale: Scale::of(value: $scale),
            amount: $this->toScaleExact(scale: $scale)->digits,
            weights: $weighed
        );

        foreach ($allocated as $part) {
            $parts[] = new BigDecimal(digits: $part);
        }

        return BigDecimals::from(...$parts);
    }

    /**
     * Returns a deterministic hash of this decimal.
     *
     * @return string The structural hash.
     */
    public function hashCode(): string
    {
        return new StructuralHash()->of(type: BigDecimal::class, representation: $this->toString());
    }

    /**
     * Returns this decimal as a string.
     *
     * <p>Always positional notation, never scientific, and trailing zeros are preserved because
     * they are the scale.</p>
     *
     * @return string The canonical string form.
     */
    public function toString(): string
    {
        return $this->digits->value();
    }

    /**
     * Compares this decimal with another number.
     *
     * @param Number $other The number to compare against.
     * @return int A negative number, zero, or a positive number.
     */
    public function compareTo(Number $other): int
    {
        return $other instanceof BigDecimal
            ? $this->digits->compareTo(other: $other->digits)
            : $this->toBigRational()->compareTo(other: $other);
    }

    /**
     * Divides this decimal by another, exactly.
     *
     * @param BigDecimal $divisor The decimal to divide by, never zero.
     * @return BigRational The exact quotient.
     * @throws DivisionByZero If the divisor is zero.
     */
    public function dividedBy(BigDecimal $divisor): BigRational
    {
        return $this->toBigRational()->dividedBy(divisor: $divisor->toBigRational());
    }

    /**
     * Tells whether this decimal is strictly less than zero.
     *
     * @return bool True when the value is negative.
     */
    public function isNegative(): bool
    {
        return $this->digits->isNegative();
    }

    /**
     * Returns the square root of this decimal at the given scale.
     *
     * @param int $scale The number of digits after the decimal point in the result.
     * @param RoundingMode $rounding The policy applied to the discarded digits.
     * @return BigDecimal A new instance holding the root.
     * @throws NegativeRoot If this decimal is negative.
     * @throws ScaleOutOfRange If the scale is negative or beyond the supported range.
     */
    public function squareRoot(int $scale, RoundingMode $rounding): BigDecimal
    {
        if ($this->isNegative()) {
            throw NegativeRoot::becauseRadicandIsNegative(radicand: $this->toString());
        }

        return new BigDecimal(
            digits: new SquareRoot()->of(
                scale: Scale::of(value: $scale),
                radicand: $this->digits,
                roundingMode: $rounding
            )
        );
    }

    /**
     * Returns the integer part of this decimal, truncated toward zero.
     *
     * @return BigInteger The digits before the decimal point.
     */
    public function integralPart(): BigInteger
    {
        return BigInteger::of(value: $this->digits->integralPart());
    }

    /**
     * Multiplies this decimal by another, exactly.
     *
     * @param BigDecimal $multiplier The decimal to multiply by.
     * @return BigDecimal A new instance whose scale is the sum of the two.
     */
    public function multipliedBy(BigDecimal $multiplier): BigDecimal
    {
        return new BigDecimal(digits: $this->digits->multipliedBy(other: $multiplier->digits));
    }

    /**
     * Returns this decimal as an integer.
     *
     * @return BigInteger The integer value.
     * @throws InexactConversion If the fractional part is not zero.
     */
    public function toBigInteger(): BigInteger
    {
        return BigInteger::of(value: $this->digits->value());
    }

    /**
     * Returns this decimal at another scale, without rounding.
     *
     * @param int $scale The target number of digits after the decimal point.
     * @return BigDecimal A new instance at the requested scale.
     * @throws InexactConversion If digits would be discarded.
     * @throws ScaleOutOfRange If the scale is negative or beyond the supported range.
     */
    public function toScaleExact(int $scale): BigDecimal
    {
        $rescaled = $this->toScale(scale: $scale, rounding: RoundingMode::Down);

        if ($rescaled->digits->compareTo(other: $this->digits) !== 0) {
            throw InexactConversion::becauseScaleIsTooSmall(scale: $scale, value: $this->toString());
        }

        return $rescaled;
    }

    /**
     * Returns this decimal as a JSON string.
     *
     * @return string The canonical string form.
     */
    public function jsonSerialize(): string
    {
        return $this->toString();
    }

    /**
     * Returns this decimal as an exact fraction.
     *
     * @return BigRational The exact rational representation.
     */
    public function toBigRational(): BigRational
    {
        return BigRational::ofFraction(
            numerator: BigInteger::of(value: $this->digits->unscaled),
            denominator: BigInteger::of(value: $this->digits->scaleFactor())
        );
    }

    /**
     * Returns the unscaled digits of this decimal.
     *
     * @return BigInteger The value with the decimal point removed.
     */
    public function unscaledValue(): BigInteger
    {
        return BigInteger::of(value: $this->digits->unscaled);
    }

    /**
     * Returns the fractional part of this decimal.
     *
     * @return BigDecimal A new instance at the same scale, carrying the sign of this decimal.
     */
    public function fractionalPart(): BigDecimal
    {
        return $this->minus(subtrahend: $this->integralPart()->toBigDecimal());
    }

    /**
     * Returns this decimal at the smallest scale that represents the same value.
     *
     * @return BigDecimal A new instance without trailing zeros.
     */
    public function withoutTrailingZeros(): BigDecimal
    {
        return new BigDecimal(digits: $this->digits->withoutTrailingZeros());
    }
}
