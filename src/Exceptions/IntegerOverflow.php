<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Exceptions;

use OverflowException;

/**
 * Raised when a value cannot be represented by a native PHP type.
 */
final class IntegerOverflow extends OverflowException implements MathFailure
{
    /**
     * Creates the failure for a value outside the native float range.
     *
     * @param string $value The value being converted.
     * @return IntegerOverflow The created failure.
     */
    public static function becauseValueExceedsFloatRange(string $value): IntegerOverflow
    {
        $template = 'Value <%s> is outside the range of a native float.';

        return new IntegerOverflow(message: sprintf($template, $value));
    }

    /**
     * Creates the failure for a value outside the native integer range.
     *
     * @param string $value The value being converted.
     * @return IntegerOverflow The created failure.
     */
    public static function becauseValueExceedsIntegerRange(string $value): IntegerOverflow
    {
        $template = 'Value <%s> is outside the range of a native integer.';

        return new IntegerOverflow(message: sprintf($template, $value));
    }
}
