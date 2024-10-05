<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Mocks;

use TinyBlocks\Math\Internal\Operations\Extension\Extension;

final class ExtensionAdapterMock implements Extension
{
    public function isAvailable(string $extension): bool
    {
        return false;
    }
}
