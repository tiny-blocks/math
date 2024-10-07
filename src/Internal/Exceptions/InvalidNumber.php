<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Exceptions;

use RuntimeException;

final class InvalidNumber extends RuntimeException
{
    public function __construct(string $value)
    {
        $template = 'The value <%s> is not a valid number.';
        parent::__construct(message: sprintf($template, $value));
    }
}
