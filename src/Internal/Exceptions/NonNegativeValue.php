<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Exceptions;

use RuntimeException;
use TinyBlocks\Math\Internal\Number;

final class NonNegativeValue extends RuntimeException
{
    public function __construct(Number $number)
    {
        $template = 'Value <%s> is not valid. Must be a negative number less than zero.';
        parent::__construct(message: sprintf($template, $number->value));
    }
}
