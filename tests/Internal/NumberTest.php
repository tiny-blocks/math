<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Math\Internal\Exceptions\InvalidNumber;

final class NumberTest extends TestCase
{
    #[DataProvider('invalidNumberDataProvider')]
    public function testInvalidNumber(mixed $value): void
    {
        /** @Given an invalid number value */
        $template = 'The value <%s> is not a valid number.';

        /** @Then an InvalidNumber exception should be thrown */
        $this->expectException(InvalidNumber::class);
        $this->expectExceptionMessage(sprintf($template, $value));

        /** @When attempting to create a Number instance with the invalid value */
        Number::from(value: $value);
    }

    public static function invalidNumberDataProvider(): array
    {
        return [
            'Single dot'              => ['value' => '.'],
            'Empty string'            => ['value' => ''],
            'String "null"'           => ['value' => 'null'],
            'String "true"'           => ['value' => 'true'],
            'String "false"'          => ['value' => 'false'],
            'Not a Number (NaN)'      => ['value' => NAN],
            'Positive infinity'       => ['value' => INF],
            'Negative infinity'       => ['value' => -INF],
            'Zero followed by x'      => ['value' => '0x'],
            'Invalid character x'     => ['value' => 'x'],
            'Double leading dots'     => ['value' => '..0'],
            'Single negative sign'    => ['value' => '-'],
            'Single positive sign'    => ['value' => '+'],
            'Negative sign with x'    => ['value' => '-x'],
            'Positive sign with x'    => ['value' => '+x'],
            'Zero followed by dash'   => ['value' => '0-'],
            'Zero followed by plus'   => ['value' => '0+'],
            'Multiple dots in value'  => ['value' => '.0.'],
            'Leading space with zero' => ['value' => ' 0']
        ];
    }
}
