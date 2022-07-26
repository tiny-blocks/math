<?php

namespace TinyBlocks\Math\Internal;

use TinyBlocks\Math\Internal\Exceptions\InvalidScale;
use PHPUnit\Framework\TestCase;

final class ScaleTest extends TestCase
{
    /**
     * @dataProvider providerForTestValidScale
     */
    public function testValidScale(int $value): void
    {
        $scale = new Scale(value: $value);

        $actual = $scale->value;

        self::assertEquals($value, $actual);
    }

    /**
     * @dataProvider providerForTestInvalidScale
     */
    public function testInvalidScale(int $value): void
    {
        $template = 'Scale value <%s> is invalid. The value must be between <%s> and <%s>.';

        $this->expectException(InvalidScale::class);
        $this->expectErrorMessage(sprintf($template, $value, 0, 2147483647));

        new Scale(value: $value);
    }

    public function providerForTestValidScale(): array
    {
        return [
            [0],
            [2147483647]
        ];
    }

    public function providerForTestInvalidScale(): array
    {
        return [
            [2147483648],
            [PHP_INT_MAX],
            [PHP_INT_MIN]
        ];
    }
}
