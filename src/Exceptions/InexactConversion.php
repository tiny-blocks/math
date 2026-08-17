<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Exceptions;

use DomainException;

/**
 * Raised when an exact conversion would discard digits.
 *
 * <p>The caller can act on this: asking for a larger scale, or rounding explicitly, resolves it.
 * Its sibling {@see NonTerminatingDecimal} cannot be resolved by asking for more digits.</p>
 */
final class InexactConversion extends DomainException implements MathFailure
{
    /**
     * Creates the failure for a value that does not fit the requested scale.
     *
     * @param int $scale The requested scale.
     * @param string $value The value being converted.
     * @return InexactConversion The created failure.
     */
    public static function becauseScaleIsTooSmall(int $scale, string $value): InexactConversion
    {
        $template = 'Value <%s> cannot be represented at scale <%d> without discarding digits.';

        return new InexactConversion(message: sprintf($template, $value, $scale));
    }

    /**
     * Creates the failure for a value carrying a fractional part where an integer was required.
     *
     * @param string $value The value being converted.
     * @return InexactConversion The created failure.
     */
    public static function becauseFractionalPartWouldBeLost(string $value): InexactConversion
    {
        $template = 'Value <%s> has a fractional part and is not an integer.';

        return new InexactConversion(message: sprintf($template, $value));
    }
}
