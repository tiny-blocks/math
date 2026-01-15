<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Exceptions;

use RuntimeException;

final class DivisionByZero extends RuntimeException
{
    public function __construct(private readonly string $dividend, private readonly string $divisor)
    {
        $template = 'Cannot divide <%.2f> by <%.2f>.';

        parent::__construct(message: sprintf($template, $this->dividend, $this->divisor));
    }
}
