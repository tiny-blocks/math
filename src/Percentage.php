<?php

declare(strict_types=1);

namespace TinyBlocks\Math;

use JsonSerializable;
use TinyBlocks\Math\Exceptions\NumberNotWellFormed;
use TinyBlocks\Math\Exceptions\ScaleOutOfRange;
use TinyBlocks\Math\Internal\StructuralHash;

/**
 * Rate expressed per hundred.
 *
 * <p><code>Percentage::of(value: '12.5')</code> is twelve and a half percent. The value is held as
 * a {@see BigDecimal}, so applying it to an amount is a multiplication and therefore exact: no
 * rounding decision is forced on the caller until the result is presented.</p>
 *
 * <p>Negative percentages and percentages above one hundred are legal, because a negative growth
 * rate and a one hundred and fifty percent increase are both real. There is no invariant to
 * enforce here and therefore no failure to raise.</p>
 */
final readonly class Percentage implements JsonSerializable
{
    private const string SIGN = '%';
    private const string ZERO = '0';
    private const int PLACES_IN_A_HUNDRED = 2;

    private function __construct(private BigDecimal $percent)
    {
    }

    /**
     * Creates a Percentage from a literal expressed per hundred.
     *
     * @param string|int $value The literal, where <code>'12.5'</code> means twelve and a half percent.
     *                          A trailing percent sign is accepted, so the string form reads back.
     * @return Percentage The created instance.
     * @throws NumberNotWellFormed If the literal is not a number, or its exponent exceeds 10000 in magnitude.
     * @throws ScaleOutOfRange If the literal implies a scale beyond the supported range.
     */
    public static function of(string|int $value): Percentage
    {
        $literal = (string)$value;
        $rate = str_ends_with($literal, self::SIGN) ? substr($literal, 0, -1) : $literal;

        return new Percentage(percent: BigDecimal::of(value: $rate));
    }

    /**
     * Creates a Percentage holding zero.
     *
     * @return Percentage The created instance.
     */
    public static function zero(): Percentage
    {
        return Percentage::of(value: self::ZERO);
    }

    /**
     * Creates a Percentage from a ratio, rounded to the given scale.
     *
     * <p>A scale is required because a proportion such as one third has no exact percentage.</p>
     *
     * @param Ratio $ratio The proportion to express per hundred.
     * @param int $scale The number of digits after the decimal point in the percentage.
     * @param RoundingMode $rounding The policy applied to the discarded digits.
     * @return Percentage The created instance.
     * @throws ScaleOutOfRange If the scale is negative or beyond the supported range.
     */
    public static function fromRatio(Ratio $ratio, int $scale, RoundingMode $rounding): Percentage
    {
        return $ratio->toPercentage(scale: $scale, rounding: $rounding);
    }

    /**
     * Returns this percentage as the factor it multiplies by.
     *
     * <p>Twelve and a half percent reads back as <code>0.125</code>.</p>
     *
     * @return BigDecimal The factor, at this percentage's scale plus two.
     */
    public function rate(): BigDecimal
    {
        return BigDecimal::ofUnscaledValue(
            scale: ($this->percent->scale() + self::PLACES_IN_A_HUNDRED),
            unscaled: $this->percent->unscaledValue()
        );
    }

    /**
     * Tells whether this percentage holds the same value and scale as another percentage.
     *
     * <p>Structural equality, so ten percent written <code>'10'</code> does not equal the same rate
     * written <code>'10.0'</code>. For arithmetic equality, use
     * <code>{@see Percentage::isEqualTo()}</code>.</p>
     *
     * @param Percentage $other The percentage to compare against.
     * @return bool True when both hold the same value at the same scale.
     */
    public function equals(Percentage $other): bool
    {
        return $other->percent->equals(other: $this->percent);
    }

    /**
     * Tells whether this percentage is zero.
     *
     * @return bool True when the value is zero.
     */
    public function isZero(): bool
    {
        return $this->percent->isZero();
    }

    /**
     * Applies this percentage to an amount.
     *
     * @param BigDecimal $amount The amount to take a percentage of.
     * @return BigDecimal The exact result, at the amount's scale plus this percentage's scale plus two.
     */
    public function applyTo(BigDecimal $amount): BigDecimal
    {
        return $amount->multipliedBy(multiplier: $this->rate());
    }

    /**
     * Returns this percentage as a ratio.
     *
     * @return Ratio The equivalent proportion, in lowest terms.
     */
    public function toRatio(): Ratio
    {
        return Ratio::between(antecedent: $this->rate(), consequent: BigDecimal::one());
    }

    /**
     * Reduces an amount by this percentage.
     *
     * @param BigDecimal $amount The amount to reduce.
     * @return BigDecimal The exact result of the amount minus this percentage of it.
     */
    public function decrease(BigDecimal $amount): BigDecimal
    {
        return $amount->minus(subtrahend: $this->applyTo(amount: $amount));
    }

    /**
     * Returns a deterministic hash of this percentage.
     *
     * @return string The structural hash.
     */
    public function hashCode(): string
    {
        return new StructuralHash()->of(type: Percentage::class, representation: $this->toString());
    }

    /**
     * Raises an amount by this percentage.
     *
     * @param BigDecimal $amount The amount to raise.
     * @return BigDecimal The exact result of the amount plus this percentage of it.
     */
    public function increase(BigDecimal $amount): BigDecimal
    {
        return $amount->plus(addend: $this->applyTo(amount: $amount));
    }

    /**
     * Returns this percentage as a string.
     *
     * @return string The value followed by a percent sign, such as <code>12.5%</code>.
     */
    public function toString(): string
    {
        $template = '%s%%';

        return sprintf($template, $this->percent->toString());
    }

    /**
     * Tells whether this percentage has the same value as another.
     *
     * @param Percentage $other The percentage to compare against.
     * @return bool True when both represent the same rate, at any scale.
     */
    public function isEqualTo(Percentage $other): bool
    {
        return $this->percent->isEqualTo(other: $other->percent);
    }

    /**
     * Tells whether this percentage is strictly less than another.
     *
     * @param Percentage $other The percentage to compare against.
     * @return bool True when this rate is smaller.
     */
    public function isLessThan(Percentage $other): bool
    {
        return $this->percent->isLessThan(other: $other->percent);
    }

    /**
     * Tells whether this percentage is strictly less than zero.
     *
     * @return bool True when the rate is negative.
     */
    public function isNegative(): bool
    {
        return $this->percent->isNegative();
    }

    /**
     * Tells whether this percentage is strictly greater than zero.
     *
     * @return bool True when the rate is positive.
     */
    public function isPositive(): bool
    {
        return $this->percent->isPositive();
    }

    /**
     * Tells whether this percentage is strictly greater than another.
     *
     * @param Percentage $other The percentage to compare against.
     * @return bool True when this rate is larger.
     */
    public function isGreaterThan(Percentage $other): bool
    {
        return $this->percent->isGreaterThan(other: $other->percent);
    }

    /**
     * Returns this percentage as a JSON string.
     *
     * @return string The canonical string form, percent sign included.
     */
    public function jsonSerialize(): string
    {
        return $this->toString();
    }

    /**
     * Tells whether this percentage is less than or equal to another.
     *
     * @param Percentage $other The percentage to compare against.
     * @return bool True when this rate is smaller or equal.
     */
    public function isLessThanOrEqualTo(Percentage $other): bool
    {
        return $this->percent->isLessThanOrEqualTo(other: $other->percent);
    }

    /**
     * Tells whether this percentage is greater than or equal to another.
     *
     * @param Percentage $other The percentage to compare against.
     * @return bool True when this rate is larger or equal.
     */
    public function isGreaterThanOrEqualTo(Percentage $other): bool
    {
        return $this->percent->isGreaterThanOrEqualTo(other: $other->percent);
    }
}
