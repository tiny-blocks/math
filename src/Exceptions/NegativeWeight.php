<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Exceptions;

use InvalidArgumentException;

/**
 * Raised when an allocation weight is negative.
 */
final class NegativeWeight extends InvalidArgumentException implements MathFailure
{
    /**
     * Creates the failure for a negative allocation weight.
     *
     * @param string $weight The rejected weight.
     * @return NegativeWeight The created failure.
     */
    public static function becauseWeightIsNegative(string $weight): NegativeWeight
    {
        $template = 'Allocation weights must not be negative, got <%s>.';

        return new NegativeWeight(message: sprintf($template, $weight));
    }
}
