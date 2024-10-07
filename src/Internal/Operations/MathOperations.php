<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Operations;

use TinyBlocks\Math\BigNumber;
use TinyBlocks\Math\Internal\Operations\Adapters\Result;

interface MathOperations
{
    /**
     * Performs the addition of two BigNumber instances.
     *
     * @param BigNumber $augend The number to which another number (addend) will be added.
     * @param BigNumber $addend The number to be added to the augend.
     * @return Result The result of adding augend and addend, encapsulated in a Result object.
     */
    public function add(BigNumber $augend, BigNumber $addend): Result;

    /**
     * Performs the subtraction of one BigNumber instance from another.
     *
     * @param BigNumber $minuend The number from which another number (subtrahend) will be subtracted.
     * @param BigNumber $subtrahend The number to be subtracted from the minuend.
     * @return Result The result of subtracting subtrahend from minuend, encapsulated in a Result object.
     */
    public function subtract(BigNumber $minuend, BigNumber $subtrahend): Result;

    /**
     * Performs the multiplication of two BigNumber instances.
     *
     * @param BigNumber $multiplicand The number to be multiplied.
     * @param BigNumber $multiplier The number by which the multiplicand will be multiplied.
     * @return Result The result of multiplying multiplicand and multiplier, encapsulated in a Result object.
     */
    public function multiply(BigNumber $multiplicand, BigNumber $multiplier): Result;

    /**
     * Performs the division of one BigNumber instance by another.
     *
     * @param BigNumber $dividend The number to be divided.
     * @param BigNumber $divisor The number by which the dividend will be divided.
     * @return Result The result of dividing dividend by divisor, encapsulated in a Result object.
     */
    public function divide(BigNumber $dividend, BigNumber $divisor): Result;
}
