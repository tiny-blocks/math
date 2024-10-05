<?php

declare(strict_types=1);

namespace TinyBlocks\Math;

use TinyBlocks\Math\Internal\BigNumberAdapter;
use TinyBlocks\Math\Internal\Number;
use TinyBlocks\Math\Internal\Scale;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectAdapter;

final class BigDecimal extends BigNumberAdapter implements BigNumber, ValueObject
{
    use ValueObjectAdapter;

    public static function from(float|string $value, ?int $scale = BigNumber::AUTOMATIC_SCALE): BigDecimal
    {
        $scale = new Scale(value: $scale);
        $number = Number::from(value: $value);

        return new BigDecimal(number: $number, scale: $scale);
    }
}
