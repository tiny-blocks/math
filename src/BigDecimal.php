<?php

namespace TinyBlocks\Math;

use TinyBlocks\Math\Internal\BigNumberAdapter;
use TinyBlocks\Math\Internal\Number;
use TinyBlocks\Math\Internal\Scale;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectAdapter;

final class BigDecimal extends BigNumberAdapter implements BigNumber, ValueObject
{
    use ValueObjectAdapter;

    private function __construct(float|string $value, ?int $scale)
    {
        parent::__construct(number: new Number(value: $value), scale: new Scale(value: $scale));
    }

    public static function from(float|string $value, ?int $scale = BigNumber::AUTOMATIC_SCALE): BigDecimal
    {
        return new BigDecimal(value: $value, scale: $scale);
    }
}
