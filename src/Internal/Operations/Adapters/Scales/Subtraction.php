<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Operations\Adapters\Scales;

use TinyBlocks\Math\BigNumber;
use TinyBlocks\Math\Internal\Scale;

final readonly class Subtraction
{
    private Scale $minuendScale;
    private Scale $subtrahendScale;

    public function __construct(private BigNumber $minuend, private BigNumber $subtrahend)
    {
        $this->minuendScale = Scale::from(value: $this->minuend->getScale());
        $this->subtrahendScale = Scale::from(value: $this->subtrahend->getScale());
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
