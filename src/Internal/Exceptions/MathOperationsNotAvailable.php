<?php

namespace TinyBlocks\Math\Internal\Exceptions;

use RuntimeException;

final class MathOperationsNotAvailable extends RuntimeException
{
    public function __construct(array $extensions)
    {
        $template = 'There are no implementations available. Enable one of these <%s> extensions.';
        parent::__construct(sprintf($template, implode(',', $extensions)));
    }
}
