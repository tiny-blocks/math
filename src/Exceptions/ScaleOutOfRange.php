<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Exceptions;

use InvalidArgumentException;

/**
 * Raised when a scale falls outside the supported range.
 */
final class ScaleOutOfRange extends InvalidArgumentException implements MathFailure
{
    /**
     * Creates the failure for a scale outside the supported bounds.
     *
     * @param int $value The rejected scale.
     * @param int $maximum The largest supported scale.
     * @return ScaleOutOfRange The created failure.
     */
    public static function becauseValueIsOutsideBounds(int $value, int $maximum): ScaleOutOfRange
    {
        $template = 'Scale must be between 0 and %d, got <%d>.';

        return new ScaleOutOfRange(message: sprintf($template, $maximum, $value));
    }
}
