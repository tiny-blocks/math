<?php

namespace TinyBlocks\Math\Internal\Operations\Adapters;

use TinyBlocks\Math\BigNumber;
use TinyBlocks\Math\Internal\Number;
use TinyBlocks\Math\Internal\Operations\Adapters\Dtos\Result;
use TinyBlocks\Math\Internal\Operations\Adapters\Scales\Addition;
use TinyBlocks\Math\Internal\Operations\Adapters\Scales\Division;
use TinyBlocks\Math\Internal\Operations\Adapters\Scales\Multiplication;
use TinyBlocks\Math\Internal\Operations\Adapters\Scales\Subtraction;
use TinyBlocks\Math\Internal\Operations\MathOperations;

final class BcMathAdapter implements MathOperations
{
    public function add(BigNumber $augend, BigNumber $addend): Result
    {
        $scale = (new Addition(augend: $augend, addend: $addend))->applyScale();
        $number = new Number(
            value: bcadd(
                $augend->toString(),
                $addend->toString(),
                $scale->value
            )
        );

        return new Result(number: $number, scale: $scale);
    }

    public function subtract(BigNumber $minuend, BigNumber $subtrahend): Result
    {
        $scale = (new Subtraction(minuend: $minuend, subtrahend: $subtrahend))->applyScale();
        $number = new Number(
            value: bcsub(
                $minuend->toString(),
                $subtrahend->toString(),
                $scale->value
            )
        );

        return new Result(number: $number, scale: $scale);
    }

    public function multiply(BigNumber $multiplicand, BigNumber $multiplier): Result
    {
        $scale = (new Multiplication(multiplicand: $multiplicand, multiplier: $multiplier))->applyScale();
        $number = new Number(
            value: bcmul(
                $multiplicand->toString(),
                $multiplier->toString(),
                $scale->value
            )
        );

        return new Result(number: $number, scale: $scale);
    }

    public function divide(BigNumber $dividend, BigNumber $divisor): Result
    {
        $scale = (new Division(dividend: $dividend, divisor: $divisor))->applyScale();
        $number = new Number(
            value: bcdiv(
                $dividend->toString(),
                $divisor->toString(),
                $scale->value
            )
        );

        return new Result(number: $number, scale: $scale);
    }
}
