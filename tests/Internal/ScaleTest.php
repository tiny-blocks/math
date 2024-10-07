<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Math\Internal\Exceptions\InvalidScale;

final class ScaleTest extends TestCase
{
    #[DataProvider('validScaleDataProvider')]
    public function testValidScale(int $value): void
    {
        /** @Given a valid scale value */
        $scale = Scale::from(value: $value);

        /** @When retrieving the scale value */
        $actual = $scale->value;

        /** @Then the scale value should match the expected value */
        self::assertEquals($value, $actual);
    }

    #[DataProvider('invalidScaleDataProvider')]
    public function testInvalidScale(int $value): void
    {
        /** @Given an invalid scale value */
        $template = 'Scale value <%s> is invalid. The value must be between <%s> and <%s>.';

        /** @Then an InvalidScale exception should be thrown */
        $this->expectException(InvalidScale::class);
        $this->expectExceptionMessage(sprintf($template, $value, 0, 2147483647));

        /** @When attempting to create a Scale with the invalid value */
        Scale::from(value: $value);
    }

    public static function validScaleDataProvider(): array
    {
        return [
            'Minimum valid scale' => ['value' => 0],
            'Maximum valid scale' => ['value' => 2147483647]
        ];
    }

    public static function invalidScaleDataProvider(): array
    {
        return [
            'PHP integer maximum'     => ['value' => PHP_INT_MAX],
            'PHP integer minimum'     => ['value' => PHP_INT_MIN],
            'Exceeding maximum scale' => ['value' => 2147483648]
        ];
    }
}
