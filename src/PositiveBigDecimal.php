<?php

declare(strict_types=1);

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

    private function __construct(Number $number, Scale $scale)
    {
        if ($number->isNegativeOrZero()) {
            throw new NonPositiveNumber(number: $number);
        }

        parent::__construct(number: $number, scale: $scale);
    }

    public static function from(float|string $value, ?int $scale = BigNumber::AUTOMATIC_SCALE): PositiveBigDecimal
    {
        $scale = new Scale(value: $scale);
        $number = Number::from(value: $value);

        return new PositiveBigDecimal(number: $number, scale: $scale);
    }
}
