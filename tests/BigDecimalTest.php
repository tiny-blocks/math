<?php

declare(strict_types=1);

namespace TinyBlocks\Math;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BigDecimalTest extends TestCase
{
    #[DataProvider('dataProvider')]
    public function testFrom(mixed $value): void
    {
        /** @Given a value to create a BigDecimal instance */
        $actual = BigDecimal::from(value: $value);

        /** @Then the created object should be an instance of both BigNumber and BigDecimal */
        self::assertInstanceOf(BigNumber::class, $actual);
        self::assertInstanceOf(BigDecimal::class, $actual);
    }

    public static function dataProvider(): array
    {
        return [
            'Zero value'       => ['value' => 0],
            'Decimal string'   => ['value' => '0.3333333333333333333333'],
            'Positive integer' => ['value' => 1],
            'Negative integer' => ['value' => -1]
        ];
    }
}
