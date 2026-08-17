<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Exceptions;

use InvalidArgumentException;

/**
 * Raised when a literal cannot be read as a number.
 */
final class NumberNotWellFormed extends InvalidArgumentException implements MathFailure
{
    /**
     * Creates the failure for a literal that is not a number.
     *
     * @param string $value The literal that was rejected.
     * @return NumberNotWellFormed The created failure.
     */
    public static function becauseValueIsNotNumeric(string $value): NumberNotWellFormed
    {
        $template = 'Value <%s> is not a well-formed number.';

        return new NumberNotWellFormed(message: sprintf($template, $value));
    }

    /**
     * Creates the failure for an exponent too large to expand.
     *
     * @param string $value The literal carrying the exponent.
     * @param int $maximum The largest exponent magnitude the library expands.
     * @return NumberNotWellFormed The created failure.
     */
    public static function becauseExponentIsTooLarge(string $value, int $maximum): NumberNotWellFormed
    {
        $template = 'Value <%s> carries an exponent beyond the supported magnitude of <%d>.';

        return new NumberNotWellFormed(message: sprintf($template, $value, $maximum));
    }
}
