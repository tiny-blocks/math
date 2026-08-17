<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Fractions;

use TinyBlocks\Math\Calculators;
use TinyBlocks\Math\Exceptions\DivisionByZero;
use TinyBlocks\Math\Exceptions\NonTerminatingDecimal;
use TinyBlocks\Math\Internal\Decimals\Digits;
use TinyBlocks\Math\Internal\Decimals\Rounding;
use TinyBlocks\Math\Internal\Decimals\Scale;
use TinyBlocks\Math\Internal\Exponent;
use TinyBlocks\Math\Internal\GreatestCommonDivisor;
use TinyBlocks\Math\RoundingMode;

final readonly class Fraction
{
    private const string TWO = '2';
    private const string FIVE = '5';
    private const string ZERO = '0';
    private const string MINUS = '-';

    private function __construct(public string $numerator, public string $denominator)
    {
    }

    public static function of(string $numerator, string $denominator): Fraction
    {
        $calculator = Calculators::active();

        if ($calculator->compare(left: $denominator, right: self::ZERO) === 0) {
            throw DivisionByZero::becauseDenominatorIsZero(numerator: $numerator);
        }

        $sign = (string)$calculator->compare(left: $denominator, right: self::ZERO);
        $signed = $calculator->multiply(left: $numerator, right: $sign);
        $positive = $calculator->multiply(left: $denominator, right: $sign);
        $divisor = new GreatestCommonDivisor()->of(left: ltrim($signed, self::MINUS), right: $positive);

        return new Fraction(
            numerator: $calculator->quotient(numerator: $signed, denominator: $divisor),
            denominator: $calculator->quotient(numerator: $positive, denominator: $divisor)
        );
    }

    public function plus(Fraction $other): Fraction
    {
        $calculator = Calculators::active();

        return Fraction::of(
            numerator: $calculator->add(
                left: $calculator->multiply(left: $this->numerator, right: $other->denominator),
                right: $calculator->multiply(left: $other->numerator, right: $this->denominator)
            ),
            denominator: $calculator->multiply(left: $this->denominator, right: $other->denominator)
        );
    }

    public function minus(Fraction $other): Fraction
    {
        return $this->plus(other: $other->negated());
    }

    public function power(int $exponent): Fraction
    {
        $calculator = Calculators::active();
        $bounded = Exponent::of(value: $exponent);
        $base = $exponent < 0 ? $this->reciprocal() : $this;

        return new Fraction(
            numerator: $calculator->power(base: $base->numerator, exponent: $bounded->magnitude),
            denominator: $calculator->power(base: $base->denominator, exponent: $bounded->magnitude)
        );
    }

    public function isZero(): bool
    {
        return $this->numerator === self::ZERO;
    }

    private function divides(Scale $scale): bool
    {
        return Calculators::active()->remainder(
            numerator: Digits::tenTo(exponent: $scale->value),
            denominator: $this->denominator
        ) === self::ZERO;
    }

    public function negated(): Fraction
    {
        return new Fraction(
            numerator: Calculators::active()->subtract(minuend: self::ZERO, subtrahend: $this->numerator),
            denominator: $this->denominator
        );
    }

    public function absolute(): Fraction
    {
        return new Fraction(numerator: ltrim($this->numerator, self::MINUS), denominator: $this->denominator);
    }

    public function toDigits(Scale $scale, RoundingMode $roundingMode): Digits
    {
        $calculator = Calculators::active();

        return Digits::of(
            scale: $scale,
            unscaled: new Rounding()->quotient(
                numerator: $calculator->multiply(left: $this->numerator, right: Digits::tenTo(exponent: $scale->value)),
                denominator: $this->denominator,
                roundingMode: $roundingMode
            )
        );
    }

    public function compareTo(Fraction $other): int
    {
        $calculator = Calculators::active();

        return $calculator->compare(
            left: $calculator->multiply(left: $this->numerator, right: $other->denominator),
            right: $calculator->multiply(left: $other->numerator, right: $this->denominator)
        );
    }

    public function dividedBy(Fraction $other): Fraction
    {
        $calculator = Calculators::active();

        return Fraction::of(
            numerator: $calculator->multiply(left: $this->numerator, right: $other->denominator),
            denominator: $calculator->multiply(left: $this->denominator, right: $other->numerator)
        );
    }

    public function exactScale(): Scale
    {
        $scale = $this->minimalScale();

        if (!$this->divides(scale: $scale)) {
            $template = '%s/%s';

            throw NonTerminatingDecimal::becauseExpansionRepeats(
                fraction: sprintf($template, $this->numerator, $this->denominator)
            );
        }

        return $scale;
    }

    public function isNegative(): bool
    {
        return str_starts_with($this->numerator, self::MINUS);
    }

    public function reciprocal(): Fraction
    {
        if ($this->isZero()) {
            throw DivisionByZero::becauseReciprocalOfZeroIsUndefined();
        }

        return Fraction::of(numerator: $this->denominator, denominator: $this->numerator);
    }

    private function factorCount(string $factor): int
    {
        $calculator = Calculators::active();
        $remaining = $this->denominator;
        $count = 0;

        while ($calculator->remainder(numerator: $remaining, denominator: $factor) === self::ZERO) {
            $remaining = $calculator->quotient(numerator: $remaining, denominator: $factor);
            $count++;
        }

        return $count;
    }

    private function minimalScale(): Scale
    {
        return Scale::of(value: max($this->factorCount(factor: self::TWO), $this->factorCount(factor: self::FIVE)));
    }

    public function multipliedBy(Fraction $other): Fraction
    {
        $calculator = Calculators::active();

        return Fraction::of(
            numerator: $calculator->multiply(left: $this->numerator, right: $other->numerator),
            denominator: $calculator->multiply(left: $this->denominator, right: $other->denominator)
        );
    }

    public function hasTerminatingDecimal(): bool
    {
        return $this->divides(scale: $this->minimalScale());
    }
}
