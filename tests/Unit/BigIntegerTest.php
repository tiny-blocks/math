<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Math\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Math\BigDecimal;
use TinyBlocks\Math\BigInteger;
use TinyBlocks\Math\BigRational;
use TinyBlocks\Math\Exceptions\BaseOutOfRange;
use TinyBlocks\Math\Exceptions\DivisionByZero;
use TinyBlocks\Math\Exceptions\ExponentOutOfRange;
use TinyBlocks\Math\Exceptions\InexactConversion;
use TinyBlocks\Math\Exceptions\IntegerOverflow;
use TinyBlocks\Math\Exceptions\MathFailure;
use TinyBlocks\Math\Exceptions\NegativeExponent;
use TinyBlocks\Math\Exceptions\NegativeRoot;
use TinyBlocks\Math\Exceptions\NumberNotWellFormed;
use TinyBlocks\Math\Number;

final class BigIntegerTest extends TestCase
{
    public function testOneThenHoldsOne(): void
    {
        /** @Given nothing but the factory */

        /** @When one is created */
        $actual = BigInteger::one();

        /** @Then it holds one */
        self::assertSame('1', $actual->toString());
    }

    public function testZeroThenHoldsZero(): void
    {
        /** @Given nothing but the factory */

        /** @When zero is created */
        $actual = BigInteger::zero();

        /** @Then it holds zero */
        self::assertSame('0', $actual->toString());
    }

    #[DataProvider('literalProvider')]
    public function testOfThenReadsTheLiteral(string|int $value, string $expected): void
    {
        /** @Given an integer literal and the value it stands for */

        /** @When an integer is created from it */
        $actual = BigInteger::of(value: $value);

        /** @Then the value is read exactly */
        self::assertSame($expected, $actual->toString());
    }

    public function testNegatedThenFlipsTheSign(): void
    {
        /** @Given a positive integer */
        $number = BigInteger::of(value: 5);

        /** @When it is negated */
        $actual = $number->negated();

        /** @Then the sign is flipped */
        self::assertSame('-5', $actual->toString());
    }

    public function testPlusThenSumsBothIntegers(): void
    {
        /** @Given an integer */
        $augend = BigInteger::of(value: '9223372036854775807');

        /** @And another integer beyond the native range */
        $addend = BigInteger::of(value: '9223372036854775807');

        /** @When they are added */
        $actual = $augend->plus(addend: $addend);

        /** @Then the sum keeps every digit */
        self::assertSame('18446744073709551614', $actual->toString());
    }

    #[DataProvider('moduloProvider')]
    public function testModuloThenIsNeverNegative(string $dividend, string $divisor, string $modulo): void
    {
        /** @Given a dividend and a modulus with the expected mathematical modulo */

        /** @When the modulo is taken */
        $actual = BigInteger::of(value: $dividend)->modulo(modulus: BigInteger::of(value: $divisor));

        /** @Then the result is never negative */
        self::assertSame($modulo, $actual->toString());
    }

    public function testAbsoluteThenRemovesTheSign(): void
    {
        /** @Given a negative integer */
        $number = BigInteger::of(value: -5);

        /** @When its magnitude is taken */
        $actual = $number->absolute();

        /** @Then the sign is gone */
        self::assertSame('5', $actual->toString());
    }

    #[DataProvider('equalityProvider')]
    public function testEqualsThenComparesStructurally(string $value, string $other, bool $expected): void
    {
        /** @Given two integer literals and whether they are structurally equal */

        /** @When they are compared */
        $actual = BigInteger::of(value: $value)->equals(other: BigInteger::of(value: $other));

        /** @Then structural equality matches the expectation */
        self::assertSame($expected, $actual);
    }

    #[DataProvider('powerProvider')]
    public function testPowerThenReturnsTheExactResult(string $value, int $exponent, string $power): void
    {
        /** @Given a value, an exponent, and the expected power */

        /** @When it is raised to that power */
        $actual = BigInteger::of(value: $value)->power(exponent: $exponent);

        /** @Then the result is exact */
        self::assertSame($power, $actual->toString());
    }

