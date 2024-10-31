<?php

declare(strict_types=1);

namespace TinyBlocks\Math;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Math\Internal\Exceptions\NonNegativeValue;

final class NegativeBigDecimalTest extends TestCase
{
    public function testFromFloat(): void
    {
        /** @Given a negative float value */
        $value = -1.0;

        /** @When creating a NegativeBigDecimal from the float */
        $actual = NegativeBigDecimal::fromFloat(value: $value);

        /** @Then the created object should be an instance of both BigNumber and NegativeBigDecimal */
        self::assertInstanceOf(BigNumber::class, $actual);
        self::assertInstanceOf(NegativeBigDecimal::class, $actual);
    }

    public function testFromString(): void
    {
        /** @Given a negative string value */
        $value = '-0.3333333333333333333333';

        /** @When creating a NegativeBigDecimal from the string */
        $actual = NegativeBigDecimal::fromString(value: $value);

        /** @Then the created object should be an instance of both BigNumber and NegativeBigDecimal */
        self::assertInstanceOf(BigNumber::class, $actual);
        self::assertInstanceOf(NegativeBigDecimal::class, $actual);
    }

    public function testNegativeValueReturnsNegativeFloat(): void
    {
        /** @Given a negative float value */
        $value = -10.155;

        /** @When creating a NegativeBigDecimal from the float */
        $negativeBigDecimal = NegativeBigDecimal::fromFloat(value: $value);

        /** @Then the toFloat method should return the correct negative value */
        self::assertSame($value, $negativeBigDecimal->toFloat());

        /** @Then the toString method should return the correct string representation */
        self::assertSame(sprintf('-%s', abs($value)), $negativeBigDecimal->toString());
    }

    #[DataProvider('dataProviderForTestNonNegativeValue')]
    public function testNonNegativeValue(mixed $value): void
    {
        /** @Given a non-negative value */
        $template = 'Value <%s> is not valid. Must be a negative number less than zero.';

        /** @Then a NonNegativeValue exception should be thrown with the correct message */
        $this->expectException(NonNegativeValue::class);
        $this->expectExceptionMessage(sprintf($template, $value));

        /** @When attempting to create a NegativeBigDecimal with a non-negative value */
        NegativeBigDecimal::fromFloat(value: $value);
    }

    public static function dataProviderForTestNonNegativeValue(): array
    {
        return [
            'Zero value'       => ['value' => 0],
            'Positive integer' => ['value' => 1]
        ];
    }
}
