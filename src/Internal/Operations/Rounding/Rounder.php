<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Operations\Rounding;

use TinyBlocks\Math\BigNumber;
use TinyBlocks\Math\Internal\Number;
use TinyBlocks\Math\RoundingMode;

final readonly class Rounder
{
    public function __construct(private RoundingMode $mode, private BigNumber $bigNumber)
    {
    }

    public function round(): Number
    {
        $rounded = round(
            $this->bigNumber->toFloat(),
            (int)$this->bigNumber->getScale(),
            $this->mode->value
        );

        return Number::from(value: $rounded);
    }
}
