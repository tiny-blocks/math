<?php

namespace TinyBlocks\Math\Internal\Operations\Adapters\Scales;

use TinyBlocks\Math\Internal\Scale;

interface Scales
{
    /**
     * @return Scale
     */
    public function applyScale(): Scale;
}
