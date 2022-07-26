<?php

namespace TinyBlocks\Math;

use TinyBlocks\Math\Internal\BigNumberAdapter;
use TinyBlocks\Math\Internal\Exceptions\NonPositiveNumber;
use TinyBlocks\Math\Internal\Number;
use TinyBlocks\Math\Internal\Scale;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectAdapter;

final class PositiveBigDecimal extends BigNumberAdapter implements BigNumber, ValueObject
{
    use ValueObjectAdapter;

    private function __construct(float|string $value, ?int $scale)
    {
        $number = new Number(value: $value);

        if ($number->isNegativeOrZero()) {
            throw new NonPositiveNumber(number: $number);
        }

        parent::__construct(number: $number, scale: new Scale(value: $scale));
    }

    public static function from(float|string $value, ?int $scale = BigNumber::AUTOMATIC_SCALE): PositiveBigDecimal
    {
        return new PositiveBigDecimal(value: $value, scale: $scale);
    }
}
