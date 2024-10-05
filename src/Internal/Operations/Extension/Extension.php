<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Operations\Extension;

/**
 * Defines a contract for checking the availability of a PHP extension.
 */
interface Extension
{
    public const BC_MATH = 'bcmath';

    /**
     * Checks if a given PHP extension is loaded and available for use.
     *
     * @param string $extension The name of the PHP extension to check.
     * @return bool True if the extension is loaded, false otherwise.
     */
    public function isAvailable(string $extension): bool;
}
