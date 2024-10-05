<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Operations\Rounding;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Math\BigDecimal;
use TinyBlocks\Math\BigNumber;
use TinyBlocks\Math\RoundingMode;

final class RounderTest extends TestCase
{
    #[DataProvider('roundHalfUpDataProvider')]
    public function testRoundHalfUp(BigNumber $number, float $expected): void
    {
        /** @Given a BigNumber and HALF_UP rounding mode */
        $rounder = new Rounder(mode: RoundingMode::HALF_UP, bigNumber: $number);

        /** @When rounding the BigNumber */
        $actual = $rounder->round();

        /** @Then the result should match the expected rounded value */
        self::assertEquals($expected, $actual->value);
    }

    #[DataProvider('roundHalfDownDataProvider')]
    public function testRoundHalfDown(BigNumber $number, float $expected): void
    {
        /** @Given a BigNumber and HALF_DOWN rounding mode */
        $rounder = new Rounder(mode: RoundingMode::HALF_DOWN, bigNumber: $number);

        /** @When rounding the BigNumber */
        $actual = $rounder->round();

        /** @Then the result should match the expected rounded value */
        self::assertEquals($expected, $actual->value);
    }

    #[DataProvider('roundHalfEvenDataProvider')]
    public function testRoundHalfEven(BigNumber $number, float $expected): void
    {
        /** @Given a BigNumber and HALF_EVEN rounding mode */
        $rounder = new Rounder(mode: RoundingMode::HALF_EVEN, bigNumber: $number);

        /** @When rounding the BigNumber */
        $actual = $rounder->round();

        /** @Then the result should match the expected rounded value */
        self::assertEquals($expected, $actual->value);
    }

    #[DataProvider('roundHalfOddDataProvider')]
    public function testRoundHalfOdd(BigNumber $number, float $expected): void
    {
        /** @Given a BigNumber and HALF_ODD rounding mode */
        $rounder = new Rounder(mode: RoundingMode::HALF_ODD, bigNumber: $number);

        /** @When rounding the BigNumber */
        $actual = $rounder->round();

        /** @Then the result should match the expected rounded value */
        self::assertEquals($expected, $actual->value);
    }

    public static function roundHalfUpDataProvider(): array
    {
        return [
            'Negative rounding up'             => [
                'number'   => BigDecimal::from(value: -65.2324, scale: 0),
                'expected' => -65
            ],
            'Round up with decimals'           => [
                'number'   => BigDecimal::from(value: 0.70878454657657657, scale: 5),
                'expected' => 0.70878
            ],
            'Round up to nearest whole number' => [
                'number'   => BigDecimal::from(value: 10.5, scale: 0),
                'expected' => 11
            ]
        ];
    }

    public static function roundHalfDownDataProvider(): array
    {
        return [
            'Negative rounding down'             => [
                'number'   => BigDecimal::from(value: -65.2324, scale: 0),
                'expected' => -65
            ],
            'Round down with decimals'           => [
                'number'   => BigDecimal::from(value: 0.70878454657657657, scale: 5),
                'expected' => 0.70878
            ],
            'Round down to nearest whole number' => [
                'number'   => BigDecimal::from(value: 10.5, scale: 0),
                'expected' => 10
            ]
        ];
    }

    public static function roundHalfEvenDataProvider(): array
    {
        return [
            'Round to nearest even'       => [
                'number'   => BigDecimal::from(value: 10.5, scale: 0),
                'expected' => 10
            ],
            'Negative rounding to even'   => [
                'number'   => BigDecimal::from(value: -65.2324, scale: 0),
                'expected' => -65
            ],
            'Round to even with decimals' => [
                'number'   => BigDecimal::from(value: 0.70878454657657657, scale: 5),
                'expected' => 0.70878
            ]
        ];
    }

    public static function roundHalfOddDataProvider(): array
    {
        return [
            'Round to nearest odd'       => [
                'number'   => BigDecimal::from(value: 10.5, scale: 0),
                'expected' => 11
            ],
            'Negative rounding to odd'   => [
                'number'   => BigDecimal::from(value: -65.2324, scale: 0),
                'expected' => -65
            ],
            'Round to odd with decimals' => [
                'number'   => BigDecimal::from(value: 0.70878454657657657, scale: 5),
                'expected' => 0.70878
            ]
        ];
    }
}
