<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Operations\Adapters\Scales;

use TinyBlocks\Math\BigNumber;
use TinyBlocks\Math\Internal\Scale;

final readonly class Division implements Scales
{
    private Scale $divisorScale;

    private Scale $dividendScale;

    public function __construct(private BigNumber $dividend, private BigNumber $divisor)
    {
        $this->dividendScale = Scale::from(value: $this->dividend->getScale());
        $this->divisorScale = Scale::from(value: $this->divisor->getScale());
    }

    public function applyScale(): Scale
    {
        if ($this->dividendScale->hasAutomaticScale() && $this->divisorScale->hasAutomaticScale()) {
            $quotient = $this->dividend->toString() / $this->divisor->toString();

            return $this->dividendScale->scaleOf(value: (string)$quotient);
        }

        return $this->dividendScale->greaterScale(other: $this->divisorScale);
    }
}
