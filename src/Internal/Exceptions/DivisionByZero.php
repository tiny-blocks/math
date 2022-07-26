<?php

namespace TinyBlocks\Math\Internal\Exceptions;

use RuntimeException;

final class DivisionByZero extends RuntimeException
{
    public function __construct(string $dividend, string $divisor)
    {
        $template = 'Cannot divide <%.2f> by <%.2f>.';
        parent::__construct(sprintf($template, $dividend, $divisor));
    }
}
