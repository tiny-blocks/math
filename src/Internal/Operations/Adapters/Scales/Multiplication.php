<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Operations\Adapters\Scales;

use TinyBlocks\Math\BigNumber;
use TinyBlocks\Math\Internal\Scale;

final readonly class Multiplication implements Scales
{
    private Scale $multiplicandScale;
    private Scale $multiplierScale;

    public function __construct(private BigNumber $multiplicand, private BigNumber $multiplier)
    {
        $this->multiplicandScale = new Scale(value: $this->multiplicand->getScale());
        $this->multiplierScale = new Scale(value: $this->multiplier->getScale());
    }

    public function applyScale(): Scale
    {
        if ($this->multiplicandScale->hasAutomaticScale() && $this->multiplierScale->hasAutomaticScale()) {
            $multiplicandScale = $this->multiplicandScale->scaleOf(value: $this->multiplicand->toString());
            $multiplierScale = $this->multiplierScale->scaleOf(value: $this->multiplier->toString());

            if ($multiplicandScale->equals(other: $multiplierScale)) {
                return $multiplicandScale->add(other: $multiplierScale);
            }

            return $multiplicandScale->greaterScale(other: $multiplierScale);
        }

        return $this->multiplicandScale->greaterScale(other: $this->multiplierScale);
    }
}
