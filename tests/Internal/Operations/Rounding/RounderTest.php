<?php

namespace TinyBlocks\Math\Internal\Operations\Rounding;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Math\BigDecimal;
use TinyBlocks\Math\BigNumber;
use TinyBlocks\Math\RoundingMode;

final class RounderTest extends TestCase
{
    /**
     * @dataProvider providerForTestRoundHalfUp
     */
    public function testRoundHalfUp(BigNumber $number, float $expected): void
    {
        $rounder = new Rounder(mode: RoundingMode::HALF_UP, bigNumber: $number);
        $rounded = $rounder->round();

        $actual = $rounded->value;

        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider providerForTestRoundHalfDown
     */
    public function testRoundHalfDown(BigNumber $number, float $expected): void
    {
        $rounder = new Rounder(mode: RoundingMode::HALF_DOWN, bigNumber: $number);
        $rounded = $rounder->round();

        $actual = $rounded->value;

        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider providerForTestRoundHalfEven
     */
    public function testRoundHalfEven(BigNumber $number, float $expected): void
    {
        $rounder = new Rounder(mode: RoundingMode::HALF_EVEN, bigNumber: $number);
        $rounded = $rounder->round();

        $actual = $rounded->value;

        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider providerForTestRoundHalfOdd
     */
    public function testRoundHalfOdd(BigNumber $number, float $expected): void
    {
        $rounder = new Rounder(mode: RoundingMode::HALF_ODD, bigNumber: $number);
        $rounded = $rounder->round();

        $actual = $rounded->value;

        self::assertEquals($expected, $actual);
    }

    public function providerForTestRoundHalfUp(): array
    {
        return [
            [
                'value' => BigDecimal::from(value: 10.5, scale: 0),
                'expected' => 11
            ],
            [
                'value' => BigDecimal::from(value: -65.2324, scale: 0),
                'expected' => -65
            ],
            [
                'value' => BigDecimal::from(value: 0.70878454657657657, scale: 5),
                'expected' => 0.70878
            ]
        ];
    }

    public function providerForTestRoundHalfDown(): array
    {
        return [
            [
                'value' => BigDecimal::from(value: 10.5, scale: 0),
                'expected' => 10
            ],
            [
                'value' => BigDecimal::from(value: -65.2324, scale: 0),
                'expected' => -65
            ],
            [
                'value' => BigDecimal::from(value: 0.70878454657657657, scale: 5),
                'expected' => 0.70878
            ]
        ];
    }

    public function providerForTestRoundHalfEven(): array
    {
        return [
            [
                'value' => BigDecimal::from(value: 10.5, scale: 0),
                'expected' => 10
            ],
            [
                'value' => BigDecimal::from(value: -65.2324, scale: 0),
                'expected' => -65
            ],
            [
                'value' => BigDecimal::from(value: 0.70878454657657657, scale: 5),
                'expected' => 0.70878
            ]
        ];
    }

    public function providerForTestRoundHalfOdd(): array
    {
        return [
            [
                'value' => BigDecimal::from(value: 10.5, scale: 0),
                'expected' => 11
            ],
            [
                'value' => BigDecimal::from(value: -65.2324, scale: 0),
                'expected' => -65
            ],
            [
                'value' => BigDecimal::from(value: 0.70878454657657657, scale: 5),
                'expected' => 0.70878
            ]
        ];
    }
}
