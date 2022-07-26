<?php

namespace TinyBlocks\Math;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Math\Internal\Exceptions\NonPositiveNumber;

final class PositiveBigDecimalTest extends TestCase
{
    /**
     * @dataProvider providerForTestFrom
     */
    public function testFrom(mixed $value): void
    {
        $actual = PositiveBigDecimal::from(value: $value);

        self::assertInstanceOf(BigNumber::class, $actual);
        self::assertInstanceOf(PositiveBigDecimal::class, $actual);
    }

    /**
     * @dataProvider providerForTestNonPositiveNumber
     */
    public function testNonPositiveNumber(mixed $value): void
    {
        $template = 'The <%.2f> value must be positive.';

        $this->expectException(NonPositiveNumber::class);
        $this->expectErrorMessage(sprintf($template, $value));

        PositiveBigDecimal::from(value: $value);
    }

    public function testNonPositiveNumberWithNegate(): void
    {
        $template = 'The <%.2f> value must be positive.';

        $this->expectException(NonPositiveNumber::class);
        $this->expectErrorMessage(sprintf($template, -1.00));

        $positive = PositiveBigDecimal::from(value: 10.155);
        $positive->negate();
    }

    public function providerForTestFrom(): array
    {
        return [
            [
                'value' => 1
            ],
            [
                'value' => '0.3333333333333333333333'
            ]
        ];
    }

    public function providerForTestNonPositiveNumber(): array
    {
        return [
            [
                'value' => -1
            ],
            [
                'value' => 0
            ]
        ];
    }
}
