<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Exceptions;

use DomainException;

/**
 * Raised when a square root is taken of a negative value.
 */
final class NegativeRoot extends DomainException implements MathFailure
{
    /**
     * Creates the failure for the square root of a negative radicand.
     *
     * @param string $radicand The value whose root was requested.
     * @return NegativeRoot The created failure.
     */
    public static function becauseRadicandIsNegative(string $radicand): NegativeRoot
    {
        $template = 'Cannot take the square root of the negative value <%s>.';

        return new NegativeRoot(message: sprintf($template, $radicand));
    }
}
