<?php

declare(strict_types=1);

namespace TinyBlocks\Math;

use TinyBlocks\Math\Internal\BigNumberBehavior;
use TinyBlocks\Math\Internal\Number;
use TinyBlocks\Math\Internal\Scale;

class BigDecimal extends BigNumberBehavior implements BigNumber
{
    public static function fromFloat(float $value, ?int $scale = BigNumber::AUTOMATIC_SCALE): BigNumber
    {
        $scale = Scale::from(value: $scale);
        $number = Number::from(value: $value);

        return new BigDecimal(number: $number, scale: $scale);
    }

    public static function fromString(string $value, ?int $scale = BigNumber::AUTOMATIC_SCALE): BigNumber
    {
        $scale = Scale::from(value: $scale);
        $number = Number::from(value: $value);

        return new BigDecimal(number: $number, scale: $scale);
    }
}
