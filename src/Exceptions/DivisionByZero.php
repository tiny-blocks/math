<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Exceptions;

use DivisionByZeroError;

/**
 * Raised when an operation would divide by zero.
 */
final class DivisionByZero extends DivisionByZeroError implements MathFailure
{
    /**
     * Creates the failure for a zero divisor.
     *
     * @param string $dividend The value being divided.
     * @return DivisionByZero The created failure.
     */
    public static function becauseDivisorIsZero(string $dividend): DivisionByZero
    {
        $template = 'Cannot divide <%s> by zero.';

        return new DivisionByZero(message: sprintf($template, $dividend));
    }

    /**
     * Creates the failure for an allocation whose weights sum to zero.
     *
     * @return DivisionByZero The created failure.
     */
    public static function becauseWeightsSumToZero(): DivisionByZero
    {
        return new DivisionByZero(message: 'Allocation weights must not sum to zero.');
    }

    /**
     * Creates the failure for a fraction whose denominator is zero.
     *
     * @param string $numerator The numerator of the rejected fraction.
     * @return DivisionByZero The created failure.
     */
    public static function becauseDenominatorIsZero(string $numerator): DivisionByZero
    {
        $template = 'Fraction <%s> cannot have a denominator of zero.';

        return new DivisionByZero(message: sprintf($template, $numerator));
    }

    /**
     * Creates the failure for the reciprocal of zero.
     *
     * @return DivisionByZero The created failure.
     */
    public static function becauseReciprocalOfZeroIsUndefined(): DivisionByZero
    {
        return new DivisionByZero(message: 'The reciprocal of zero is undefined.');
    }
}
