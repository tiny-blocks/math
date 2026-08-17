<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Allocations;

use TinyBlocks\Math\Calculators;

final readonly class Share
{
    private const string ONE = '1';

    private function __construct(public string $floor, public string $remainder)
    {
    }

    public static function of(string $total, string $numerator): Share
    {
        $calculator = Calculators::active();
        $modulo = $calculator->remainder(
            numerator: $calculator->add(
                left: $calculator->remainder(numerator: $numerator, denominator: $total),
                right: $total
            ),
            denominator: $total
        );

        return new Share(
            floor: $calculator->quotient(
                numerator: $calculator->subtract(minuend: $numerator, subtrahend: $modulo),
                denominator: $total
            ),
            remainder: $modulo
        );
    }

    public function incremented(): Share
    {
        return new Share(
            floor: Calculators::active()->add(left: $this->floor, right: self::ONE),
            remainder: $this->remainder
        );
    }

    public function byLargestRemainder(Share $other): int
    {
        return Calculators::active()->compare(left: $other->remainder, right: $this->remainder);
    }
}
