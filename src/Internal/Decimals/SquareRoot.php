<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Decimals;

use TinyBlocks\Math\Calculators;
use TinyBlocks\Math\RoundingMode;

final readonly class SquareRoot
{
    private const string ONE = '1';
    private const string TWO = '2';
    private const string FOUR = '4';

    public function of(Scale $scale, Digits $radicand, RoundingMode $roundingMode): Digits
    {
        $calculator = Calculators::active();
        $numerator = $radicand->shiftedBy(places: ($scale->value * 2));
        $denominator = Digits::tenTo(exponent: $radicand->scale->value);
        $whole = $calculator->squareRoot(
            radicand: $calculator->quotient(numerator: $numerator, denominator: $denominator)
        );

        return Digits::of(
            scale: $scale,
            unscaled: $this->rounded(
                whole: $whole,
                numerator: $numerator,
                denominator: $denominator,
                roundingMode: $roundingMode
            )
        );
    }

    private function rounded(string $whole, string $numerator, string $denominator, RoundingMode $roundingMode): string
    {
        $calculator = Calculators::active();
        $exact = $calculator->multiply(left: $calculator->multiply(left: $whole, right: $whole), right: $denominator);

        if ($calculator->compare(left: $numerator, right: $exact) === 0) {
            return $whole;
        }

        $halfway = $calculator->add(left: $calculator->multiply(left: $whole, right: self::TWO), right: self::ONE);

        return new Rounding()->nearest(
            whole: $whole,
            comparison: $calculator->compare(
                left: $calculator->multiply(left: self::FOUR, right: $numerator),
                right: $calculator->multiply(
                    left: $calculator->multiply(left: $halfway, right: $halfway),
                    right: $denominator
                )
            ),
            roundingMode: $roundingMode
        );
    }
}
