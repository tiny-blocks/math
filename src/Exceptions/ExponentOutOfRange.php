<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Exceptions;

use InvalidArgumentException;

/**
 * Raised when an exponent is too large for the library to expand.
 *
 * <p>The bound applies to the magnitude, so it is the same for the negative exponents
 * <code>{@see \TinyBlocks\Math\BigRational::power()}</code> accepts. A negative exponent on an
 * integer or a decimal is a different invariant and raises {@see NegativeExponent}.</p>
 */
final class ExponentOutOfRange extends InvalidArgumentException implements MathFailure
{
    /**
     * Creates the failure for an exponent beyond the supported magnitude.
     *
     * @param int $maximum The largest supported magnitude.
     * @param int $exponent The rejected exponent.
     * @return ExponentOutOfRange The created failure.
     */
    public static function becauseExponentIsTooLarge(int $maximum, int $exponent): ExponentOutOfRange
    {
        $template = 'Exponent magnitude must not exceed %d, got <%d>.';

        return new ExponentOutOfRange(message: sprintf($template, $maximum, $exponent));
    }
}
