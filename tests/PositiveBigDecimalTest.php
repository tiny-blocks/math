<?php

declare(strict_types=1);

namespace TinyBlocks\Math;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Math\Internal\Exceptions\NonPositiveValue;
use TinyBlocks\Math\Models\CustomPositiveBigDecimal;

final class PositiveBigDecimalTest extends TestCase
{
    public function testFromFloat(): void
    {
        /** @Given a positive float value */
        $value = 1.0;

        /** @When creating a PositiveBigDecimal from the float */
        $actual = PositiveBigDecimal::fromFloat(value: $value);

        /** @Then the created object should be an instance of both BigNumber and PositiveBigDecimal */
        self::assertInstanceOf(BigNumber::class, $actual);
        self::assertInstanceOf(PositiveBigDecimal::class, $actual);

        /** @And the scale and value should be correctly initialized */
        self::assertSame($value, $actual->toFloat());
        self::assertNull($actual->getScale());
    }

    public function testFromString(): void
    {
        /** @Given a positive string value */
        $value = '0.3333333333333333333333';

        /** @When creating a PositiveBigDecimal from the string */
        $actual = PositiveBigDecimal::fromString(value: $value);

        /** @Then the created object should be an instance of both BigNumber and PositiveBigDecimal */
        self::assertInstanceOf(BigNumber::class, $actual);
        self::assertInstanceOf(PositiveBigDecimal::class, $actual);

        /** @And the scale and value should be correctly initialized */
        self::assertSame($value, $actual->toString());
        self::assertNull($actual->getScale());
    }

    #[DataProvider('dataProviderForTestNonPositiveValue')]
    public function testNonPositiveValue(mixed $value): void
    {
        /** @Given a non-positive value */
        $template = 'Value <%s> is not valid. Must be a positive number greater than zero.';

        /** @Then a NonPositiveValue exception should be thrown with the correct message */
        $this->expectException(NonPositiveValue::class);
        $this->expectExceptionMessage(sprintf($template, $value));

        /** @When attempting to create a PositiveBigDecimal with a non-positive value */
        PositiveBigDecimal::fromFloat(value: $value);
    }

    public function testNonPositiveValueWithCustomClass(): void
    {
        /** @Given a non-positive value */
        $template = 'Value <%s> is not valid. Must be a positive number greater than zero.';

        /** @Then a NonPositiveValue exception should be thrown with the correct message */
        $this->expectException(NonPositiveValue::class);
        $this->expectExceptionMessage(sprintf($template, -1.00));

        /** @When attempting to create a CustomPositiveBigDecimal with a non-positive value */
        CustomPositiveBigDecimal::fromFloat(value: -1.00);
    }

    public static function dataProviderForTestNonPositiveValue(): array
    {
        return [
            'Zero value'       => ['value' => 0],
            'Negative integer' => ['value' => -1]
        ];
    }
}
