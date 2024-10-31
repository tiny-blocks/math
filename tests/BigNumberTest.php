<?php

declare(strict_types=1);

namespace TinyBlocks\Math;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Math\Internal\Exceptions\DivisionByZero;
use TinyBlocks\Math\Models\LargeNumber;

final class BigNumberTest extends TestCase
{
    public function testAbsolute(): void
    {
        /** @Given a BigNumber instance */
        $negativeValue = -10.155;
        $number = LargeNumber::fromFloat(value: $negativeValue);

        /** @When calling the absolute method */
        $actual = $number->absolute();

        /** @Then the result should be an instance of BigNumber */
        self::assertInstanceOf(BigNumber::class, $actual);

        /** @And the value should be the absolute value of the negative number */
        self::assertSame(abs($negativeValue), $actual->toFloat());
        self::assertSame(sprintf('%s', abs($negativeValue)), $actual->toString());
    }

    #[DataProvider('providerForTestAdd')]
    public function testAdd(int $scale, mixed $value, mixed $other, array $expected): void
    {
        /** @Given two BigNumber instances to be added */
        $augend = LargeNumber::fromString(value: $value);
        $addend = LargeNumber::fromString(value: $other);

        /** @When adding the two BigNumber instances */
        $actual = $augend->add(addend: $addend);

        /** @Then the result should have the correct scale and values */
        self::assertSame($scale, $actual->getScale());
        self::assertSame($expected['string'], $actual->toString());
        self::assertSame(floatval($expected['float']), $actual->toFloat());
    }

    #[DataProvider('providerForTestSubtract')]
    public function testSubtract(int $scale, mixed $value, mixed $other, array $expected): void
    {
        /** @Given two BigNumber instances to be subtracted */
        $minuend = LargeNumber::fromString(value: $value);
        $subtrahend = LargeNumber::fromString(value: $other);

        /** @When subtracting the second BigNumber from the first */
        $actual = $minuend->subtract(subtrahend: $subtrahend);

        /** @Then the result should have the correct scale and values */
        self::assertSame($scale, $actual->getScale());
        self::assertSame($expected['string'], $actual->toString());
        self::assertSame(floatval($expected['float']), $actual->toFloat());
    }

    #[DataProvider('providerForTestMultiply')]
    public function testMultiply(int $scale, mixed $value, mixed $other, array $expected): void
    {
        /** @Given two BigNumber instances to be multiplied */
        $multiplicand = LargeNumber::fromString(value: $value);
        $multiplier = LargeNumber::fromString(value: $other);

        /** @When multiplying the two BigNumber instances */
        $actual = $multiplicand->multiply(multiplier: $multiplier);

        /** @Then the result should have the correct scale and values */
        self::assertSame($scale, $actual->getScale());
        self::assertSame($expected['string'], $actual->toString());
        self::assertSame(floatval($expected['float']), $actual->toFloat());
    }

    #[DataProvider('providerForTestDivide')]
    public function testDivide(int $scale, mixed $value, mixed $other, array $expected): void
    {
        /** @Given a BigNumber instance to be divided by another BigNumber */
        $dividend = LargeNumber::fromString(value: $value);
        $divisor = LargeNumber::fromString(value: $other);

        /** @When dividing the first BigNumber by the second */
        $actual = $dividend->divide(divisor: $divisor);

        /** @Then the result should have the correct scale and values */
        self::assertSame($scale, $actual->getScale());
        self::assertSame($expected['string'], $actual->toString());
        self::assertSame(floatval($expected['float']), $actual->toFloat());
    }

    #[DataProvider('providerForTestDivisionByZero')]
    public function testDivisionByZero(mixed $value, mixed $other): void
    {
        /** @Given a BigNumber instance to be divided by zero */
        $template = 'Cannot divide <%.2f> by <%.2f>.';

        /** @Then an exception DivisionByZero should be thrown with the correct message */
        $this->expectException(DivisionByZero::class);
        $this->expectExceptionMessage(sprintf($template, $value, $other));

        /** @When attempting to divide the BigNumber by zero */
        $dividend = LargeNumber::fromFloat(value: $value);
        $divisor = LargeNumber::fromFloat(value: $other);

        $dividend->divide(divisor: $divisor);
    }

    #[DataProvider('providerForTestWithRounding')]
    public function testWithRounding(RoundingMode $mode, int $scale, mixed $value, array $expected): void
    {
        /** @Given a BigNumber instance with specified rounding mode */
        $number = LargeNumber::fromFloat(value: $value, scale: $scale);

        /** @When rounding the BigNumber */
        $actual = $number->withRounding(mode: $mode);

        /** @Then the result should match the expected values */
        self::assertSame($scale, $actual->getScale());
        self::assertSame($expected['string'], $actual->toString());
        self::assertSame(floatval($expected['float']), $actual->toFloat());
    }

