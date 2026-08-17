<?php

declare(strict_types=1);

namespace TinyBlocks\Math;

use Countable;
use IteratorAggregate;
use JsonSerializable;
use TinyBlocks\Math\Exceptions\NumberNotWellFormed;
use TinyBlocks\Math\Exceptions\ScaleOutOfRange;
use Traversable;

/**
 * Immutable, ordered collection of decimals.
 *
 * <p>Carries the weights handed to <code>{@see BigDecimal::allocate()}</code> and the parts it
 * produces. Order is significant in both directions: the part at a position belongs to the weight
 * at the same position, and ties in the allocation remainder are broken by position.</p>
 *
 * @implements IteratorAggregate<int, BigDecimal>
 */
final readonly class BigDecimals implements Countable, IteratorAggregate, JsonSerializable
{
    /** @var list<BigDecimal> */
    private array $values;

    private function __construct(BigDecimal ...$values)
    {
        $this->values = array_values($values);
    }

    /**
     * Creates a BigDecimals from decimal literals.
     *
     * @param string|int ...$values The literals to read, in order.
     * @return BigDecimals The created collection.
     * @throws NumberNotWellFormed If any literal is not a number, or its exponent exceeds 10000 in magnitude.
     * @throws ScaleOutOfRange If any literal implies a scale beyond the supported range.
     */
    public static function of(string|int ...$values): BigDecimals
    {
        return new BigDecimals(...array_map(
            static fn(string|int $value): BigDecimal => BigDecimal::of(value: $value),
            $values
        ));
    }

    /**
     * Creates a BigDecimals from decimals.
     *
     * @param BigDecimal ...$values The decimals, in order.
     * @return BigDecimals The created collection.
     */
    public static function from(BigDecimal ...$values): BigDecimals
    {
        return new BigDecimals(...$values);
    }

    /**
     * Returns every decimal in this collection, in order.
     *
     * @return list<BigDecimal> The decimals.
     */
    public function all(): array
    {
        return $this->values;
    }

    /**
     * Returns the exact sum of every decimal in this collection.
     *
     * <p>No rounding happens. The scale of the result is the largest scale among the elements,
     * and an empty collection sums to zero.</p>
     *
     * @return BigDecimal The exact total.
     */
    public function sum(): BigDecimal
    {
        return array_reduce(
            $this->values,
            static fn(BigDecimal $total, BigDecimal $value): BigDecimal => $total->plus(addend: $value),
            BigDecimal::zero()
        );
    }

    /**
     * Returns the number of decimals in this collection.
     *
     * @return int The element count.
     */
    public function count(): int
    {
        return count($this->values);
    }

    /**
     * Returns an iterator over the decimals, in order.
     *
     * @return Traversable<int, BigDecimal> The iterator.
     */
    public function getIterator(): Traversable
    {
        yield from $this->values;
    }

    /**
     * Returns this collection as a JSON array of strings.
     *
     * <p>Strings rather than JSON numbers, for the reason {@see Number::jsonSerialize()} gives. The
     * array reads back through <code>{@see BigDecimals::of()}</code>.</p>
     *
     * @return list<string> The canonical string form of every decimal, in order.
     */
    public function jsonSerialize(): array
    {
        return array_map(static fn(BigDecimal $value): string => $value->toString(), $this->values);
    }
}
