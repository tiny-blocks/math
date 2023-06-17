<?php

namespace TinyBlocks\Math\Internal\Exceptions;

use RuntimeException;

final class InvalidScale extends RuntimeException
{
    public function __construct(int $value, int $minimum, int $maximum)
    {
        $template = 'Scale value <%s> is invalid. The value must be between <%s> and <%s>.';
        parent::__construct(message: sprintf($template, $value, $minimum, $maximum));
    }
}