    #[DataProvider('providerForTestWithScale')]
    public function testWithScale(mixed $value, int $scale, int $withScale, array $expected): void
    {
        /** @Given a BigNumber instance to be scaled */
        $number = LargeNumber::fromFloat(value: $value, scale: $scale);

        /** @When applying a new scale to the BigNumber */
        $actual = $number->withScale(scale: $withScale);

        /** @Then the result should have the correct adjusted scale and values */
        self::assertSame($scale, $number->getScale());
        self::assertSame($withScale, $actual->getScale());
        self::assertSame($expected['string'], $actual->toString());
        self::assertSame(floatval($expected['float']), $actual->toFloat());
    }

    #[DataProvider('providerForTestIsZero')]
    public function testIsZero(mixed $value, bool $expected): void
    {
        /** @Given a BigNumber instance */
        $number = LargeNumber::fromFloat(value: $value);

        /** @When checking if the BigNumber is zero */
        $actual = $number->isZero();

        /** @Then the result should indicate if it is zero or not */
        self::assertSame($expected, $actual);
    }

    #[DataProvider('providerForTestIsNegative')]
    public function testIsNegative(mixed $value, bool $expected): void
    {
        /** @Given a BigNumber instance */
        $number = LargeNumber::fromFloat(value: $value);

        /** @When checking if the BigNumber is negative */
        $actual = $number->isNegative();

        /** @Then the result should indicate if it is negative or not */
        self::assertSame($expected, $actual);
    }

    #[DataProvider('providerForTestIsPositive')]
    public function testIsPositive(mixed $value, bool $expected): void
    {
        /** @Given a BigNumber instance */
        $number = LargeNumber::fromFloat(value: $value);

        /** @When checking if the BigNumber is positive */
        $actual = $number->isPositive();

        /** @Then the result should indicate if it is positive or not */
        self::assertSame($expected, $actual);
    }

    #[DataProvider('providerForTestIsNegativeOrZero')]
    public function testIsNegativeOrZero(mixed $value, bool $expected): void
    {
        /** @Given a BigNumber instance */
        $number = LargeNumber::fromFloat(value: $value);

        /** @When checking if the BigNumber is negative or zero */
        $actual = $number->isNegativeOrZero();

        /** @Then the result should indicate if it is negative or zero */
        self::assertSame($expected, $actual);
    }

    #[DataProvider('providerForTestIsPositiveOrZero')]
    public function testIsPositiveOrZero(mixed $value, bool $expected): void
    {
        /** @Given a BigNumber instance */
        $number = LargeNumber::fromFloat(value: $value);

        /** @When checking if the BigNumber is positive or zero */
        $actual = $number->isPositiveOrZero();

        /** @Then the result should indicate if it is positive or zero */
        self::assertSame($expected, $actual);
    }

    #[DataProvider('providerForTestIsLessThan')]
    public function testIsLessThan(BigNumber $value, BigNumber $other, bool $expected): void
    {
        /** @Given two BigNumber instances */
        /** @When checking if the first BigNumber is less than the second */
        $actual = $value->isLessThan(other: $other);

        /** @Then the result should indicate if it is less than */
        self::assertSame($expected, $actual);
    }

    #[DataProvider('providerForTestIsGreaterThan')]
    public function testIsGreaterThan(BigNumber $value, BigNumber $other, bool $expected): void
    {
        /** @Given two BigNumber instances */
        /** @When checking if the first BigNumber is greater than the second */
        $actual = $value->isGreaterThan(other: $other);

        /** @Then the result should indicate if it is greater than */
        self::assertSame($expected, $actual);
    }

    #[DataProvider('providerForTestIsLessThanOrEqual')]
    public function testIsLessThanOrEqual(BigNumber $value, BigNumber $other, bool $expected): void
    {
        /** @Given two BigNumber instances */
        /** @When checking if the first BigNumber is less than or equal to the second */
        $actual = $value->isLessThanOrEqual(other: $other);

        /** @Then the result should indicate if it is less than or equal to */
        self::assertSame($expected, $actual);
    }

    #[DataProvider('providerForTestIsGreaterThanOrEqual')]
    public function testIsGreaterThanOrEqual(BigNumber $value, BigNumber $other, bool $expected): void
    {
        /** @Given two BigNumber instances */
        /** @When checking if the first BigNumber is greater than or equal to the second */
        $actual = $value->isGreaterThanOrEqual(other: $other);

        /** @Then the result should indicate if it is greater than or equal to */
        self::assertSame($expected, $actual);
    }

