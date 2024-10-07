<?php

declare(strict_types=1);

namespace TinyBlocks\Math;

use TinyBlocks\Math\Internal\BigNumberBehavior;
use TinyBlocks\Math\Internal\Number;
use TinyBlocks\Math\Internal\Scale;

class BigDecimal extends BigNumberBehavior implements BigNumber
{
    protected function __construct(string|float $value, ?int $scale = null)
    {
        $scale = Scale::from(value: $scale);
        $number = Number::from(value: $value);

        parent::__construct(number: $number, scale: $scale);
    }

    public static function fromFloat(float $value, ?int $scale = BigNumber::AUTOMATIC_SCALE): BigDecimal
    {
        return new BigDecimal(value: $value, scale: $scale);
    }

    public static function fromString(string $value, ?int $scale = BigNumber::AUTOMATIC_SCALE): BigDecimal
    {
        return new BigDecimal(value: $value, scale: $scale);
    }
}
