<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Math\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Math\BigDecimal;
use TinyBlocks\Math\BigDecimals;
use TinyBlocks\Math\Exceptions\NumberNotWellFormed;

final class BigDecimalsTest extends TestCase
{
    public function testFromThenAcceptsDecimalsDirectly(): void
    {
        /** @Given a decimal */
        $first = BigDecimal::of(value: '1.50');

        /** @And a second decimal */
        $second = BigDecimal::of(value: '2.25');

        /** @When a collection is built from them */
        $actual = BigDecimals::from($first, $second);

        /** @Then both are present, in order */
        self::assertSame(
            ['1.50', '2.25'],
            array_map(static fn(BigDecimal $part): string => $part->toString(), $actual->all())
        );
    }

    public function testAllThenKeepsTheOrderOfTheLiterals(): void
    {
        /** @Given three decimal literals in a deliberate order */
        $values = BigDecimals::of('3', '1', '2');

        /** @When the collection is read back */
        $actual = $values->all();

        /** @Then the order is untouched */
        self::assertSame(
            ['3', '1', '2'],
            array_map(static fn(BigDecimal $part): string => $part->toString(), $actual)
        );
    }

    #[DataProvider('sumProvider')]
    public function testSumThenStaysExactAtTheLargestScale(BigDecimals $values, string $expected, int $count): void
    {
        /** @Given a collection of decimals with its expected total and size */

        /** @When the total is taken */
        $actual = $values->sum();

        /** @Then the total is exact at the largest scale, and the size is unchanged */
        self::assertSame($expected, $actual->toString());
        self::assertCount($count, $values);
    }

    public function testIterationThenYieldsEveryDecimalInOrder(): void
    {
        /** @Given three decimal literals in a deliberate order */
        $values = BigDecimals::of('3', '1', '2');

        /** @When the collection is iterated */
        $actual = iterator_to_array($values);

        /** @Then every element is yielded in order */
        self::assertSame(
            ['3', '1', '2'],
            array_map(static fn(BigDecimal $part): string => $part->toString(), $actual)
        );
    }

    public function testOfWhenALiteralIsMalformedThenNumberNotWellFormed(): void
    {
        /** @Given a literal that is not a number */
        $value = 'abc';

        /** @Then a failure describing the malformed literal is raised */
        $this->expectException(NumberNotWellFormed::class);

        /** @When a collection is built from it */
        BigDecimals::of($value);
    }

    public function testFromWhenArgumentsAreNamedThenTheCollectionStaysAList(): void
    {
        /** @Given a decimal bound to a named argument */
        $first = BigDecimal::of(value: '1.50');

        /** @And a second decimal bound to another named argument */
        $second = BigDecimal::of(value: '2.25');

        /** @When a collection is built from them */
        $actual = BigDecimals::from(first: $first, second: $second);

        /** @Then the names are dropped and the collection stays an ordered list */
        self::assertSame(
            ['1.50', '2.25'],
            array_map(static fn(BigDecimal $part): string => $part->toString(), $actual->all())
        );
    }

    public function testJsonSerializeThenEmitsAJsonArrayOfStringsThatReadsBack(): void
    {
        /** @Given a collection of decimals */
        $decimals = BigDecimals::of('1.50', '2.00');

        /** @When it is serialized */
        $actual = $decimals->jsonSerialize();

        /** @Then it is a list of canonical strings that encodes as a JSON array and reads back */
        self::assertSame(['1.50', '2.00'], $actual);
        self::assertSame('["1.50","2.00"]', json_encode($decimals));
        self::assertEquals($decimals, BigDecimals::of(...$actual));
    }

    public static function sumProvider(): array
    {
        return [
            'Empty'            => ['values' => BigDecimals::of(), 'expected' => '0', 'count' => 0],
            'Single element'   => ['values' => BigDecimals::of('1.50'), 'expected' => '1.50', 'count' => 1],
            'Mixed scales'     => ['values' => BigDecimals::of('1.5', '2.25'), 'expected' => '3.75', 'count' => 2],
            'Negative total'   => ['values' => BigDecimals::of('1.00', '-2.00'), 'expected' => '-1.00', 'count' => 2],
            'Native integers'  => ['values' => BigDecimals::of(1, 2, 3), 'expected' => '6', 'count' => 3]
        ];
    }
}