    public static function providerForTestAdd(): array
    {
        return [
            'Adding integers'                   => [
                'scale'    => 0,
                'value'    => '1',
                'other'    => '1',
                'expected' => ['float' => 2, 'string' => '2']
            ],
            'Adding with scale'                 => [
                'scale'    => 3,
                'value'    => '1002.771',
                'other'    => '123',
                'expected' => ['float' => 1125.771, 'string' => '1125.771']
            ],
            'Adding positives and negatives'    => [
                'scale'    => 0,
                'value'    => '123',
                'other'    => '-999',
                'expected' => ['float' => -876, 'string' => '-876']
            ],
            'Adding large numbers with decimal' => [
                'scale'    => 4,
                'value'    => '-4565.9999',
                'other'    => '999999999.04',
                'expected' => ['float' => 999995433.0401, 'string' => '999995433.0401']
            ]
        ];
    }

    public static function providerForTestSubtract(): array
    {
        return [
            'Simple subtraction'     => [
                'scale'    => 2,
                'value'    => '10.22',
                'other'    => '5.11',
                'expected' => ['float' => 5.11, 'string' => '5.11']
            ],
            'Subtracting negatives'  => [
                'scale'    => 3,
                'value'    => '-10.099',
                'other'    => '-10.095',
                'expected' => ['float' => -0.004, 'string' => '-0.004']
            ],
            'Resulting in negative'  => [
                'scale'    => 0,
                'value'    => '11',
                'other'    => '12',
                'expected' => ['float' => -1, 'string' => '-1']
            ],
            'Subtraction with scale' => [
                'scale'    => 4,
                'value'    => '12.9999',
                'other'    => '6.3333',
                'expected' => ['float' => 6.6666, 'string' => '6.6666']
            ]
        ];
    }

    public static function providerForTestMultiply(): array
    {
        return [
            'Basic multiplication'        => [
                'scale'    => 0,
                'value'    => '2',
                'other'    => '2',
                'expected' => ['float' => 4, 'string' => '4']
            ],
            'Multiplying negatives'       => [
                'scale'    => 4,
                'value'    => '-2.11',
                'other'    => '55.33',
                'expected' => ['float' => -116.7463, 'string' => '-116.7463']
            ],
            'Multiplying large numbers'   => [
                'scale'    => 2,
                'value'    => '123.22',
                'other'    => '999',
                'expected' => ['float' => 123096.78, 'string' => '123096.78']
            ],
            'Multiplication with decimal' => [
                'scale'    => 1,
                'value'    => '123',
                'other'    => '0.1',
                'expected' => ['float' => 12.3, 'string' => '12.3']
            ]
        ];
    }

    public static function providerForTestDivide(): array
    {
        return [
            'Large division'                 => [
                'scale'    => 16,
                'value'    => '1.234',
                'other'    => '123.456',
                'expected' => ['float' => 0.0099954639709694, 'string' => '0.0099954639709694']
            ],
            'Division with small scale'      => [
                'scale'    => 5,
                'value'    => '1.234',
                'other'    => '100.00',
                'expected' => ['float' => 0.01234, 'string' => '0.01234']
            ],
            'Division resulting in zero'     => [
                'scale'    => 0,
                'value'    => '0.00',
                'other'    => '8',
                'expected' => ['float' => 0, 'string' => '0']
            ],
            'Division resulting in negative' => [
                'scale'    => 0,
                'value'    => '-7',
                'other'    => '0.2',
                'expected' => ['float' => -35, 'string' => '-35']
            ]
        ];
    }

    public static function providerForTestDivisionByZero(): array
    {
        return [
            'Division of zero by zero'         => ['value' => 0, 'other' => 0],
            'Division of positive by zero'     => ['value' => 20, 'other' => 0],
            'Division of decimal zero by zero' => ['value' => 0.00, 'other' => 0.00]
        ];
    }

    public static function providerForTestWithRounding(): array
    {
        return [
            'Half up rounding'   => [
                'mode'     => RoundingMode::HALF_UP,
                'scale'    => 2,
                'value'    => 0.9950,
                'expected' => ['float' => 1, 'string' => '1']
            ],
            'Half odd rounding'  => [
                'mode'     => RoundingMode::HALF_ODD,
                'scale'    => 2,
                'value'    => 0.9950,
                'expected' => ['float' => 0.99, 'string' => '0.99']
            ],
            'Half down rounding' => [
                'mode'     => RoundingMode::HALF_DOWN,
                'scale'    => 2,
                'value'    => 0.9950,
                'expected' => ['float' => 0.99, 'string' => '0.99']
            ],
            'Half even rounding' => [
                'mode'     => RoundingMode::HALF_EVEN,
                'scale'    => 2,
                'value'    => 0.9950,
                'expected' => ['float' => 1, 'string' => '1']
            ]
        ];
    }

