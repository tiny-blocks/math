<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Backends;

final readonly class Division
{
    private function __construct(public Magnitude $quotient, public Magnitude $remainder)
    {
    }

    public static function of(Magnitude $quotient, Magnitude $remainder): Division
    {
        return new Division(quotient: $quotient, remainder: $remainder);
    }
}
