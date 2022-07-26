<?php

namespace TinyBlocks\Math;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Math\Internal\Exceptions\DivisionByZero;
use TinyBlocks\Math\Mock\BigNumberMock;

final class BigNumberTest extends TestCase
{
    /**
     * @dataProvider providerForTestAdd
     */
    public function testAdd(int $scale, mixed $value, mixed $other, array $expected): void
    {
        $augend = BigNumberMock::from(value: $value);
        $addend = BigNumberMock::from(value: $other);

        $actual = $augend->add(addend: $addend);

        self::assertSame($scale, $actual->getScale());
        self::assertSame($expected['string'], $actual->toString());
        self::assertSame(floatval($expected['float']), $actual->toFloat());
    }

    /**
     * @dataProvider providerForTestSubtract
     */
    public function testSubtract(int $scale, mixed $value, mixed $other, array $expected): void
    {
        $minuend = BigNumberMock::from(value: $value);
        $subtrahend = BigNumberMock::from(value: $other);

        $actual = $minuend->subtract(subtrahend: $subtrahend);

        self::assertSame($scale, $actual->getScale());
        self::assertSame($expected['string'], $actual->toString());
        self::assertSame(floatval($expected['float']), $actual->toFloat());
    }

    /**
     * @dataProvider providerForTestMultiply
     */
    public function testMultiply(int $scale, mixed $value, mixed $other, array $expected): void
    {
        $multiplicand = BigNumberMock::from(value: $value);
        $multiplier = BigNumberMock::from(value: $other);

        $actual = $multiplicand->multiply(multiplier: $multiplier);

        self::assertSame($scale, $actual->getScale());
        self::assertSame($expected['string'], $actual->toString());
        self::assertSame(floatval($expected['float']), $actual->toFloat());
    }

    /**
     * @dataProvider providerForTestDivide
     */
    public function testDivide(int $scale, mixed $value, mixed $other, array $expected): void
    {
        $dividend = BigNumberMock::from(value: $value);
        $divisor = BigNumberMock::from(value: $other);

        $actual = $dividend->divide(divisor: $divisor);

        self::assertSame($scale, $actual->getScale());
        self::assertSame($expected['string'], $actual->toString());
        self::assertSame(floatval($expected['float']), $actual->toFloat());
    }

    /**
     * @dataProvider providerForTestDivisionByZero
     */
    public function testDivisionByZero(mixed $value, mixed $other): void
    {
        $template = 'Cannot divide <%.2f> by <%.2f>.';

        $this->expectException(DivisionByZero::class);
        $this->expectErrorMessage(sprintf($template, $value, $other));

        $dividend = BigNumberMock::from(value: $value);
        $divisor = BigNumberMock::from(value: $other);

        $dividend->divide(divisor: $divisor);
    }

    /**
     * @dataProvider providerForTestWithRounding
     */
    public function testWithRounding(RoundingMode $mode, int $scale, mixed $value, array $expected): void
    {
        $value = BigNumberMock::from(value: $value, scale: $scale);

        $actual = $value->withRounding(mode: $mode);

        self::assertSame($scale, $actual->getScale());
        self::assertSame($expected['string'], $actual->toString());
        self::assertSame(floatval($expected['float']), $actual->toFloat());
    }

    /**
     * @dataProvider providerForTestWithScale
     */
    public function testWithScale(mixed $value, int $scale, int $withScale, array $expected): void
    {
        $value = BigNumberMock::from(value: $value, scale: $scale);

        $actual = $value->withScale(scale: $withScale);

        self::assertEquals($scale, $value->getScale());
        self::assertEquals($withScale, $actual->getScale());
        self::assertSame($expected['string'], $actual->toString());
        self::assertSame(floatval($expected['float']), $actual->toFloat());
    }

    public function testNegate(): void
    {
        $value = BigNumberMock::from(value: 25.001, scale: 3);

        $actual = $value->negate();

        self::assertSame(3, $actual->getScale());
        self::assertSame(-25.001, $actual->toFloat());
        self::assertSame('-25.001', $actual->toString());
    }

