<?php

namespace TinyBlocks\Math\Mock;

use TinyBlocks\Math\Internal\Operations\Extension\Extension;

final class ExtensionAdapterMock implements Extension
{
    public function isAvailable(string $extension): bool
    {
        return false;
    }
}
