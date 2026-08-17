<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Backends;

final readonly class Difference
{
    private function __construct(public Magnitude $magnitude, public bool $isNegative)
    {
    }

    public static function of(Magnitude $magnitude, bool $isNegative): Difference
    {
        return new Difference(magnitude: $magnitude, isNegative: $isNegative);
    }
}
