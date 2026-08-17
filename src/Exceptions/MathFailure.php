<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Exceptions;

use Throwable;

/**
 * Contract implemented by every failure this library raises.
 *
 * <p>PHP splits unrecoverable and recoverable faults into <code>Error</code> and
 * <code>Exception</code>, and this library raises both: a zero divisor belongs under
 * <code>DivisionByZeroError</code> while a malformed literal belongs under
 * <code>InvalidArgumentException</code>. Catching this interface catches every one of them in a
 * single clause.</p>
 */
interface MathFailure extends Throwable
{
}
