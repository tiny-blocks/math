<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Operations\Adapters\Scales;

use TinyBlocks\Math\Internal\Scale;

/**
 * Defines a contract for applying a scale in mathematical operations.
 * Implementations of this interface should return an instance of {@see Scale}, which
 * encapsulates the precision level or decimal places for a given operation.
 */
interface Scales
{
    /**
     * Applies the appropriate scale for the current mathematical operation.
     *
     * @return Scale The scale object that defines the precision for the operation.
     */
    public function applyScale(): Scale;
}
