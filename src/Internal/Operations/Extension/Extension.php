<?php

namespace TinyBlocks\Math\Internal\Operations\Extension;

interface Extension
{
    public const BC_MATH = 'bcmath';

    /**
     * Find out whether an extension is loaded.
     * @param string $extension
     * @return bool
     */
    public function isAvailable(string $extension): bool;
}