    public function testMinusThenSubtractsTheSubtrahend(): void
    {
        /** @Given an integer beyond the float mantissa */
        $minuend = BigInteger::of(value: '10000000000000000001');

        /** @And a larger integer that differs only in the last digit */
        $subtrahend = BigInteger::of(value: '10000000000000000003');

        /** @When the larger is subtracted */
        $actual = $minuend->minus(subtrahend: $subtrahend);

        /** @Then the difference keeps the last digit rather than collapsing to zero */
        self::assertSame('-2', $actual->toString());
    }

    #[DataProvider('quotientProvider')]
    public function testQuotientThenTruncatesTowardZero(string $dividend, string $divisor, string $quotient): void
    {
        /** @Given a dividend and a divisor with the expected truncated quotient */

        /** @When the quotient is taken */
        $actual = BigInteger::of(value: $dividend)->quotient(divisor: BigInteger::of(value: $divisor));

        /** @Then the quotient truncates toward zero */
        self::assertSame($quotient, $actual->toString());
    }

    #[DataProvider('signProvider')]
    public function testSignPredicatesThenAgreeOnTheSign(
        string $value,
        bool $isZero,
        bool $isNegative,
        bool $isPositive
    ): void {
        /** @Given an integer literal and its expected sign */

        /** @When the sign predicates are asked */
        $actual = BigInteger::of(value: $value);

        /** @Then all three agree */
        self::assertSame($isZero, $actual->isZero());
        self::assertSame($isNegative, $actual->isNegative());
        self::assertSame($isPositive, $actual->isPositive());
    }

    public function testToBigDecimalThenCarriesScaleZero(): void
    {
        /** @Given an integer */
        $number = BigInteger::of(value: -42);

        /** @When it is converted to a decimal */
        $actual = $number->toBigDecimal();

        /** @Then the decimal has no fractional digits */
        self::assertSame(0, $actual->scale());
    }

    public function testJsonSerializeThenEmitsAJsonString(): void
    {
        /** @Given an integer beyond the precision of a JSON number */
        $number = BigInteger::of(value: '9007199254740993');

        /** @When it is encoded */
        $actual = json_encode($number);

        /** @Then it is a JSON string rather than a JSON number */
        self::assertSame('"9007199254740993"', $actual);
    }

    #[DataProvider('rootProvider')]
    public function testSquareRootThenTruncatesTowardZero(string $value, string $root): void
    {
        /** @Given a value and the expected truncated root */

        /** @When its square root is taken */
        $actual = BigInteger::of(value: $value)->squareRoot();

        /** @Then the root is truncated toward zero */
        self::assertSame($root, $actual->toString());
    }

    #[DataProvider('parityProvider')]
    public function testIsEvenThenReportsDivisibilityByTwo(string $value, bool $expected): void
    {
        /** @Given an integer literal and whether it is even */

        /** @When its parity is asked */
        $actual = BigInteger::of(value: $value);

        /** @Then the answer matches, and is the opposite of being odd */
        self::assertSame($expected, $actual->isEven());
        self::assertSame(!$expected, $actual->isOdd());
    }

    public function testDividedByThenReturnsAnExactFraction(): void
    {
        /** @Given an integer */
        $dividend = BigInteger::of(value: 10);

        /** @And a divisor that does not divide it evenly */
        $divisor = BigInteger::of(value: 4);

        /** @When it is divided */
        $actual = $dividend->dividedBy(divisor: $divisor);

        /** @Then the exact fraction is returned in lowest terms */
        self::assertSame('5/2', $actual->toString());
    }

    public function testToBigRationalThenHasADenominatorOfOne(): void
    {
        /** @Given an integer */
        $number = BigInteger::of(value: 7);

        /** @When it is converted to a fraction */
        $actual = $number->toBigRational();

        /** @Then the denominator is one */
        self::assertSame('1', $actual->denominator()->toString());
    }

    public function testHashCodeWhenValuesMatchThenHashesMatch(): void
    {
        /** @Given an integer */
        $number = BigInteger::of(value: 5);

        /** @And another integer holding the same value */
        $other = BigInteger::of(value: '5.0');

        /** @When both hashes are taken */
        $actual = $number->hashCode();

        /** @Then they agree */
        self::assertSame($other->hashCode(), $actual);
    }

