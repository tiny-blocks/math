<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal;

use TinyBlocks\Math\Calculators;

final readonly class GreatestCommonDivisor
{
    private const string ZERO = '0';

    public function of(string $left, string $right): string
    {
        $calculator = Calculators::active();

        while ($right !== self::ZERO) {
            $remainder = $calculator->remainder(numerator: $left, denominator: $right);
            $left = $right;
            $right = $remainder;
        }

        return $left;
    }
}
