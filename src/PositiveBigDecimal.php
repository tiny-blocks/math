<?php

declare(strict_types=1);

namespace TinyBlocks\Math;

use TinyBlocks\Math\Internal\BigNumberBehavior;
use TinyBlocks\Math\Internal\Exceptions\NonPositiveNumber;
use TinyBlocks\Math\Internal\Number;
use TinyBlocks\Math\Internal\Scale;

class PositiveBigDecimal extends BigNumberBehavior implements BigNumber
{
    protected function __construct(string|float $value, ?int $scale = null)
    {
        $scale = Scale::from(value: $scale);
        $number = Number::from(value: $value);

        if ($number->isNegativeOrZero()) {
            throw new NonPositiveNumber(number: $number);
        }

        parent::__construct(number: $number, scale: $scale);
    }

    public static function fromFloat(float $value, ?int $scale = BigNumber::AUTOMATIC_SCALE): PositiveBigDecimal
    {
        return new PositiveBigDecimal(value: $value, scale: $scale);
    }

    public static function fromString(string $value, ?int $scale = BigNumber::AUTOMATIC_SCALE): PositiveBigDecimal
    {
        return new PositiveBigDecimal(value: $value, scale: $scale);
    }
}
