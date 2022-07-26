<?php

namespace TinyBlocks\Math\Internal\Operations\Adapters\Scales;

use TinyBlocks\Math\BigNumber;
use TinyBlocks\Math\Internal\Scale;

final class Addition implements Scales
{
    private readonly Scale $augendScale;
    private readonly Scale $addendScale;

    public function __construct(private readonly BigNumber $augend, private readonly BigNumber $addend)
    {
        $this->augendScale = new Scale(value: $this->augend->getScale());
        $this->addendScale = new Scale(value: $this->addend->getScale());
    }

    public function applyScale(): Scale
    {
        if ($this->augendScale->hasAutomaticScale() && $this->addendScale->hasAutomaticScale()) {
            $augendScale = $this->augendScale->scaleOf(value: $this->augend->toString());
            $addendScale = $this->addendScale->scaleOf(value: $this->addend->toString());

            if ($augendScale->equals(other: $addendScale)) {
                return $augendScale;
            }

            return $augendScale->greaterScale(other: $addendScale);
        }

        return $this->augendScale->greaterScale(other: $this->addendScale);
    }
}
