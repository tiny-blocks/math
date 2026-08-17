<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Allocations;

use Generator;
use TinyBlocks\Math\Calculators;
use TinyBlocks\Math\Exceptions\DivisionByZero;
use TinyBlocks\Math\Exceptions\NegativeWeight;
use TinyBlocks\Math\Internal\Decimals\Digits;
use TinyBlocks\Math\Internal\Decimals\Scale;

final readonly class Allocation
{
    private const string ZERO = '0';

    /**
     * @param list<Digits> $weights
     * @return Generator<int, Digits>
     */
    public function of(Scale $scale, Digits $amount, array $weights): Generator
    {
        $calculator = Calculators::active();
        $integers = $this->integers(weights: $weights);
        $total = array_reduce(
            $integers,
            static fn(string $carry, string $weight): string => $calculator->add(left: $carry, right: $weight),
            self::ZERO
        );

        if ($calculator->compare(left: $total, right: self::ZERO) === 0) {
            throw DivisionByZero::becauseWeightsSumToZero();
        }

        yield from $this->shared(scale: $scale, total: $total, units: $amount->unscaled, integers: $integers);
    }

    /** @return Generator<int, Digits> */
    private function shared(Scale $scale, string $total, string $units, array $integers): Generator
    {
        $calculator = Calculators::active();
        $shares = array_map(
            static fn(string $weight): Share => Share::of(
                total: $total,
                numerator: $calculator->multiply(left: $units, right: $weight)
            ),
            $integers
        );
        $leftover = array_reduce(
            $shares,
            static fn(string $carry, Share $share): string => $calculator->add(
                left: $carry,
                right: $share->floor
            ),
            self::ZERO
        )
                |> (static fn(string $distributed): string => $calculator->subtract(
                    minuend: $units,
                    subtrahend: $distributed
                ))
                |> intval(...);
        $ranked = array_keys($shares);

        usort(
            $ranked,
            static fn(int $left, int $right): int => $shares[$left]->byLargestRemainder(other: $shares[$right])
                ?: ($left <=> $right)
        );

        foreach (array_slice($ranked, 0, $leftover) as $position) {
            $shares[$position] = $shares[$position]->incremented();
        }

        foreach ($shares as $share) {
            yield Digits::of(scale: $scale, unscaled: $share->floor);
        }
    }

    private function integers(array $weights): array
    {
        $common = array_reduce(
            $weights,
            static fn(Scale $carry, Digits $weight): Scale => $carry->greatest(other: $weight->scale),
            Scale::zero()
        );

        return array_map(
            static fn(Digits $weight): string => $weight->isNegative()
                ? throw NegativeWeight::becauseWeightIsNegative(weight: $weight->value())
                : $weight->shiftedBy(places: $common->placesFrom(other: $weight->scale)),
            $weights
        );
    }
}
