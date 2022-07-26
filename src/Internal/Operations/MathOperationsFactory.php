<?php

namespace TinyBlocks\Math\Internal\Operations;

use TinyBlocks\Math\Internal\Exceptions\MathOperationsNotAvailable;
use TinyBlocks\Math\Internal\Operations\Adapters\BcMathAdapter;
use TinyBlocks\Math\Internal\Operations\Extension\Extension;

final class MathOperationsFactory
{
    public function __construct(private readonly Extension $extension)
    {
    }

    public function build(): MathOperations
    {
        if ($this->extension->isAvailable(extension: Extension::BC_MATH)) {
            return new BcMathAdapter();
        }

        throw new MathOperationsNotAvailable(extensions: [Extension::BC_MATH]);
    }
}