    #[DataProvider('divisorProvider')]
    public function testGreatestCommonDivisorThenIgnoresTheSigns(string $value, string $other): void
    {
        /** @Given two integer literals sharing divisors, in any sign combination */

        /** @When their greatest common divisor is taken */
        $actual = BigInteger::of(value: $value)->greatestCommonDivisor(other: BigInteger::of(value: $other));

        /** @Then the result is positive whatever the signs were */
        self::assertSame('6', $actual->toString());
    }

    #[DataProvider('remainderProvider')]
    public function testRemainderThenCarriesTheSignOfTheDividend(
        string $dividend,
        string $divisor,
        string $remainder
    ): void {
        /** @Given a dividend and a divisor with the expected remainder */

        /** @When the remainder is taken */
        $actual = BigInteger::of(value: $dividend)->remainder(divisor: BigInteger::of(value: $divisor));

        /** @Then the remainder follows the dividend */
        self::assertSame($remainder, $actual->toString());
    }

    #[DataProvider('positionalBaseProvider')]
    public function testBaseConversionThenRoundTripsThroughTheBase(int $base, string $text, string $value): void
    {
        /** @Given a base, a representation in it, and the decimal value */

        /** @When the value is written in that base */
        $actual = BigInteger::of(value: $value);

        /** @Then the text matches, and reading it back yields the value */
        self::assertSame($text, $actual->toBase(base: $base));
        self::assertSame($value, BigInteger::fromBase(base: $base, value: $text)->toString());
    }

    #[DataProvider('comparisonProvider')]
    public function testComparisonPredicatesThenAgreeWithCompareTo(string $value, string $other, int $expected): void
    {
        /** @Given an integer literal */
        $number = BigInteger::of(value: $value);

        /** @And another to compare it against */
        $against = BigInteger::of(value: $other);

        /** @When they are compared */
        $actual = $number->compareTo(other: $against);

        /** @Then every predicate agrees with the comparison */
        self::assertSame($expected, $actual);
        self::assertSame($expected === 0, $number->isEqualTo(other: $against));
        self::assertSame($expected < 0, $number->isLessThan(other: $against));
        self::assertSame($expected > 0, $number->isGreaterThan(other: $against));
        self::assertSame($expected <= 0, $number->isLessThanOrEqualTo(other: $against));
        self::assertSame($expected >= 0, $number->isGreaterThanOrEqualTo(other: $against));
    }

    public function testQuotientWhenDivisorIsZeroThenDivisionByZero(): void
    {
        /** @Given an integer */
        $number = BigInteger::of(value: 7);

        /** @Then a failure naming the dividend is raised */
        $this->expectException(DivisionByZero::class);
        $this->expectExceptionMessage('Cannot divide <7> by zero.');

        /** @When a truncated quotient by zero is asked for */
        $number->quotient(divisor: BigInteger::zero());
    }

    public function testDividedByWhenDivisorIsZeroThenDivisionByZero(): void
    {
        /** @Given an integer */
        $number = BigInteger::of(value: 7);

        /** @Then a failure naming the dividend is raised */
        $this->expectException(DivisionByZero::class);
        $this->expectExceptionMessage('Cannot divide <7> by zero.');

        /** @When it is divided by zero */
        $number->dividedBy(divisor: BigInteger::zero());
    }

    public function testRemainderWhenDivisorIsZeroThenDivisionByZero(): void
    {
        /** @Given an integer */
        $number = BigInteger::of(value: 7);

        /** @Then a failure naming the dividend is raised */
        $this->expectException(DivisionByZero::class);
        $this->expectExceptionMessage('Cannot divide <7> by zero.');

        /** @When a remainder by zero is asked for */
        $number->remainder(divisor: BigInteger::zero());
    }

    public function testSquareRootWhenValueIsNegativeThenNegativeRoot(): void
    {
        /** @Given a negative integer */
        $number = BigInteger::of(value: -4);

        /** @Then a failure describing the negative radicand is raised */
        $this->expectException(NegativeRoot::class);
        $this->expectExceptionMessage('Cannot take the square root of the negative value <-4>.');

        /** @When its square root is taken */
        $number->squareRoot();
    }

    #[DataProvider('nativeRangeProvider')]
    public function testToIntWhenValueFitsThenReturnsTheNativeInteger(string $value, int $expected): void
    {
        /** @Given an integer literal at a boundary of the native range */

        /** @When it is converted to a native integer */
        $actual = BigInteger::of(value: $value)->toInt();

        /** @Then the boundary itself is accepted */
        self::assertSame($expected, $actual);
    }

