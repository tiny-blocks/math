<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Operations\Adapters\Scales;

use TinyBlocks\Math\BigNumber;
use TinyBlocks\Math\Internal\Scale;

final readonly class Addition implements Scales
{
    private Scale $augendScale;
    private Scale $addendScale;

    public function __construct(private BigNumber $augend, private BigNumber $addend)
    {
        $this->augendScale = Scale::from(value: $this->augend->getScale());
        $this->addendScale = Scale::from(value: $this->addend->getScale());
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
