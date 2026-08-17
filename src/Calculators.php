<?php

declare(strict_types=1);

namespace TinyBlocks\Math;

use TinyBlocks\Math\Exceptions\CalculatorNotAvailable;
use TinyBlocks\Math\Internal\Backends\BcMathCalculator;
use TinyBlocks\Math\Internal\Backends\CalculatorSelection;
use TinyBlocks\Math\Internal\Backends\NativeCalculator;

/**
 * Resolves the calculation backend for the process.
 *
 * <p>Resolution takes the first shipped backend that can run: BCMath when the extension is loaded,
 * otherwise a pure PHP backend that needs nothing beyond 64-bit integers. Both produce identical
 * results, so the extension buys speed rather than correctness. A consumer that ships its own
 * engine registers it, and a registered backend always wins over the resolved one.</p>
 *
 * <p>Resolution happens once and is then cached, so the arithmetic path never pays for it again.
 * A backend is checked when it is registered rather than when it is used, so a bootstrap mistake
 * surfaces at bootstrap.</p>
 *
 * <p>What is swapped here is a stateless, pure engine, so a replacement changes how fast a result
 * is produced and never what the result is.</p>
 */
final class Calculators
{
    private static ?Calculator $current = null;

    private function __construct()
    {
    }

    /**
     * Returns to automatic resolution, discarding any registered backend.
     */
    public static function reset(): void
    {
        Calculators::$current = null;
    }

    /**
     * Returns the backend in use, resolving it on first call.
     *
     * @return Calculator The active backend.
     * @throws CalculatorNotAvailable If no candidate can run in this process.
     */
    public static function active(): Calculator
    {
        return Calculators::$current ??= new CalculatorSelection()->resolved(
            new BcMathCalculator(),
            new NativeCalculator()
        );
    }

    /**
     * Registers a backend for the rest of the process.
     *
     * <p>Intended for bootstrap code that ships its own engine, and for tests. Call
     * <code>{@see Calculators::reset()}</code> to return to automatic resolution.</p>
     *
     * @param Calculator $calculator The backend to use.
     * @throws CalculatorNotAvailable If the backend cannot run in this process.
     */
    public static function register(Calculator $calculator): void
    {
        Calculators::$current = new CalculatorSelection()->verified(calculator: $calculator);
    }
}
