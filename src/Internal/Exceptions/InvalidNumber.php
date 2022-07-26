<?php

namespace TinyBlocks\Math\Internal\Exceptions;

use RuntimeException;

final class InvalidNumber extends RuntimeException
{
    public function __construct(mixed $value)
    {
        $template = 'The value <%s> is not a valid number.';
        parent::__construct(sprintf($template, $value));
    }
}
