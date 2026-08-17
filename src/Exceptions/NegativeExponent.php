<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Exceptions;

use InvalidArgumentException;

/**
 * Raised when an integer or decimal is raised to a negative power.
 *
 * <p>A negative exponent leaves the integers and the decimals, so the operation has no result in
 * either type. <code>{@see \TinyBlocks\Math\BigRational::power()}</code> accepts one, because a
 * rational is closed under it.</p>
 */
final class NegativeExponent extends InvalidArgumentException implements MathFailure
{
    /**
     * Creates the failure for a negative exponent.
     *
     * @param int $exponent The rejected exponent.
     * @return NegativeExponent The created failure.
     */
    public static function becauseExponentIsNegative(int $exponent): NegativeExponent
    {
        $template = 'Exponent must not be negative, got <%d>. Convert to a BigRational first.';

        return new NegativeExponent(message: sprintf($template, $exponent));
    }
}
