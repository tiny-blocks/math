<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal;

use TinyBlocks\Math\Exceptions\ExponentOutOfRange;

final readonly class Exponent
{
    private const int MAXIMUM = 2147483647;

    private function __construct(public int $magnitude)
    {
    }

    public static function of(int $value): Exponent
    {
        if ($value > self::MAXIMUM || $value < (0 - self::MAXIMUM)) {
            throw ExponentOutOfRange::becauseExponentIsTooLarge(maximum: self::MAXIMUM, exponent: $value);
        }

        return new Exponent(magnitude: max($value, -$value));
    }
}