    public function testMultipliedByThenStaysExactPastTheFloatMantissa(): void
    {
        /** @Given an integer beyond the float mantissa */
        $multiplicand = BigInteger::of(value: '10000000000000000001');

        /** @And a multiplier that forces every digit to carry */
        $multiplier = BigInteger::of(value: '10000000000000000001');

        /** @When they are multiplied */
        $actual = $multiplicand->multipliedBy(multiplier: $multiplier);

        /** @Then the product keeps every digit a float would have discarded */
        self::assertSame('100000000000000000020000000000000000001', $actual->toString());
    }

    public function testEveryFailureThenCanBeCaughtAsASingleMathFailure(): void
    {
        /** @Given an integer */
        $number = BigInteger::one();

        /** @Then the division failure is reachable through the shared contract */
        $this->expectException(MathFailure::class);

        /** @When it is divided by zero */
        $number->quotient(divisor: BigInteger::zero());
    }

    public function testFromBaseWhenValueIsEmptyThenNumberNotWellFormed(): void
    {
        /** @Given an empty literal */
        $value = '';

        /** @Then a failure describing the malformed literal is raised */
        $this->expectException(NumberNotWellFormed::class);

        /** @When an integer is read in base sixteen */
        BigInteger::fromBase(base: 16, value: $value);
    }

    public function testPowerWhenExponentIsNegativeThenNegativeExponent(): void
    {
        /** @Given an integer */
        $number = BigInteger::of(value: 2);

        /** @Then a failure describing the negative exponent is raised */
        $this->expectException(NegativeExponent::class);
        $this->expectExceptionMessage('Exponent must not be negative, got <-1>.');

        /** @When it is raised to a negative power */
        $number->power(exponent: -1);
    }

    #[DataProvider('crossTypeProvider')]
    public function testCompareToWhenTheOtherIsAnotherTypeThenStaysExact(
        string $value,
        Number $other,
        int $expected
    ): void {
        /** @Given an integer and a number of another type with the expected comparison */

        /** @When they are compared */
        $actual = BigInteger::of(value: $value)->compareTo(other: $other);

        /** @Then the comparison crosses the type boundary */
        self::assertSame($expected, $actual);
    }

    public function testFromBaseWhenLiteralIsUppercaseThenItIsReadTheSame(): void
    {
        /** @Given a hexadecimal literal written in uppercase */
        $value = '-FF';

        /** @When it is read in base sixteen */
        $actual = BigInteger::fromBase(base: 16, value: $value);

        /** @Then the case makes no difference */
        self::assertSame('-255', $actual->toString());
    }

    public function testFromBaseWhenSignIsRepeatedThenNumberNotWellFormed(): void
    {
        /** @Given a literal carrying more than one leading minus */
        $value = '--ff';

        /** @Then a failure describing the malformed literal is raised */
        $this->expectException(NumberNotWellFormed::class);

        /** @When it is read in base sixteen */
        BigInteger::fromBase(base: 16, value: $value);
    }

    public function testToIntWhenValueBelowTheNativeRangeThenIntegerOverflow(): void
    {
        /** @Given an integer one below the smallest native integer */
        $number = BigInteger::of(value: '-9223372036854775809');

        /** @Then a failure describing the overflow is raised */
        $this->expectException(IntegerOverflow::class);

        /** @When it is converted to a native integer */
        $number->toInt();
    }

    public function testOfWhenValueCarriesAFractionalPartThenInexactConversion(): void
    {
        /** @Given a literal with a non-zero fractional part */
        $value = '1.5';

        /** @Then a failure describing the lost fractional part is raised */
        $this->expectException(InexactConversion::class);
        $this->expectExceptionMessage('Value <1.5> has a fractional part and is not an integer.');

        /** @When an integer is created from it */
        BigInteger::of(value: $value);
    }

    public function testToIntWhenValueExceedsTheNativeRangeThenIntegerOverflow(): void
    {
        /** @Given an integer one beyond the largest native integer */
        $number = BigInteger::of(value: '9223372036854775808');

        /** @Then a failure describing the overflow is raised */
        $this->expectException(IntegerOverflow::class);

        /** @When it is converted to a native integer */
        $number->toInt();
    }

