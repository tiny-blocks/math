<?php

declare(strict_types=1);

namespace TinyBlocks\Math;

/**
 * Use one of the following constants to specify the mode in which rounding occurs.
 *
 * @see https://www.php.net/manual/en/function.round.php
 */
enum RoundingMode: int
{
    case HALF_UP = 1;
    case HALF_DOWN = 2;
    case HALF_EVEN = 3;
    case HALF_ODD = 4;
}
