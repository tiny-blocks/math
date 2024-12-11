<?php

declare(strict_types=1);

namespace TinyBlocks\Math;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RoundingModeTest extends TestCase
{
    #[DataProvider('halfUpDataProvider')]
    public function testRoundHalfUp(float $value, string $expected): void
    {
        /** @Given a value and HALF_UP rounding mode */
        $bigNumber = BigDecimal::fromFloat(value: $value);

        /** @When rounding the value using HALF_UP */
        $actual = RoundingMode::HALF_UP->round(bigNumber: $bigNumber);

        /** @Then the result should match the expected */
        self::assertSame($expected, $actual->value);
    }

    #[DataProvider('halfOddDataProvider')]
    public function testRoundHalfOdd(float $value, string $expected): void
    {
        /** @Given a value and HALF_ODD rounding mode */
        $bigNumber = BigDecimal::fromFloat(value: $value);

        /** @When rounding the value using HALF_ODD */
        $actual = RoundingMode::HALF_ODD->round(bigNumber: $bigNumber);

        /** @Then the result should match the expected */
        self::assertSame($expected, $actual->value);
    }

    #[DataProvider('halfDownDataProvider')]
    public function testRoundHalfDown(float $value, string $expected): void
    {
        /** @Given a value and HALF_DOWN rounding mode */
        $bigNumber = BigDecimal::fromFloat(value: $value);

        /** @When rounding the value using HALF_DOWN */
        $actual = RoundingMode::HALF_DOWN->round(bigNumber: $bigNumber);

        /** @Then the result should match the expected */
        self::assertSame($expected, $actual->value);
    }

    #[DataProvider('halfEvenDataProvider')]
    public function testRoundHalfEven(float $value, string $expected): void
    {
        /** @Given a value and HALF_EVEN rounding mode */
        $bigNumber = BigDecimal::fromFloat(value: $value);

        /** @When rounding the value using HALF_EVEN */
        $actual = RoundingMode::HALF_EVEN->round(bigNumber: $bigNumber);

        /** @Then the result should match the expected */
        self::assertSame($expected, $actual->value);
    }

    public static function halfUpDataProvider(): array
    {
        return [
            'Half up, round 0.5 up to 1'      => ['value' => 0.5, 'expected' => '1'],
            'Half up, round 1.50 up to 2'     => ['value' => 1.50, 'expected' => '2'],
            'Half up, round 1.75 up to 2'     => ['value' => 1.75, 'expected' => '2'],
            'Half up, round 2.50 up to 3'     => ['value' => 2.50, 'expected' => '3'],
            'Half up, round 1.45 down to 1'   => ['value' => 1.45, 'expected' => '1'],
            'Half up, round -1.50 down to -2' => ['value' => -1.50, 'expected' => '-2']
        ];
    }

    public static function halfOddDataProvider(): array
    {
        return [
            'Half odd, round 0.5 up to 1'      => ['value' => 0.5, 'expected' => '1'],
            'Half odd, round 2.55 up to 3'     => ['value' => 2.55, 'expected' => '3'],
            'Half odd, round 1.50 down to 1'   => ['value' => 1.50, 'expected' => '1'],
            'Half odd, round 1.25 down to 1'   => ['value' => 1.25, 'expected' => '1'],
            'Half odd, round 1.75 down to 1'   => ['value' => 1.75, 'expected' => '1'],
            'Half odd, round -1.50 down to -1' => ['value' => -1.50, 'expected' => '-1']
        ];
    }

    public static function halfDownDataProvider(): array
    {
        return [
            'Half down, round 0.5 down to 0'    => ['value' => 0.5, 'expected' => '0'],
            'Half down, round 1.50 down to 1'   => ['value' => 1.50, 'expected' => '1'],
            'Half down, round 2.50 down to 2'   => ['value' => 2.50, 'expected' => '2'],
            'Half down, round 1.45 down to 0'   => ['value' => 1.45, 'expected' => '0'],
            'Half down, round 1.75 down to 1'   => ['value' => 1.75, 'expected' => '1'],
            'Half down, round -1.50 down to -2' => ['value' => -1.50, 'expected' => '-2']
        ];
    }

    public static function halfEvenDataProvider(): array
    {
        return [
            'Half even, round 2.55 up to 3'     => ['value' => 2.55, 'expected' => '3'],
            'Half even, round 1.50 up to 2'     => ['value' => 1.50, 'expected' => '2'],
            'Half even, round 1.75 up to 2'     => ['value' => 1.75, 'expected' => '2'],
            'Half even, round 0.5 down to 0'    => ['value' => 0.5, 'expected' => '0'],
            'Half even, round 1.25 down to 1'   => ['value' => 1.25, 'expected' => '1'],
            'Half even, round -1.50 down to -2' => ['value' => -1.50, 'expected' => '-2']
        ];
    }
}
