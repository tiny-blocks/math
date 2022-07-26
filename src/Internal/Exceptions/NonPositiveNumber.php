<?php

namespace TinyBlocks\Math\Internal\Exceptions;

use TinyBlocks\Math\Internal\Number;
use RuntimeException;

final class NonPositiveNumber extends RuntimeException
{
    public function __construct(private readonly Number $number)
    {
        $template = 'The <%.2f> value must be positive.';
        parent::__construct(sprintf($template, $this->number->value));
    }
}
