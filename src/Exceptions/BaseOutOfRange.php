<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Exceptions;

use InvalidArgumentException;

/**
 * Raised when a positional base falls outside the supported range.
 */
final class BaseOutOfRange extends InvalidArgumentException implements MathFailure
{
    /**
     * Creates the failure for a base outside the supported bounds.
     *
     * @param int $base The rejected base.
     * @param string $bounds The supported range, as a human-readable interval.
     * @return BaseOutOfRange The created failure.
     */
    public static function becauseBaseIsOutsideBounds(int $base, string $bounds): BaseOutOfRange
    {
        $template = 'Base must be within %s, got <%d>.';

        return new BaseOutOfRange(message: sprintf($template, $bounds, $base));
    }
}
