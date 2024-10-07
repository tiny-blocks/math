<?php

declare(strict_types=1);

namespace TinyBlocks\Math;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Math\Internal\Exceptions\NonPositiveNumber;

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
    }

    #[DataProvider('dataProviderForTestNonPositiveNumber')]
    public function testNonPositiveNumber(mixed $value): void
    {
        /** @Given a non-positive value */
        $template = 'The <%.2f> value must be positive.';

        /** @Then a NonPositiveNumber exception should be thrown with the correct message */
        $this->expectException(NonPositiveNumber::class);
        $this->expectExceptionMessage(sprintf($template, $value));

        /** @When attempting to create a PositiveBigDecimal with a non-positive value */
        PositiveBigDecimal::fromFloat(value: $value);
    }

    public function testNonPositiveNumberWithNegate(): void
    {
        /** @Given a PositiveBigDecimal value */
        $template = 'The <%.2f> value must be positive.';

        /** @Then a NonPositiveNumber exception should be thrown when the value is negated */
        $this->expectException(NonPositiveNumber::class);
        $this->expectExceptionMessage(sprintf($template, -1.00));

        /** @When negating a positive number, it should trigger an exception */
        $positive = PositiveBigDecimal::fromFloat(value: 10.155);
        $positive->negate();
    }

    public static function dataProviderForTestNonPositiveNumber(): array
    {
        return [
            'Zero value'       => ['value' => 0],
            'Negative integer' => ['value' => -1]
        ];
    }
}
