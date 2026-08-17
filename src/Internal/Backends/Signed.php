<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Backends;

final readonly class Signed
{
    private const string MINUS = '-';

    private function __construct(public Magnitude $magnitude, public bool $isNegative)
    {
    }

    public static function of(string $value): Signed
    {
        $magnitude = Magnitude::of(magnitude: ltrim($value, self::MINUS));

        return new Signed(
            magnitude: $magnitude,
            isNegative: str_starts_with($value, self::MINUS) && !$magnitude->isZero()
        );
    }
}
