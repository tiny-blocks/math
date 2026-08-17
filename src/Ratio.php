<?php

declare(strict_types=1);

namespace TinyBlocks\Math;

use JsonSerializable;
use TinyBlocks\Math\Exceptions\DivisionByZero;
use TinyBlocks\Math\Exceptions\InexactConversion;
use TinyBlocks\Math\Exceptions\NumberNotWellFormed;
use TinyBlocks\Math\Exceptions\ScaleOutOfRange;
use TinyBlocks\Math\Internal\StructuralHash;

/**
 * Exact proportion between two quantities, written antecedent to consequent.
 *
 * <p>Held as a {@see BigRational} in lowest terms, so <code>4:2</code> and <code>2:1</code> are the
 * same ratio. Where a {@see BigRational} is a quantity, a Ratio is a relationship: it is applied to
 * an amount rather than added to one, and it reads back as <code>16:9</code>.</p>
 *
 * <p><code>{@see Ratio::between()}</code> derives a proportion from two arbitrary numbers without
 * anyone naming a scale, because the result stays exact.</p>
 */
final readonly class Ratio implements JsonSerializable
{
    private const string HUNDRED = '100';
    private const string SEPARATOR = ':';

    private function __construct(private BigRational $proportion)
    {
    }

    /**
     * Creates a Ratio from two integer terms.
     *
     * @param int|string $antecedent The first term.
     * @param int|string $consequent The second term, never zero.
     * @return Ratio The created instance.
     * @throws NumberNotWellFormed If either term is not a number.
     * @throws InexactConversion If either term carries a non-zero fractional part.
     * @throws DivisionByZero If the consequent is zero.
     */
    public static function of(int|string $antecedent, int|string $consequent): Ratio
    {
        return new Ratio(
            proportion: BigRational::ofFraction(
                numerator: BigInteger::of(value: $antecedent),
                denominator: BigInteger::of(value: $consequent)
            )
        );
    }

    /**
     * Creates a Ratio from its two terms written as one string.
     *
     * <p>Reads the form <code>{@see Ratio::toString()}</code> emits, so
     * <code>'16:9'</code> reads back as the ratio it came from.</p>
     *
     * @param string $value The two terms separated by a colon.
     * @return Ratio The created instance.
     * @throws NumberNotWellFormed If the string does not carry exactly two terms, or either is not a number.
     * @throws InexactConversion If either term carries a non-zero fractional part.
     * @throws DivisionByZero If the consequent is zero.
     */
    public static function from(string $value): Ratio
    {
        $terms = explode(self::SEPARATOR, $value);

        if (count($terms) !== 2) {
            throw NumberNotWellFormed::becauseValueIsNotNumeric(value: $value);
        }

        return Ratio::of(antecedent: $terms[0], consequent: $terms[1]);
    }

    /**
     * Creates a Ratio from two numbers of any kind.
     *
     * <p>Exact, so no scale and no rounding mode are involved.</p>
     *
     * @param Number $antecedent The first quantity.
     * @param Number $consequent The second quantity, never zero.
     * @return Ratio The created instance.
     * @throws DivisionByZero If the consequent is zero.
     */
    public static function between(Number $antecedent, Number $consequent): Ratio
    {
        return new Ratio(
            proportion: $antecedent->toBigRational()->dividedBy(divisor: $consequent->toBigRational())
        );
    }

    /**
     * Tells whether this ratio holds the same proportion as another ratio.
     *
     * <p>Structural equality. Because a ratio is always stored in lowest terms,
     * <code>32:18</code> equals <code>16:9</code>.</p>
     *
     * @param Ratio $other The ratio to compare against.
     * @return bool True when both hold the same proportion.
     */
    public function equals(Ratio $other): bool
    {
        return $other->proportion->equals(other: $this->proportion);
    }

    /**
     * Applies this ratio to an amount.
     *
     * @param Number $amount The amount to scale.
     * @return BigRational The exact result of the amount times the ratio.
     */
    public function applyTo(Number $amount): BigRational
    {
        return $this->proportion->multipliedBy(multiplier: $amount->toBigRational());
    }

    /**
     * Returns a deterministic hash of this ratio.
     *
     * @return string The structural hash.
     */
    public function hashCode(): string
    {
        return new StructuralHash()->of(type: Ratio::class, representation: $this->toString());
    }

    /**
     * Returns this ratio with its two terms swapped.
     *
     * @return Ratio A new instance holding the inverse proportion.
     * @throws DivisionByZero If the antecedent is zero.
     */
    public function inverted(): Ratio
    {
        return new Ratio(proportion: $this->proportion->reciprocal());
    }

    /**
     * Returns this ratio as a string.
     *
     * @return string The two terms separated by a colon, such as <code>16:9</code>.
     */
    public function toString(): string
    {
        $template = '%s:%s';

        return sprintf(
            $template,
            $this->proportion->numerator()->toString(),
            $this->proportion->denominator()->toString()
        );
    }

    /**
     * Returns the first term of this ratio.
     *
     * @return BigInteger The antecedent, carrying the sign of the ratio.
     */
    public function antecedent(): BigInteger
    {
        return $this->proportion->numerator();
    }

    /**
     * Returns the second term of this ratio.
     *
     * @return BigInteger The consequent, always strictly positive.
     */
    public function consequent(): BigInteger
    {
        return $this->proportion->denominator();
    }

    /**
     * Returns this ratio as a percentage, rounded to the given scale.
     *
     * @param int $scale The number of digits after the decimal point in the percentage.
     * @param RoundingMode $rounding The policy applied to the discarded digits.
     * @return Percentage The equivalent percentage.
     * @throws ScaleOutOfRange If the scale is negative or beyond the supported range.
     */
    public function toPercentage(int $scale, RoundingMode $rounding): Percentage
    {
        return Percentage::of(
            value: $this->proportion
                ->multipliedBy(multiplier: BigRational::of(value: self::HUNDRED))
                ->toDecimal(scale: $scale, rounding: $rounding)
                ->toString()
        );
    }

    /**
     * Returns this ratio as a JSON string.
     *
     * @return string The canonical string form, such as <code>16:9</code>.
     */
    public function jsonSerialize(): string
    {
        return $this->toString();
    }

    /**
     * Returns this ratio as an exact fraction.
     *
     * @return BigRational The proportion, in lowest terms.
     */
    public function toBigRational(): BigRational
    {
        return $this->proportion;
    }
}
