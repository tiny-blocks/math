<?php

namespace TinyBlocks\Math\Internal\Operations\Adapters\Scales;

use TinyBlocks\Math\BigNumber;
use TinyBlocks\Math\Internal\Scale;

final class Subtraction
{
    private readonly Scale $minuendScale;
    private readonly Scale $subtrahendScale;

    public function __construct(private readonly BigNumber $minuend, private readonly BigNumber $subtrahend)
    {
        $this->minuendScale = new Scale(value: $this->minuend->getScale());
        $this->subtrahendScale = new Scale(value: $this->subtrahend->getScale());
    }

    public function applyScale(): Scale
    {
        if ($this->minuendScale->hasAutomaticScale() && $this->subtrahendScale->hasAutomaticScale()) {
            $minuendScale = $this->minuendScale->scaleOf(value: $this->minuend->toString());
            $subtrahendScale = $this->minuendScale->scaleOf(value: $this->subtrahend->toString());

            if ($minuendScale->equals(other: $subtrahendScale)) {
                return $minuendScale;
            }

            return $minuendScale->greaterScale(other: $subtrahendScale);
        }

        return $this->minuendScale->greaterScale(other: $this->subtrahendScale);
    }
}
