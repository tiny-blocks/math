<?php

namespace TinyBlocks\Math\Internal\Operations;

use TinyBlocks\Math\BigNumber;
use TinyBlocks\Math\Internal\Operations\Adapters\Dtos\Result;

interface MathOperations
{
    /**
     * @param BigNumber $augend
     * @param BigNumber $addend
     * @return Result
     */
    public function add(BigNumber $augend, BigNumber $addend): Result;

    /**
     * @param BigNumber $minuend
     * @param BigNumber $subtrahend
     * @return Result
     */
    public function subtract(BigNumber $minuend, BigNumber $subtrahend): Result;

    /**
     * @param BigNumber $multiplicand
     * @param BigNumber $multiplier
     * @return Result
     */
    public function multiply(BigNumber $multiplicand, BigNumber $multiplier): Result;

    /**
     * @param BigNumber $dividend
     * @param BigNumber $divisor
     * @return Result
     */
    public function divide(BigNumber $dividend, BigNumber $divisor): Result;
}
