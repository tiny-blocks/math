<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Operations\Adapters;

use TinyBlocks\Math\Internal\Number;
use TinyBlocks\Math\Internal\Scale;

final readonly class Result
{
    public function __construct(public Number $number, public Scale $scale)
    {
    }
}