    public function testFromBaseWhenBaseIsAboveTheSupportedRangeThenBaseOutOfRange(): void
    {
        /** @Given a base above thirty-six */
        $base = 37;

        /** @Then a failure describing the bounds is raised */
        $this->expectException(BaseOutOfRange::class);

        /** @When an integer is read in that base */
        BigInteger::fromBase(base: $base, value: '1');
    }

    public function testFromBaseWhenCharacterIsNotInTheBaseThenNumberNotWellFormed(): void
    {
        /** @Given a literal carrying a character the base does not define */
        $value = '9';

        /** @Then a failure describing the malformed literal is raised */
        $this->expectException(NumberNotWellFormed::class);

        /** @When an integer is read in base two */
        BigInteger::fromBase(base: 2, value: $value);
    }

    public function testFromBaseWhenBaseIsOutsideTheSupportedRangeThenBaseOutOfRange(): void
    {
        /** @Given a base below two */
        $base = 1;

        /** @Then a failure describing the bounds is raised */
        $this->expectException(BaseOutOfRange::class);
        $this->expectExceptionMessage('Base must be within 2 to 36, got <1>.');

        /** @When an integer is read in that base */
        BigInteger::fromBase(base: $base, value: '1');
    }

    public function testPowerWhenExponentExceedsTheMagnitudeCeilingThenExponentOutOfRange(): void
    {
        /** @Given an integer */
        $number = BigInteger::of(value: 2);

        /** @Then a failure naming the rejected exponent is raised */
        $this->expectException(ExponentOutOfRange::class);
        $this->expectExceptionMessage('Exponent magnitude must not exceed 2147483647, got <2147483648>.');

        /** @When it is raised to an exponent beyond the supported magnitude */
        $number->power(exponent: 2147483648);
    }

    public static function rootProvider(): array
    {
        return [
            'Zero'     => ['value' => '0', 'root' => '0'],
            'Perfect'  => ['value' => '16', 'root' => '4'],
            'Truncate' => ['value' => '17', 'root' => '4'],
            'Large'    => ['value' => '1267650600228229401496703205376', 'root' => '1125899906842624']
        ];
    }

    public static function signProvider(): array
    {
        return [
            'Zero'     => ['value' => '0', 'isZero' => true, 'isNegative' => false, 'isPositive' => false],
            'Negative' => ['value' => '-1', 'isZero' => false, 'isNegative' => true, 'isPositive' => false],
            'Positive' => ['value' => '1', 'isZero' => false, 'isNegative' => false, 'isPositive' => true]
        ];
    }

    public static function powerProvider(): array
    {
        return [
            'Zero'     => ['value' => '0', 'exponent' => 0, 'power' => '1'],
            'Perfect'  => ['value' => '16', 'exponent' => 2, 'power' => '256'],
            'Truncate' => ['value' => '17', 'exponent' => 1, 'power' => '17'],
            'Large'    => ['value' => '2', 'exponent' => 100, 'power' => '1267650600228229401496703205376']
        ];
    }

    public static function moduloProvider(): array
    {
        return [
            'Positive by positive' => ['dividend' => '7', 'divisor' => '2', 'modulo' => '1'],
            'Negative by positive' => ['dividend' => '-7', 'divisor' => '2', 'modulo' => '1'],
            'Positive by negative' => ['dividend' => '7', 'divisor' => '-2', 'modulo' => '1'],
            'Negative by negative' => ['dividend' => '-7', 'divisor' => '-2', 'modulo' => '1'],
            'Exact division'       => ['dividend' => '8', 'divisor' => '2', 'modulo' => '0']
        ];
    }

    public static function parityProvider(): array
    {
        return [
            'Zero'           => ['value' => '0', 'expected' => true],
            'Even positive'  => ['value' => '4', 'expected' => true],
            'Odd positive'   => ['value' => '7', 'expected' => false],
            'Even negative'  => ['value' => '-4', 'expected' => true],
            'Odd negative'   => ['value' => '-3', 'expected' => false]
        ];
    }

    public static function divisorProvider(): array
    {
        return [
            'Both positive'       => ['value' => '12', 'other' => '18'],
            'Negative left'       => ['value' => '-12', 'other' => '18'],
            'Negative and larger' => ['value' => '-18', 'other' => '12'],
            'Negative right'      => ['value' => '12', 'other' => '-18'],
            'Both negative'       => ['value' => '-12', 'other' => '-18']
        ];
    }

