<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Decimals;

use TinyBlocks\Math\Calculators;
use TinyBlocks\Math\RoundingMode;

final readonly class Rounding
{
    private const string ONE = '1';
    private const string TWO = '2';
    private const string FIVE = '5';
    private const string ZERO = '0';
    private const string MINUS = '-';
    private const string MINUS_ONE = '-1';

    public function nearest(string $whole, int $comparison, RoundingMode $roundingMode): string
    {
        $calculator = Calculators::active();
        $isNegative = str_starts_with($whole, self::MINUS);
        $roundsAwayFromZero = $roundingMode->roundsAwayFromZero(
            isEven: $calculator->remainder(numerator: $whole, denominator: self::TWO) === self::ZERO,
            comparison: $comparison,
            isNegative: $isNegative
        );

        $unit = $isNegative ? self::MINUS_ONE : self::ONE;

        return Digits::normalized(
            text: $roundsAwayFromZero ? $calculator->add(left: $whole, right: $unit) : $whole
        );
    }

    public function trimmed(string $value, Scale $places, RoundingMode $roundingMode): string
    {
        $magnitude = ltrim($value, self::MINUS);
        $length = max(0, (strlen($magnitude) - $places->value));
        $discarded = str_pad(substr($magnitude, $length), $places->value, self::ZERO, STR_PAD_LEFT);
        $template = '%s%s%s';
        $signed = sprintf(
            $template,
            str_starts_with($value, self::MINUS) ? self::MINUS : '',
            self::ZERO,
            substr($magnitude, 0, $length)
        );

        if (ltrim($discarded, self::ZERO) === '') {
            return Digits::normalized(text: $signed);
        }

        return $this->nearest(
            whole: $signed,
            comparison: (strcmp($discarded, str_pad(self::FIVE, $places->value, self::ZERO)) <=> 0),
            roundingMode: $roundingMode
        );
    }

    public function quotient(string $numerator, string $denominator, RoundingMode $roundingMode): string
    {
        $calculator = Calculators::active();
        $whole = $calculator->quotient(numerator: $numerator, denominator: $denominator);
        $remainder = $calculator->subtract(
            minuend: $numerator,
            subtrahend: $calculator->multiply(left: $whole, right: $denominator)
        );

        if ($remainder === self::ZERO) {
            return $whole;
        }

        $template = '%s%s';

        return $this->nearest(
            whole: sprintf(
                $template,
                str_starts_with($numerator, self::MINUS) ? self::MINUS : '',
                ltrim($whole, self::MINUS)
            ),
            comparison: $calculator->compare(
                left: $calculator->multiply(left: ltrim($remainder, self::MINUS), right: self::TWO),
                right: $denominator
            ),
            roundingMode: $roundingMode
        );
    }
}
