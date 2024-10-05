<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Operations\Adapters\Scales;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Math\BigDecimal;
use TinyBlocks\Math\BigNumber;

final class ScalesTest extends TestCase
{
    #[DataProvider('additionDataProvider')]
    public function testAddition(BigNumber $addend, BigNumber $augend, int $expected): void
    {
        /** @Given two BigNumber instances to be added */
        $addition = new Addition(augend: $augend, addend: $addend);

        /** @When applying the scale to the result of the addition */
        $actual = $addition->applyScale();

        /** @Then the scale value should match the expected value */
        self::assertEquals($expected, $actual->value);
    }

    #[DataProvider('subtractionDataProvider')]
    public function testSubtraction(BigNumber $minuend, BigNumber $subtrahend, int $expected): void
    {
        /** @Given two BigNumber instances to be subtracted */
        $subtraction = new Subtraction(minuend: $minuend, subtrahend: $subtrahend);

        /** @When applying the scale to the result of the subtraction */
        $actual = $subtraction->applyScale();

        /** @Then the scale value should match the expected value */
        self::assertEquals($expected, $actual->value);
    }

    #[DataProvider('multiplicationDataProvider')]
    public function testMultiplication(BigNumber $multiplier, BigNumber $multiplicand, int $expected): void
    {
        /** @Given two BigNumber instances to be multiplied */
        $multiplication = new Multiplication(multiplicand: $multiplicand, multiplier: $multiplier);

        /** @When applying the scale to the result of the multiplication */
        $actual = $multiplication->applyScale();

        /** @Then the scale value should match the expected value */
        self::assertEquals($expected, $actual->value);
    }

    #[DataProvider('divisionDataProvider')]
    public function testDivision(BigNumber $dividend, BigNumber $divisor, int $expected): void
    {
        /** @Given two BigNumber instances to be divided */
        $division = new Division(dividend: $dividend, divisor: $divisor);

        /** @When applying the scale to the result of the division */
        $actual = $division->applyScale();

        /** @Then the scale value should match the expected value */
        self::assertEquals($expected, $actual->value);
    }

    public static function additionDataProvider(): array
    {
        return [
            'Adding integers'                       => [
                'addend'   => BigDecimal::from(value: 1),
                'augend'   => BigDecimal::from(value: 1),
                'expected' => 0
            ],
            'Adding large decimals'                 => [
                'addend'   => BigDecimal::from(value: 1.001),
                'augend'   => BigDecimal::from(value: 1.0001),
                'expected' => 4
            ],
            'Adding integer and decimal'            => [
                'addend'   => BigDecimal::from(value: 1),
                'augend'   => BigDecimal::from(value: 1.1),
                'expected' => 1
            ],
            'Adding decimals with specific scale'   => [
                'addend'   => BigDecimal::from(value: 1.001, scale: 3),
                'augend'   => BigDecimal::from(value: 1.0001),
                'expected' => 3
            ],
            'Adding decimals with different scales' => [
                'addend'   => BigDecimal::from(value: 1.01),
                'augend'   => BigDecimal::from(value: 1.1),
                'expected' => 2
            ]
        ];
    }

    public static function subtractionDataProvider(): array
    {
        return [
            'Subtracting integers'                       => [
                'minuend'    => BigDecimal::from(value: 1),
                'subtrahend' => BigDecimal::from(value: 1),
                'expected'   => 0
            ],
            'Subtracting large decimals'                 => [
                'minuend'    => BigDecimal::from(value: 1.001),
                'subtrahend' => BigDecimal::from(value: 1.0001),
                'expected'   => 4
            ],
            'Subtracting integer and decimal'            => [
                'minuend'    => BigDecimal::from(value: 1),
                'subtrahend' => BigDecimal::from(value: 1.1),
                'expected'   => 1
            ],
            'Subtracting decimals with specific scale'   => [
                'minuend'    => BigDecimal::from(value: 1.001, scale: 3),
                'subtrahend' => BigDecimal::from(value: 1.0001),
                'expected'   => 3
            ],
            'Subtracting decimals with different scales' => [
                'minuend'    => BigDecimal::from(value: 1.01),
                'subtrahend' => BigDecimal::from(value: 1.1),
                'expected'   => 2
            ]
        ];
    }

    public static function multiplicationDataProvider(): array
    {
        return [
            'Multiplying integers'                       => [
                'multiplier'   => BigDecimal::from(value: 1),
                'multiplicand' => BigDecimal::from(value: 1),
                'expected'     => 0
            ],
            'Multiplying large decimals'                 => [
                'multiplier'   => BigDecimal::from(value: 1.001),
                'multiplicand' => BigDecimal::from(value: 1.0001),
                'expected'     => 4
            ],
            'Multiplying integer and decimal'            => [
                'multiplier'   => BigDecimal::from(value: 1),
                'multiplicand' => BigDecimal::from(value: 1.1),
                'expected'     => 1
            ],
            'Multiplying decimals with specific scale'   => [
                'multiplier'   => BigDecimal::from(value: 1.001, scale: 3),
                'multiplicand' => BigDecimal::from(value: 1.0001),
                'expected'     => 3
            ],
            'Multiplying decimals with different scales' => [
                'multiplier'   => BigDecimal::from(value: 1.01),
                'multiplicand' => BigDecimal::from(value: 1.1),
                'expected'     => 2
            ],
        ];
    }

    public static function divisionDataProvider(): array
    {
        return [
            'Dividing integers'                       => [
                'dividend' => BigDecimal::from(value: 1),
                'divisor'  => BigDecimal::from(value: 1),
                'expected' => 0
            ],
            'Dividing large decimals'                 => [
                'dividend' => BigDecimal::from(value: 1.001),
                'divisor'  => BigDecimal::from(value: 1.0001),
                'expected' => 12
            ],
            'Dividing integer and decimal'            => [
                'dividend' => BigDecimal::from(value: 1),
                'divisor'  => BigDecimal::from(value: 1.1),
                'expected' => 14
            ],
            'Dividing decimals with specific scale'   => [
                'dividend' => BigDecimal::from(value: 1.001, scale: 3),
                'divisor'  => BigDecimal::from(value: 1.0001),
                'expected' => 3
            ],
            'Dividing decimals with different scales' => [
                'dividend' => BigDecimal::from(value: 1.01),
                'divisor'  => BigDecimal::from(value: 1.1),
                'expected' => 14
            ]
        ];
    }
}