    public static function providerForTestWithScale(): array
    {
        return [
            'Scaling zero'                     => [
                'value'     => 0,
                'scale'     => 0,
                'withScale' => 0,
                'expected'  => ['float' => 0, 'string' => '0']
            ],
            'Scaling with decimal'             => [
                'value'     => 0.0,
                'scale'     => 1,
                'withScale' => 1,
                'expected'  => ['float' => 0.0, 'string' => '0']
            ],
            'Scaling large negative number'    => [
                'value'     => -553.99999,
                'scale'     => 5,
                'withScale' => 1,
                'expected'  => ['float' => -553.9, 'string' => '-553.9']
            ],
            'Scaling with precision reduction' => [
                'value'     => 10.5555,
                'scale'     => 4,
                'withScale' => 3,
                'expected'  => ['float' => 10.555, 'string' => '10.555']
            ]
        ];
    }

    public static function providerForTestIsZero(): array
    {
        return [
            'Exact zero float'   => ['value' => 0.0, 'expected' => true],
            'NonZero negative'   => ['value' => -1, 'expected' => false],
            'Zero with decimals' => ['value' => 0.0000000000, 'expected' => true]
        ];
    }

    public static function providerForTestIsNegative(): array
    {
        return [
            'NonNegative zero'     => ['value' => 0, 'expected' => false],
            'Large negative float' => ['value' => -45.9999, 'expected' => true],
            'Small negative float' => ['value' => -0.1, 'expected' => true]
        ];
    }

    public static function providerForTestIsPositive(): array
    {
        return [
            'Negative one'         => ['value' => -1, 'expected' => false],
            'Positive integer'     => ['value' => 1, 'expected' => true],
            'Zero is not positive' => ['value' => 0, 'expected' => false]
        ];
    }

    public static function providerForTestIsNegativeOrZero(): array
    {
        return [
            'Zero'             => ['value' => 0, 'expected' => true],
            'Positive integer' => ['value' => 1, 'expected' => false],
            'Negative integer' => ['value' => -1, 'expected' => true]
        ];
    }

    public static function providerForTestIsPositiveOrZero(): array
    {
        return [
            'Zero'             => ['value' => 0, 'expected' => true],
            'Negative integer' => ['value' => -1, 'expected' => false],
            'Positive integer' => ['value' => 1, 'expected' => true]
        ];
    }

    public static function providerForTestIsLessThan(): array
    {
        return [
            'Value equal to other'                    => [
                'value'    => LargeNumber::fromFloat(value: 1),
                'other'    => LargeNumber::fromFloat(value: 1),
                'expected' => false
            ],
            'Value less than other with decimals'     => [
                'value'    => LargeNumber::fromFloat(value: 45.333, scale: 3),
                'other'    => LargeNumber::fromFloat(value: 45.334, scale: 3),
                'expected' => true
            ],
            'Negative value less than other negative' => [
                'value'    => LargeNumber::fromString(value: '-51'),
                'other'    => LargeNumber::fromString(value: '-11'),
                'expected' => true
            ]
        ];
    }

    public static function providerForTestIsGreaterThan(): array
    {
        return [
            'Equal values'                => [
                'value'    => LargeNumber::fromFloat(value: 1),
                'other'    => LargeNumber::fromFloat(value: 1),
                'expected' => false
            ],
            'Value greater than other'    => [
                'value'    => LargeNumber::fromFloat(value: 12.12),
                'other'    => LargeNumber::fromFloat(value: 12.11),
                'expected' => true
            ],
            'Negative less than positive' => [
                'value'    => LargeNumber::fromString(value: '-1.2222'),
                'other'    => LargeNumber::fromString(value: '1'),
                'expected' => false
            ]
        ];
    }

    public static function providerForTestIsLessThanOrEqual(): array
    {
        return [
            'Values are equal'               => [
                'value'    => LargeNumber::fromString(value: '88.664'),
                'other'    => LargeNumber::fromString(value: '88.664'),
                'expected' => true
            ],
            'Equal integer values'           => [
                'value'    => LargeNumber::fromFloat(value: 1),
                'other'    => LargeNumber::fromFloat(value: 1),
                'expected' => true
            ],
            'Positive greater than negative' => [
                'value'    => LargeNumber::fromString(value: '12'),
                'other'    => LargeNumber::fromString(value: '-90.95'),
                'expected' => false
            ]
        ];
    }

    public static function providerForTestIsGreaterThanOrEqual(): array
    {
        return [
            'Greater than other'      => [
                'value'    => LargeNumber::fromString(value: '45'),
                'other'    => LargeNumber::fromString(value: '45.01'),
                'expected' => false
            ],
            'Equal integer values'    => [
                'value'    => LargeNumber::fromFloat(value: 1),
                'other'    => LargeNumber::fromFloat(value: 1),
                'expected' => true
            ],
            'Very large values equal' => [
                'value'    => LargeNumber::fromFloat(value: 99.999999999999999999),
                'other'    => LargeNumber::fromFloat(value: 99.99999999999999999),
                'expected' => true
            ]
        ];
    }
}
