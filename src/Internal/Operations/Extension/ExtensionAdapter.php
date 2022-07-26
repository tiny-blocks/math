<?php

namespace TinyBlocks\Math\Internal\Operations\Extension;

final class ExtensionAdapter implements Extension
{
    public function isAvailable(string $extension): bool
    {
        return extension_loaded($extension);
    }
}
