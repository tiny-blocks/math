<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Exceptions;

use RuntimeException;
use TinyBlocks\Math\Internal\Number;

final class NonPositiveValue extends RuntimeException
{
    public function __construct(Number $number)
    {
        $template = 'Value <%s> is not valid. Must be a positive number greater than zero.';

        parent::__construct(message: sprintf($template, $number->value));
    }
}
