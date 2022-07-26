<?php

namespace TinyBlocks\Math\Internal\Operations\Adapters\Scales;

use TinyBlocks\Math\BigNumber;
use TinyBlocks\Math\Internal\Scale;

final class Division implements Scales
{
    private readonly Scale $dividendScale;
    private readonly Scale $divisorScale;

    public function __construct(private readonly BigNumber $dividend, private readonly BigNumber $divisor)
    {
        $this->dividendScale = new Scale(value: $this->dividend->getScale());
        $this->divisorScale = new Scale(value: $this->divisor->getScale());
    }

    public function applyScale(): Scale
    {
        if ($this->dividendScale->hasAutomaticScale() && $this->divisorScale->hasAutomaticScale()) {
            $quotient = $this->dividend->toString() / $this->divisor->toString();

            return $this->dividendScale->scaleOf(value: $quotient);
        }

        return $this->dividendScale->greaterScale(other: $this->divisorScale);
    }
}
