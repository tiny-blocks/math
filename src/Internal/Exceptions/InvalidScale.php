<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Exceptions;

use RuntimeException;

final class InvalidScale extends RuntimeException
{
    public function __construct(
        private readonly int $value,
        private readonly int $minimum,
        private readonly int $maximum
    ) {
        $template = 'Scale value <%s> is invalid. The value must be between <%s> and <%s>.';

        parent::__construct(message: sprintf($template, $this->value, $this->minimum, $this->maximum));
    }
}
