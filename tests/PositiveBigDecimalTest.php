<?php

declare(strict_types=1);

namespace TinyBlocks\Math;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Math\Internal\Exceptions\NonPositiveNumber;

final class PositiveBigDecimalTest extends TestCase
{
    #[DataProvider('dataProviderForTestFrom')]
    public function testFrom(mixed $value): void
    {
        /** @Given a positive value to create a PositiveBigDecimal instance */
        $actual = PositiveBigDecimal::from(value: $value);

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
        PositiveBigDecimal::from(value: $value);
    }

    public function testNonPositiveNumberWithNegate(): void
    {
        /** @Given a PositiveBigDecimal value */
        $template = 'The <%.2f> value must be positive.';

        /** @Then a NonPositiveNumber exception should be thrown when the value is negated */
        $this->expectException(NonPositiveNumber::class);
        $this->expectExceptionMessage(sprintf($template, -1.00));

        /** @When negating a positive number, it should trigger an exception */
        $positive = PositiveBigDecimal::from(value: 10.155);
        $positive->negate();
    }

    public static function dataProviderForTestFrom(): array
    {
        return [
            'Positive integer'        => ['value' => 1],
            'Positive decimal string' => ['value' => '0.3333333333333333333333']
        ];
    }

    public static function dataProviderForTestNonPositiveNumber(): array
    {
        return [
            'Zero value'       => ['value' => 0],
            'Negative integer' => ['value' => -1]
        ];
    }
}