    public static function literalProvider(): array
    {
        return [
            'Native integer'      => ['value' => 42, 'expected' => '42'],
            'Leading plus'        => ['value' => '+7', 'expected' => '7'],
            'Leading zeros'       => ['value' => '007', 'expected' => '7'],
            'Negative zero'       => ['value' => '-0', 'expected' => '0'],
            'Zero fractional'     => ['value' => '5.00', 'expected' => '5'],
            'Scientific notation' => ['value' => '1e3', 'expected' => '1000'],
            'Beyond native range' => ['value' => '92233720368547758070', 'expected' => '92233720368547758070']
        ];
    }

    public static function equalityProvider(): array
    {
        return [
            'Same value'       => ['value' => '5', 'other' => '5', 'expected' => true],
            'Same after trim'  => ['value' => '5', 'other' => '5.00', 'expected' => true],
            'Different value'  => ['value' => '5', 'other' => '6', 'expected' => false],
            'Opposite signs'   => ['value' => '5', 'other' => '-5', 'expected' => false]
        ];
    }

    public static function quotientProvider(): array
    {
        return [
            'Positive by positive' => ['dividend' => '7', 'divisor' => '2', 'quotient' => '3'],
            'Negative by positive' => ['dividend' => '-7', 'divisor' => '2', 'quotient' => '-3'],
            'Positive by negative' => ['dividend' => '7', 'divisor' => '-2', 'quotient' => '-3'],
            'Negative by negative' => ['dividend' => '-7', 'divisor' => '-2', 'quotient' => '3'],
            'Exact division'       => ['dividend' => '8', 'divisor' => '2', 'quotient' => '4']
        ];
    }

    public static function crossTypeProvider(): array
    {
        return [
            'Equal to a decimal'      => ['value' => '5', 'other' => BigDecimal::of(value: '5.00'), 'expected' => 0],
            'Less than a decimal'     => ['value' => '5', 'other' => BigDecimal::of(value: '5.01'), 'expected' => -1],
            'Greater than a fraction' => ['value' => '5', 'other' => BigRational::of(value: '1/3'), 'expected' => 1]
        ];
    }

    public static function remainderProvider(): array
    {
        return [
            'Positive by positive' => ['dividend' => '7', 'divisor' => '2', 'remainder' => '1'],
            'Negative by positive' => ['dividend' => '-7', 'divisor' => '2', 'remainder' => '-1'],
            'Positive by negative' => ['dividend' => '7', 'divisor' => '-2', 'remainder' => '1'],
            'Negative by negative' => ['dividend' => '-7', 'divisor' => '-2', 'remainder' => '-1'],
            'Exact division'       => ['dividend' => '8', 'divisor' => '2', 'remainder' => '0']
        ];
    }

    public static function comparisonProvider(): array
    {
        return [
            'Less than'              => ['value' => '9', 'other' => '10', 'expected' => -1],
            'Digits differ in count' => [
                'value'    => '99999999999999999999',
                'other'    => '100000000000000000000',
                'expected' => -1
            ],
            'Equal'        => ['value' => '10', 'other' => '10', 'expected' => 0],
            'Greater than' => ['value' => '10', 'other' => '9', 'expected' => 1]
        ];
    }

    public static function nativeRangeProvider(): array
    {
        return [
            'Largest'  => ['value' => '9223372036854775807', 'expected' => PHP_INT_MAX],
            'Smallest' => ['value' => '-9223372036854775808', 'expected' => PHP_INT_MIN],
            'Zero'     => ['value' => '0', 'expected' => 0]
        ];
    }

    public static function positionalBaseProvider(): array
    {
        return [
            'Zero in hex'       => ['base' => 16, 'text' => '0', 'value' => '0'],
            'Binary'            => ['base' => 2, 'text' => '11111111', 'value' => '255'],
            'Hexadecimal'       => ['base' => 16, 'text' => 'ff', 'value' => '255'],
            'Base thirty-six'   => ['base' => 36, 'text' => 'zz', 'value' => '1295'],
            'Negative in hex'   => ['base' => 16, 'text' => '-ff', 'value' => '-255']
        ];
    }
}
