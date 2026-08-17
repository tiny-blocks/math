<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Exceptions;

use DomainException;

/**
 * Raised when an exact decimal was demanded of a quotient whose expansion repeats forever.
 *
 * <p>No scale resolves this, which is what separates it from {@see InexactConversion}.</p>
 */
final class NonTerminatingDecimal extends DomainException implements MathFailure
{
    /**
     * Creates the failure for a fraction with a repeating decimal expansion.
     *
     * @param string $fraction The fraction in numerator/denominator form.
     * @return NonTerminatingDecimal The created failure.
     */
    public static function becauseExpansionRepeats(string $fraction): NonTerminatingDecimal
    {
        $template = 'Fraction <%s> has a non-terminating decimal expansion.';

        return new NonTerminatingDecimal(message: sprintf($template, $fraction));
    }
}
