<?php

namespace TinyBlocks\Math;

use PHPUnit\Framework\TestCase;

final class BigDecimalTest extends TestCase
{
    /**
     * @dataProvider providerForTestFrom
     */
    public function testFrom(mixed $value): void
    {
        $actual = BigDecimal::from(value: $value);

        self::assertInstanceOf(BigNumber::class, $actual);
        self::assertInstanceOf(BigDecimal::class, $actual);
    }

    public function providerForTestFrom(): array
    {
        return [
            [
                'value' => -1
            ],
            [
                'value' => 0
            ],
            [
                'value' => 1
            ],
            [
                'value' => '0.3333333333333333333333'
            ]
        ];
    }
}
