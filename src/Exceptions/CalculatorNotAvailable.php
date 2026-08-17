<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Exceptions;

use RuntimeException;

/**
 * Raised when the calculation backend cannot run in the current process.
 */
final class CalculatorNotAvailable extends RuntimeException implements MathFailure
{
    /**
     * Creates the failure for a process where no shipped backend can run.
     *
     * @return CalculatorNotAvailable The created failure.
     */
    public static function becauseNoBackendCanRun(): CalculatorNotAvailable
    {
        $message = 'No calculation backend can run in this process. The native backend needs 64-bit integers.';

        return new CalculatorNotAvailable(message: $message);
    }

    /**
     * Creates the failure for a backend whose extension is not loaded.
     *
     * @param string $calculator The class name of the backend that reported itself unavailable.
     * @return CalculatorNotAvailable The created failure.
     */
    public static function becauseExtensionIsMissing(string $calculator): CalculatorNotAvailable
    {
        $template = 'Calculator <%s> is not available. Install the extension it requires.';

        return new CalculatorNotAvailable(message: sprintf($template, $calculator));
    }
}
