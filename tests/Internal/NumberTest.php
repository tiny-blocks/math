<?php

namespace TinyBlocks\Math\Internal;

use TinyBlocks\Math\Internal\Exceptions\InvalidNumber;
use PHPUnit\Framework\TestCase;

final class NumberTest extends TestCase
{
    /**
     * @dataProvider providerForTestInvalidNumber
     */
    public function testInvalidNumber(mixed $value): void
    {
        $template = 'The value <%s> is not a valid number.';

        $this->expectException(InvalidNumber::class);
        $this->expectErrorMessage(sprintf($template, $value));

        new Number(value: $value);
    }

    public function providerForTestInvalidNumber(): array
    {
        return [
            [''],
            [NAN],
            [INF],
            ['.'],
            ['-'],
            ['+'],
            ['x'],
            [-INF],
            ['+x'],
            ['-x'],
            [' 0'],
            ['0-'],
            ['0+'],
            ['0x'],
            ['.0.'],
            ['..0'],
            ['null'],
            ['true'],
            ['false']
        ];
    }
}
