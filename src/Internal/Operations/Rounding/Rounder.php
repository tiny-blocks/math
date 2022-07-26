<?php

namespace TinyBlocks\Math\Internal\Operations\Rounding;

use TinyBlocks\Math\BigNumber;
use TinyBlocks\Math\Internal\Number;
use TinyBlocks\Math\RoundingMode;

final class Rounder
{
    public function __construct(private readonly RoundingMode $mode, private readonly BigNumber $bigNumber)
    {
    }

    public function round(): Number
    {
        $rounded = round(
            $this->bigNumber->toString(),
            $this->bigNumber->getScale(),
            $this->mode->value
        );

        return new Number(value: $rounded);
    }
}
