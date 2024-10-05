<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Models;

use TinyBlocks\Math\BigNumber;
use TinyBlocks\Math\Internal\BigNumberAdapter;
use TinyBlocks\Math\Internal\Number;
use TinyBlocks\Math\Internal\Scale;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectAdapter;

final class LargeNumber extends BigNumberAdapter implements BigNumber, ValueObject
{
    use ValueObjectAdapter;

    public static function from(float|string $value, ?int $scale = BigNumber::AUTOMATIC_SCALE): LargeNumber
    {
        $scale = new Scale(value: $scale);
        $number = Number::from(value: $value);

        return new LargeNumber(number: $number, scale: $scale);
    }
}
