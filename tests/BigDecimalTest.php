<?php

declare(strict_types=1);

namespace TinyBlocks\Math;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BigDecimalTest extends TestCase
{
    #[DataProvider('dataProviderForFromString')]
    public function testFromString(string $value): void
    {
        /** @Given a string value to create a BigDecimal instance */
        $actual = BigDecimal::fromString(value: $value);

        /** @Then the created object should be an instance of both BigNumber and BigDecimal */
        self::assertInstanceOf(BigNumber::class, $actual);
        self::assertInstanceOf(BigDecimal::class, $actual);
    }

    #[DataProvider('dataProviderForFromFloat')]
    public function testFromFloat(float $value): void
    {
        /** @Given a float value to create a BigDecimal instance */
        $actual = BigDecimal::fromFloat(value: $value);

        /** @Then the created object should be an instance of both BigNumber and BigDecimal */
        self::assertInstanceOf(BigNumber::class, $actual);
        self::assertInstanceOf(BigDecimal::class, $actual);
    }

    public static function dataProviderForFromString(): array
    {
        return [
            'Zero value'       => ['value' => '0'],
            'Decimal string'   => ['value' => '0.3333333333333333333333'],
            'Positive integer' => ['value' => '1'],
            'Negative integer' => ['value' => '-1']
        ];
    }

    public static function dataProviderForFromFloat(): array
    {
        return [
            'Zero value'       => ['value' => 0.0],
            'Positive float'   => ['value' => 0.3333333333333],
            'Positive integer' => ['value' => 1.0],
            'Negative integer' => ['value' => -1.0]
        ];
    }
}
