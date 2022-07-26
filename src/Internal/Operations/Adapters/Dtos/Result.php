<?php

namespace TinyBlocks\Math\Internal\Operations\Adapters\Dtos;

use TinyBlocks\Math\Internal\Number;
use TinyBlocks\Math\Internal\Scale;

final class Result
{
    public function __construct(public readonly Number $number, public readonly Scale $scale)
    {
    }
}
