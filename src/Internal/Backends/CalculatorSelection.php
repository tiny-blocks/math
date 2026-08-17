<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Backends;

use TinyBlocks\Math\Calculator;
use TinyBlocks\Math\Exceptions\CalculatorNotAvailable;

final readonly class CalculatorSelection
{
    public function resolved(Calculator ...$candidates): Calculator
    {
        foreach ($candidates as $candidate) {
            if ($candidate->isAvailable()) {
                return $candidate;
            }
        }

        throw CalculatorNotAvailable::becauseNoBackendCanRun();
    }

    public function verified(Calculator $calculator): Calculator
    {
        if (!$calculator->isAvailable()) {
            throw CalculatorNotAvailable::becauseExtensionIsMissing(calculator: $calculator::class);
        }

        return $calculator;
    }
}