    /**
     * @dataProvider providerForTestIsZero
     */
    public function testIsZero(mixed $value, bool $expected): void
    {
        $value = BigNumberMock::from(value: $value);

        $actual = $value->isZero();

        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider providerForTestIsNegative
     */
    public function testIsNegative(mixed $value, bool $expected): void
    {
        $value = BigNumberMock::from(value: $value);

        $actual = $value->isNegative();

        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider providerForTestIsPositive
     */
    public function testIsPositive(mixed $value, bool $expected): void
    {
        $value = BigNumberMock::from(value: $value);

        $actual = $value->isPositive();

        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider providerForTestIsNegativeOrZero
     */
    public function testIsNegativeOrZero(mixed $value, bool $expected): void
    {
        $value = BigNumberMock::from(value: $value);

        $actual = $value->isNegativeOrZero();

        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider providerForTestIsPositiveOrZero
     */
    public function testIsPositiveOrZero(mixed $value, bool $expected): void
    {
        $value = BigNumberMock::from(value: $value);

        $actual = $value->isPositiveOrZero();

        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider providerForTestIsLessThan
     */
    public function testIsLessThan(BigNumber $value, BigNumber $other, bool $expected): void
    {
        $actual = $value->isLessThan(other: $other);

        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider providerForTestIsGreaterThan
     */
    public function testIsGreaterThan(BigNumber $value, BigNumber $other, bool $expected): void
    {
        $actual = $value->isGreaterThan(other: $other);

        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider providerForTestIsLessThanOrEqual
     */
    public function testIsLessThanOrEqual(BigNumber $value, BigNumber $other, bool $expected): void
    {
        $actual = $value->isLessThanOrEqual(other: $other);

        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider providerForTestIsGreaterThanOrEqual
     */
    public function testIsGreaterThanOrEqual(BigNumber $value, BigNumber $other, bool $expected): void
    {
        $actual = $value->isGreaterThanOrEqual(other: $other);

        self::assertEquals($expected, $actual);
    }

    public function providerForTestAdd(): array
    {
        return [
            [
                'scale' => 0,
                'value' => 1,
                'other' => 1,
                'expected' => [
                    'float' => 2,
                    'string' => '2'
                ]
            ],
            [
                'scale' => 0,
                'value' => '123',
                'other' => '-999',
                'expected' => [
                    'float' => -876,
                    'string' => '-876'
                ]
            ],
            [
                'scale' => 3,
                'value' => 1002.771,
                'other' => 123,
                'expected' => [
                    'float' => 1125.771,
                    'string' => '1125.771'
                ]
            ],
            [
                'scale' => 4,
                'value' => '-4565.9999',
                'other' => '999999999.04',
                'expected' => [
                    'float' => 999995433.0401,
                    'string' => '999995433.0401'
                ]
            ]
        ];
    }

    public function providerForTestSubtract(): array
    {
        return [
            [
                'scale' => 2,
                'value' => 10.22,
                'other' => 5.11,
                'expected' => [
                    'float' => 5.11,
                    'string' => '5.11'
                ]
            ],
            [
                'scale' => 4,
                'value' => 12.9999,
                'other' => 06.3333,
                'expected' => [
                    'float' => 6.6666,
                    'string' => '6.6666'
                ]
            ],
            [
                'scale' => 3,
                'value' => -10.099,
                'other' => -10.095,
                'expected' => [
                    'float' => -0.004,
                    'string' => '-0.004'
                ]
            ],
            [
                'scale' => 0,
                'value' => 11,
                'other' => 12,
                'expected' => [
                    'float' => -1,
                    'string' => '-1'
                ]
            ]
        ];
    }

    public function providerForTestMultiply(): array
    {
        return [
            [
                'scale' => 0,
                'value' => 2,
                'other' => 2,
                'expected' => [
                    'float' => 4,
                    'string' => '4'
                ]
            ],
            [
                'scale' => 1,
                'value' => 123.0,
                'other' => 0.1,
                'expected' => [
                    'float' => 12.3,
                    'string' => '12.3'
                ]
            ],
            [
                'scale' => 2,
                'value' => '123.22',
                'other' => '999',
                'expected' => [
                    'float' => 123096.78,
                    'string' => '123096.78'
                ]
            ],
            [
                'scale' => 4,
                'value' => '-2.11',
                'other' => '55.33',
                'expected' => [
                    'float' => -116.7463,
                    'string' => '-116.7463'
                ]
            ]
        ];
    }

    public function providerForTestDivide(): array
    {
        return [
            [
                'scale' => 5,
                'value' => 1.234,
                'other' => '100.00',
                'expected' => [
                    'float' => 0.01234,
                    'string' => '0.01234'
                ]
            ],
            [
                'scale' => 0,
                'value' => -7,
                'other' => 0.2,
                'expected' => [
                    'float' => -35,
                    'string' => '-35'
                ]
            ],
            [
                'scale' => 16,
                'value' => '1.234',
                'other' => '123.456',
                'expected' => [
                    'float' => 0.0099954639709694,
                    'string' => '0.0099954639709694'
                ]
            ],
            [
                'scale' => 0,
                'value' => '0.00',
                'other' => 8,
                'expected' => [
                    'float' => 0,
                    'string' => '0'
                ]
            ]
        ];
    }

    public function providerForTestDivisionByZero(): array
    {
        return [
            [
                'value' => 20,
                'other' => 0
            ],
            [
                'value' => 0,
                'other' => 0
            ],
            [
                'value' => '0.00',
                'other' => '0.00'
            ]
        ];
    }

    public function providerForTestWithRounding(): array
    {
        return [
            [
                'mode' => RoundingMode::HALF_UP,
                'scale' => 2,
                'value' => 0.9950,
                'expected' => [
                    'float' => 1,
                    'string' => '1'
                ]
            ],
            [
                'mode' => RoundingMode::HALF_DOWN,
                'scale' => 2,
                'value' => 0.9950,
                'expected' => [
                    'float' => 0.99,
                    'string' => '0.99'
                ]
            ],
            [
                'mode' => RoundingMode::HALF_EVEN,
                'scale' => 2,
                'value' => 0.9950,
                'expected' => [
                    'float' => 1,
                    'string' => '1'
                ]
            ],
            [
                'mode' => RoundingMode::HALF_ODD,
                'scale' => 2,
                'value' => 0.9950,
                'expected' => [
                    'float' => 0.99,
                    'string' => '0.99'
                ]
            ]
        ];
    }

    public function providerForTestWithScale(): array
    {
        return [
            [
                'value' => 0,
                'scale' => 0,
                'withScale' => 0,
                'expected' => [
                    'float' => 0,
                    'string' => '0'
                ]
            ],
            [
                'value' => '0.0',
                'scale' => 1,
                'withScale' => 1,
                'expected' => [
                    'float' => '0.0',
                    'string' => '0.0'
                ]
            ],
            [
                'value' => 10.5555,
                'scale' => 4,
                'withScale' => 3,
                'expected' => [
                    'float' => 10.555,
                    'string' => '10.555'
                ]
            ],
            [
                'value' => '-553.99999',
                'scale' => 5,
                'withScale' => 1,
                'expected' => [
                    'float' => -553.9,
                    'string' => '-553.9'
                ]
            ]
        ];
    }

    public function providerForTestIsZero(): array
    {
        return [
            [
                'value' => 0.0,
                'expected' => true
            ],
            [
                'value' => '0.0000000000',
                'expected' => true
            ],
            [
                'value' => -1,
                'expected' => false
            ]
        ];
    }

    public function providerForTestIsNegative(): array
    {
        return [
            [
                'value' => 0,
                'expected' => false
            ],
            [
                'value' => -45.9999,
                'expected' => true
            ],
            [
                'value' => -0.1,
                'expected' => true
            ]
        ];
    }

    public function providerForTestIsPositive(): array
    {
        return [
            [
                'value' => 0,
                'expected' => false
            ],
            [
                'value' => -1,
                'expected' => false
            ],
            [
                'value' => 1,
                'expected' => true
            ]
        ];
    }

    public function providerForTestIsNegativeOrZero(): array
    {
        return [
            [
                'value' => 0,
                'expected' => true
            ],
            [
                'value' => -1,
                'expected' => true
            ],
            [
                'value' => 1,
                'expected' => false
            ]
        ];
    }

    public function providerForTestIsPositiveOrZero(): array
    {
        return [
            [
                'value' => 0,
                'expected' => true
            ],
            [
                'value' => -1,
                'expected' => false
            ],
            [
                'value' => 1,
                'expected' => true
            ]
        ];
    }

    public function providerForTestIsLessThan(): array
    {
        return [
            [
                'value' => BigNumberMock::from(value: 45.333, scale: 3),
                'other' => BigNumberMock::from(value: 45.334, scale: 3),
                'expected' => true
            ],
            [
                'value' => BigNumberMock::from(value: 1),
                'other' => BigNumberMock::from(value: 1),
                'expected' => false
            ],
            [
                'value' => BigNumberMock::from(value: '-51'),
                'other' => BigNumberMock::from(value: '-11'),
                'expected' => true
            ]
        ];
    }

    public function providerForTestIsGreaterThan(): array
    {
        return [
            [
                'value' => BigNumberMock::from(value: 12.12),
                'other' => BigNumberMock::from(value: 12.11),
                'expected' => true
            ],
            [
                'value' => BigNumberMock::from(value: 1),
                'other' => BigNumberMock::from(value: 1),
                'expected' => false
            ],
            [
                'value' => BigNumberMock::from(value: '-1.2222'),
                'other' => BigNumberMock::from(value: '1'),
                'expected' => false
            ]
        ];
    }

    public function providerForTestIsLessThanOrEqual(): array
    {
        return [
            [
                'value' => BigNumberMock::from(value: '88.664'),
                'other' => BigNumberMock::from(value: '88.664'),
                'expected' => true
            ],
            [
                'value' => BigNumberMock::from(value: 1),
                'other' => BigNumberMock::from(value: 1),
                'expected' => true
            ],
            [
                'value' => BigNumberMock::from(value: '12'),
                'other' => BigNumberMock::from(value: '-90.95'),
                'expected' => false
            ]
        ];
    }

    public function providerForTestIsGreaterThanOrEqual(): array
    {
        return [
            [
                'value' => BigNumberMock::from(value: 99.999999999999999999),
                'other' => BigNumberMock::from(value: 99.99999999999999999),
                'expected' => true
            ],
            [
                'value' => BigNumberMock::from(value: 1),
                'other' => BigNumberMock::from(value: 1),
                'expected' => true
            ],
            [
                'value' => BigNumberMock::from(value: '45'),
                'other' => BigNumberMock::from(value: '45.01'),
                'expected' => false
            ]
        ];
    }
}
