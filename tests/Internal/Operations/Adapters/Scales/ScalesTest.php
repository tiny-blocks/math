<?php

namespace TinyBlocks\Math\Internal\Operations\Adapters\Scales;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Math\BigDecimal;
use TinyBlocks\Math\BigNumber;

final class ScalesTest extends TestCase
{
    /**
     * @dataProvider providerForTestAddition
     */
    public function testAddition(BigNumber $addend, BigNumber $augend, int $expected): void
    {
        $addition = new Addition(augend: $augend, addend: $addend);
        $scale = $addition->applyScale();

        $actual = $scale->value;

        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider providerForTestSubtraction
     */
    public function testSubtraction(BigNumber $minuend, BigNumber $subtrahend, int $expected): void
    {
        $addition = new Subtraction(minuend: $minuend, subtrahend: $subtrahend);
        $scale = $addition->applyScale();

        $actual = $scale->value;

        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider providerForTestMultiplication
     */
    public function testMultiplication(BigNumber $multiplier, BigNumber $multiplicand, int $expected): void
    {
        $multiplication = new Multiplication(multiplicand: $multiplicand, multiplier: $multiplier);
        $scale = $multiplication->applyScale();

        $actual = $scale->value;

        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider providerForTestDivision
     */
    public function testDivision(BigNumber $dividend, BigNumber $divisor, int $expected): void
    {
        $division = new Division(dividend: $dividend, divisor: $divisor);
        $scale = $division->applyScale();

        $actual = $scale->value;

        self::assertEquals($expected, $actual);
    }

    public function providerForTestAddition(): array
    {
        return [
            [
                'value' => BigDecimal::from(value: 1),
                'other' => BigDecimal::from(value: 1),
                'expected' => 0
            ],
            [
                'value' => BigDecimal::from(value: 1),
                'other' => BigDecimal::from(value: 1.1),
                'expected' => 1
            ],
            [
                'value' => BigDecimal::from(value: 1.01),
                'other' => BigDecimal::from(value: 1.1),
                'expected' => 2
            ],
            [
                'value' => BigDecimal::from(value: 1.001, scale: 3),
                'other' => BigDecimal::from(value: 1.0001),
                'expected' => 3
            ],
            [
                'value' => BigDecimal::from(value: 1.001),
                'other' => BigDecimal::from(value: 1.0001),
                'expected' => 4
            ]
        ];
    }

    public function providerForTestSubtraction(): array
    {
        return [
            [
                'value' => BigDecimal::from(value: 1),
                'other' => BigDecimal::from(value: 1),
                'expected' => 0
            ],
            [
                'value' => BigDecimal::from(value: 1),
                'other' => BigDecimal::from(value: 1.1),
                'expected' => 1
            ],
            [
                'value' => BigDecimal::from(value: 1.01),
                'other' => BigDecimal::from(value: 1.1),
                'expected' => 2
            ],
            [
                'value' => BigDecimal::from(value: 1.001, scale: 3),
                'other' => BigDecimal::from(value: 1.0001),
                'expected' => 3
            ],
            [
                'value' => BigDecimal::from(value: 1.001),
                'other' => BigDecimal::from(value: 1.0001),
                'expected' => 4
            ]
        ];
    }

    public function providerForTestMultiplication(): array
    {
        return [
            [
                'value' => BigDecimal::from(value: 1),
                'other' => BigDecimal::from(value: 1),
                'expected' => 0
            ],
            [
                'value' => BigDecimal::from(value: 1),
                'other' => BigDecimal::from(value: 1.1),
                'expected' => 1
            ],
            [
                'value' => BigDecimal::from(value: 1.01),
                'other' => BigDecimal::from(value: 1.1),
                'expected' => 2
            ],
            [
                'value' => BigDecimal::from(value: 1.001, scale: 3),
                'other' => BigDecimal::from(value: 1.0001),
                'expected' => 3
            ],
            [
                'value' => BigDecimal::from(value: 1.001),
                'other' => BigDecimal::from(value: 1.0001),
                'expected' => 4
            ]
        ];
    }

    public function providerForTestDivision(): array
    {
        return [
            [
                'value' => BigDecimal::from(value: 1),
                'other' => BigDecimal::from(value: 1),
                'expected' => 0
            ],
            [
                'value' => BigDecimal::from(value: 1),
                'other' => BigDecimal::from(value: 1.1),
                'expected' => 14
            ],
            [
                'value' => BigDecimal::from(value: 1.01),
                'other' => BigDecimal::from(value: 1.1),
                'expected' => 14
            ],
            [
                'value' => BigDecimal::from(value: 1.001, scale: 3),
                'other' => BigDecimal::from(value: 1.0001),
                'expected' => 3
            ],
            [
                'value' => BigDecimal::from(value: 1.001),
                'other' => BigDecimal::from(value: 1.0001),
                'expected' => 12
            ]
        ];
    }
}
