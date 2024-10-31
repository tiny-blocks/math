<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Models;

use TinyBlocks\Math\BigNumber;
use TinyBlocks\Math\Internal\BigNumberBehavior;
use TinyBlocks\Math\Internal\Number;
use TinyBlocks\Math\Internal\Scale;

final class LargeNumber extends BigNumberBehavior implements BigNumber
{
    public static function fromFloat(float $value, ?int $scale = BigNumber::AUTOMATIC_SCALE): LargeNumber
    {
        $scale = Scale::from(value: $scale);
        $number = Number::from(value: $value);

        return new LargeNumber(number: $number, scale: $scale);
    }

    public static function fromString(string $value, ?int $scale = BigNumber::AUTOMATIC_SCALE): LargeNumber
    {
        $scale = Scale::from(value: $scale);
        $number = Number::from(value: $value);

        return new LargeNumber(number: $number, scale: $scale);
    }
}
