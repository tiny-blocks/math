<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal;

final readonly class StructuralHash
{
    private const string ALGORITHM = 'xxh128';

    public function of(string $type, string $representation): string
    {
        $template = '%s(%s)';

        return hash(self::ALGORITHM, sprintf($template, $type, $representation));
    }
}
