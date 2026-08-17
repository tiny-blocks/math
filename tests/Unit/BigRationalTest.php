<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Math\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Math\BigDecimal;
use TinyBlocks\Math\BigInteger;
use TinyBlocks\Math\BigRational;
use TinyBlocks\Math\Exceptions\DivisionByZero;
use TinyBlocks\Math\Exceptions\ExponentOutOfRange;
use TinyBlocks\Math\Exceptions\InexactConversion;
use TinyBlocks\Math\Exceptions\NonTerminatingDecimal;
use TinyBlocks\Math\Exceptions\NumberNotWellFormed;
use TinyBlocks\Math\RoundingMode;

final class BigRationalTest extends TestCase
{
    public function testOneThenHoldsOne(): void
    {
        /** @Given nothing but the factory */

        /** @When one is created */
        $actual = BigRational::one();

        /** @Then it holds one */
        self::assertSame('1', $actual->toString());
    }

    public function testZeroThenHoldsZero(): void
    {
        /** @Given nothing but the factory */

        /** @When zero is created */
        $actual = BigRational::zero();

        /** @Then it holds zero */
        self::assertSame('0', $actual->toString());
    }

    public function testNegatedThenFlipsTheSign(): void
    {
        /** @Given a fraction */
        $fraction = BigRational::of(value: '3/4');

        /** @When it is negated */
        $actual = $fraction->negated();

        /** @Then the numerator carries the sign */
        self::assertSame('-3/4', $actual->toString());
    }

    #[DataProvider('arithmeticProvider')]
    public function testArithmeticThenStaysExact(
        string $left,
        string $right,
        string $sum,
        string $difference,
        string $product,
        string $quotient
    ): void {
        /** @Given two fractions and the expected exact results */

        /** @When the four operations run */
        $actual = BigRational::of(value: $left);

        /** @Then every result is exact and in lowest terms */
        self::assertSame($sum, $actual->plus(addend: BigRational::of(value: $right))->toString());
        self::assertSame($difference, $actual->minus(subtrahend: BigRational::of(value: $right))->toString());
        self::assertSame($product, $actual->multipliedBy(multiplier: BigRational::of(value: $right))->toString());
        self::assertSame($quotient, $actual->dividedBy(divisor: BigRational::of(value: $right))->toString());
    }

    public function testAbsoluteThenRemovesTheSign(): void
    {
        /** @Given a negative fraction */
        $fraction = BigRational::of(value: '-3/4');

        /** @When its magnitude is taken */
        $actual = $fraction->absolute();

        /** @Then the sign is gone */
        self::assertSame('3/4', $actual->toString());
    }

    public function testReciprocalThenSwapsTheTerms(): void
    {
        /** @Given a fraction */
        $fraction = BigRational::of(value: '2/3');

        /** @When its reciprocal is taken */
        $actual = $fraction->reciprocal();

        /** @Then the terms are swapped */
        self::assertSame('3/2', $actual->toString());
    }

    #[DataProvider('signProvider')]
    public function testSignPredicatesThenAgreeOnTheSign(
        string $value,
        bool $isZero,
        bool $isNegative,
        bool $isPositive
    ): void {
        /** @Given a fraction literal and its expected sign */

        /** @When the sign predicates are asked */
        $actual = BigRational::of(value: $value);

        /** @Then all three agree */
        self::assertSame($isZero, $actual->isZero());
        self::assertSame($isNegative, $actual->isNegative());
        self::assertSame($isPositive, $actual->isPositive());
    }

    public function testJsonSerializeThenEmitsAJsonString(): void
    {
        /** @Given a fraction */
        $fraction = BigRational::of(value: '3/4');

        /** @When it is encoded */
        $actual = json_encode($fraction);

        /** @Then it is a JSON string carrying the slash */
        self::assertSame('"3\/4"', $actual);
    }

    #[DataProvider('powerProvider')]
    public function testPowerThenAcceptsNegativeExponents(string $value, int $exponent, string $expected): void
    {
        /** @Given a fraction, an exponent, and the expected exact power */

        /** @When it is raised to that power */
        $actual = BigRational::of(value: $value)->power(exponent: $exponent);

        /** @Then the result is exact, including for a negative exponent */
        self::assertSame($expected, $actual->toString());
    }

    #[DataProvider('floatProvider')]
    public function testToFloatThenReturnsTheNearestFloat(string $value, float $expected): void
    {
        /** @Given a fraction and the float nearest to it */

        /** @When it is converted to a float */
        $actual = BigRational::of(value: $value)->toFloat();

        /** @Then the nearest float is returned */
        self::assertSame($expected, $actual);
    }

    #[DataProvider('literalProvider')]
    public function testOfThenReadsTheLiteralInLowestTerms(string|int $value, string $expected): void
    {
        /** @Given a literal and the fraction it stands for */

        /** @When a fraction is created from it */
        $actual = BigRational::of(value: $value);

        /** @Then the fraction is reduced and the sign sits on the numerator */
        self::assertSame($expected, $actual->toString());
    }

    #[DataProvider('decimalProvider')]
    public function testToDecimalThenRoundsAtTheRequestedScale(
        string $value,
        int $scale,
        RoundingMode $rounding,
        string $expected
    ): void {
        /** @Given a fraction, a target scale, and a rounding mode */

        /** @When it is converted to a decimal */
        $actual = BigRational::of(value: $value)->toDecimal(scale: $scale, rounding: $rounding);

        /** @Then the decimal is rounded as instructed */
        self::assertSame($expected, $actual->toString());
    }

    #[DataProvider('comparisonProvider')]
    public function testCompareToThenWorksAcrossEveryNumberType(string $value, string $other, int $expected): void
    {
        /** @Given a fraction literal and a decimal literal with the expected comparison */

        /** @When they are compared */
        $actual = BigRational::of(value: $value)->compareTo(other: BigDecimal::of(value: $other));

        /** @Then the comparison crosses the type boundary */
        self::assertSame($expected, $actual);
    }

    public function testToBigRationalThenReturnsTheSameInstance(): void
    {
        /** @Given a fraction */
        $fraction = BigRational::of(value: '2/3');

        /** @When it is asked for its rational form */
        $actual = $fraction->toBigRational();

        /** @Then it hands back itself */
        self::assertSame($fraction, $actual);
    }

    public function testEqualsWhenFractionsMatchThenTheyAreEqual(): void
    {
        /** @Given a fraction */
        $fraction = BigRational::of(value: '3/4');

        /** @And the same value written unreduced */
        $other = BigRational::of(value: '6/8');

        /** @When they are compared structurally */
        $actual = $fraction->equals(other: $other);

        /** @Then they are equal, because a fraction is always stored in lowest terms */
        self::assertTrue($actual);
    }

    public function testHashCodeWhenFractionsMatchThenHashesMatch(): void
    {
        /** @Given a fraction */
        $fraction = BigRational::of(value: '3/4');

        /** @And the same value written unreduced */
        $other = BigRational::of(value: '6/8');

        /** @When both hashes are taken */
        $actual = $fraction->hashCode();

        /** @Then they agree */
        self::assertSame($other->hashCode(), $actual);
    }

    #[DataProvider('exactDecimalProvider')]
    public function testToDecimalExactThenConvertsAtTheMinimalScale(string $value, string $expected): void
    {
        /** @Given a terminating fraction and the decimal it stands for */

        /** @When it is converted without rounding */
        $actual = BigRational::of(value: $value)->toDecimalExact();

        /** @Then the conversion lands at the smallest scale that represents it */
        self::assertSame($expected, $actual->toString());
    }

    public function testDividedByWhenDivisorIsZeroThenDivisionByZero(): void
    {
        /** @Given a fraction */
        $fraction = BigRational::of(value: '3/4');

        /** @Then a failure naming the dividend is raised */
        $this->expectException(DivisionByZero::class);
        $this->expectExceptionMessage('Cannot divide <3/4> by zero.');

        /** @When it is divided by zero */
        $fraction->dividedBy(divisor: BigRational::zero());
    }

    public function testReciprocalWhenFractionIsZeroThenDivisionByZero(): void
    {
        /** @Given a fraction holding zero */
        $fraction = BigRational::zero();

        /** @Then a failure describing the undefined reciprocal is raised */
        $this->expectException(DivisionByZero::class);
        $this->expectExceptionMessage('The reciprocal of zero is undefined.');

        /** @When its reciprocal is taken */
        $fraction->reciprocal();
    }

    #[DataProvider('terminatingProvider')]
    public function testTerminatingExpansionThenIsReportedForBothKinds(string $value, bool $terminates): void
    {
        /** @Given a fraction and whether its decimal expansion terminates */

        /** @When the question is asked */
        $actual = BigRational::of(value: $value)->hasTerminatingDecimal();

        /** @Then the answer matches */
        self::assertSame($terminates, $actual);
    }

    #[DataProvider('termsProvider')]
    public function testNumeratorAndDenominatorThenExposeTheReducedTerms(
        string $value,
        string $numerator,
        string $denominator
    ): void {
        /** @Given a literal with its reduced terms */

        /** @When both terms are taken */
        $actual = BigRational::of(value: $value);

        /** @Then the denominator is strictly positive and the numerator carries the sign */
        self::assertSame($numerator, $actual->numerator()->toString());
        self::assertSame($denominator, $actual->denominator()->toString());
    }

    public function testOfFractionWhenDenominatorIsZeroThenDivisionByZero(): void
    {
        /** @Given a numerator */
        $numerator = BigInteger::of(value: 3);

        /** @Then a failure naming the numerator is raised */
        $this->expectException(DivisionByZero::class);
        $this->expectExceptionMessage('Fraction <3> cannot have a denominator of zero.');

        /** @When a fraction is built over a zero denominator */
        BigRational::ofFraction(numerator: $numerator, denominator: BigInteger::zero());
    }

    public function testEqualsWhenOnlyTheNumeratorMatchesThenTheyAreNotEqual(): void
    {
        /** @Given a fraction */
        $fraction = BigRational::of(value: '1/2');

        /** @And another fraction sharing the numerator but not the denominator */
        $other = BigRational::of(value: '1/3');

        /** @When they are compared structurally */
        $actual = $fraction->equals(other: $other);

        /** @Then they are not equal, because both terms take part */
        self::assertFalse($actual);
    }

    public function testOfWhenLiteralCarriesTwoSlashesThenNumberNotWellFormed(): void
    {
        /** @Given a literal with more than one slash */
        $value = '1/2/3';

        /** @Then a failure describing the malformed literal is raised */
        $this->expectException(NumberNotWellFormed::class);

        /** @When a fraction is created from it */
        BigRational::of(value: $value);
    }

    public function testToBigIntegerWhenDenominatorIsOneThenReturnsTheNumerator(): void
    {
        /** @Given a fraction that reduces to a whole number */
        $fraction = BigRational::of(value: '8/4');

        /** @When it is converted to an integer */
        $actual = $fraction->toBigInteger();

        /** @Then it carries the numerator of the reduced fraction */
        self::assertSame('2', $actual->toString());
    }

    public function testToBigIntegerWhenDenominatorIsNotOneThenInexactConversion(): void
    {
        /** @Given a fraction that is not an integer */
        $fraction = BigRational::of(value: '1/3');

        /** @Then a failure describing the lost fractional part is raised */
        $this->expectException(InexactConversion::class);

        /** @When it is converted to an integer */
        $fraction->toBigInteger();
    }

    public function testToDecimalExactWhenExpansionRepeatsThenNonTerminatingDecimal(): void
    {
        /** @Given a fraction whose decimal expansion repeats forever */
        $fraction = BigRational::of(value: '1/3');

        /** @Then a failure describing the repeating expansion is raised */
        $this->expectException(NonTerminatingDecimal::class);
        $this->expectExceptionMessage('Fraction <1/3> has a non-terminating decimal expansion.');

        /** @When an exact decimal is demanded */
        $fraction->toDecimalExact();
    }

    public function testPowerWhenExponentIsNegativeAndFractionIsZeroThenDivisionByZero(): void
    {
        /** @Given a fraction holding zero */
        $fraction = BigRational::zero();

        /** @Then a failure describing the undefined reciprocal is raised */
        $this->expectException(DivisionByZero::class);

        /** @When it is raised to a negative power */
        $fraction->power(exponent: -2);
    }

    public function testPowerWhenExponentExceedsTheMagnitudeCeilingThenExponentOutOfRange(): void
    {
        /** @Given a fraction holding zero */
        $fraction = BigRational::zero();

        /** @Then a failure naming the rejected exponent is raised */
        $this->expectException(ExponentOutOfRange::class);
        $this->expectExceptionMessage('Exponent magnitude must not exceed 2147483647, got <-2147483648>.');

        /** @When it is raised to a negative exponent beyond the supported magnitude */
        $fraction->power(exponent: -2147483648);
    }

    public function testPowerWhenExponentIsAtTheNegativeMagnitudeLimitThenTheBoundIsCleared(): void
    {
        /** @Given a fraction holding zero */
        $fraction = BigRational::zero();

        /** @Then the reciprocal is what fails, so the exponent cleared the magnitude bound */
        $this->expectException(DivisionByZero::class);

        /** @When it is raised to the largest negative exponent the library accepts */
        $fraction->power(exponent: -2147483647);
    }

    public static function signProvider(): array
    {
        return [
            'Zero'     => ['value' => '0', 'isZero' => true, 'isNegative' => false, 'isPositive' => false],
            'Negative' => ['value' => '-1/2', 'isZero' => false, 'isNegative' => true, 'isPositive' => false],
            'Positive' => ['value' => '1/2', 'isZero' => false, 'isNegative' => false, 'isPositive' => true]
        ];
    }

    public static function floatProvider(): array
    {
        return [
            'One third'       => ['value' => '1/3', 'expected' => (1 / 3)],
            'One half'        => ['value' => '1/2', 'expected' => 0.5],
            'Negative'        => ['value' => '-1/4', 'expected' => -0.25],
            'Small'           => ['value' => '1/30000000000', 'expected' => (1 / 30000000000)],
            'Small negative'  => ['value' => '-1/30000000000', 'expected' => (-1 / 30000000000)],
            'Very small'      => ['value' => '1/1000000000000000000000', 'expected' => 1.0E-21],
            'Below a float'   => ['value' => '1/1e400', 'expected' => 0.0],
            'Digit sensitive' => ['value' => '155055128/874831071', 'expected' => 0.17724007884489051],
            'Wide integral'   => ['value' => '123456789012345678/3', 'expected' => (123456789012345678 / 3)]
        ];
    }

    public static function powerProvider(): array
    {
        return [
            'Zero exponent'     => ['value' => '2/3', 'exponent' => 0, 'expected' => '1'],
            'Zero base'         => ['value' => '0', 'exponent' => 0, 'expected' => '1'],
            'Positive exponent' => ['value' => '2/3', 'exponent' => 2, 'expected' => '4/9'],
            'Negative exponent' => ['value' => '2/3', 'exponent' => -2, 'expected' => '9/4']
        ];
    }

    public static function termsProvider(): array
    {
        return [
            'Reduced'              => ['value' => '6/8', 'numerator' => '3', 'denominator' => '4'],
            'Negative numerator'   => ['value' => '-3/4', 'numerator' => '-3', 'denominator' => '4'],
            'Negative denominator' => ['value' => '3/-4', 'numerator' => '-3', 'denominator' => '4'],
            'Zero'                 => ['value' => '0/5', 'numerator' => '0', 'denominator' => '1']
        ];
    }

    public static function decimalProvider(): array
    {
        return [
            'Repeating'     => [
                'value'    => '100/3',
                'scale'    => 2,
                'rounding' => RoundingMode::HalfEven,
                'expected' => '33.33'
            ],
            'Exact'         => [
                'value'    => '1/4',
                'scale'    => 2,
                'rounding' => RoundingMode::HalfEven,
                'expected' => '0.25'
            ],
            'Negative'      => [
                'value'    => '-1/3',
                'scale'    => 3,
                'rounding' => RoundingMode::Floor,
                'expected' => '-0.334'
            ],
            'Scale zero'    => [
                'value'    => '3/2',
                'scale'    => 0,
                'rounding' => RoundingMode::HalfEven,
                'expected' => '2'
            ],
            'Padded scale'  => [
                'value'    => '1/2',
                'scale'    => 4,
                'rounding' => RoundingMode::Down,
                'expected' => '0.5000'
            ],
            'Exact upward'  => [
                'value'    => '1/2',
                'scale'    => 1,
                'rounding' => RoundingMode::Up,
                'expected' => '0.5'
            ],
            'Negative half' => [
                'value'    => '-1/2',
                'scale'    => 0,
                'rounding' => RoundingMode::HalfUp,
                'expected' => '-1'
            ]
        ];
    }

    public static function literalProvider(): array
    {
        return [
            'Fraction'             => ['value' => '3/4', 'expected' => '3/4'],
            'Unreduced fraction'   => ['value' => '6/8', 'expected' => '3/4'],
            'Decimal'              => ['value' => '0.75', 'expected' => '3/4'],
            'Integer'              => ['value' => '3', 'expected' => '3'],
            'Native integer'       => ['value' => -3, 'expected' => '-3'],
            'Negative denominator' => ['value' => '3/-4', 'expected' => '-3/4'],
            'Whole fraction'       => ['value' => '8/4', 'expected' => '2']
        ];
    }

    public static function arithmeticProvider(): array
    {
        return [
            'Thirds and sixths' => [
                'left'       => '1/3',
                'right'      => '1/6',
                'sum'        => '1/2',
                'difference' => '1/6',
                'product'    => '1/18',
                'quotient'   => '2'
            ],
            'Negative'          => [
                'left'       => '-1/2',
                'right'      => '1/4',
                'sum'        => '-1/4',
                'difference' => '-3/4',
                'product'    => '-1/8',
                'quotient'   => '-2'
            ],
            'Whole numbers'     => [
                'left'       => '4',
                'right'      => '2',
                'sum'        => '6',
                'difference' => '2',
                'product'    => '8',
                'quotient'   => '2'
            ]
        ];
    }

    public static function comparisonProvider(): array
    {
        return [
            'Less than'    => ['value' => '1/3', 'other' => '0.5', 'expected' => -1],
            'Equal'        => ['value' => '1/2', 'other' => '0.50', 'expected' => 0],
            'Greater than' => ['value' => '2/3', 'other' => '0.5', 'expected' => 1]
        ];
    }

    public static function terminatingProvider(): array
    {
        return [
            'Halves'      => ['value' => '1/2', 'terminates' => true],
            'Thirds'      => ['value' => '1/3', 'terminates' => false],
            'Sixths'      => ['value' => '1/6', 'terminates' => false],
            'Eighths'     => ['value' => '1/8', 'terminates' => true],
            'Sevenths'    => ['value' => '1/7', 'terminates' => false],
            'Hundredths'  => ['value' => '1/100', 'terminates' => true],
            'Twentieths'  => ['value' => '1/20', 'terminates' => true],
            'Integer'     => ['value' => '3', 'terminates' => true],
            'Zero'        => ['value' => '0', 'terminates' => true]
        ];
    }

    public static function exactDecimalProvider(): array
    {
        return [
            'Halves'     => ['value' => '1/2', 'expected' => '0.5'],
            'Eighths'    => ['value' => '1/8', 'expected' => '0.125'],
            'Hundredths' => ['value' => '1/100', 'expected' => '0.01'],
            'Twentieths' => ['value' => '1/20', 'expected' => '0.05'],
            'Integer'    => ['value' => '3', 'expected' => '3'],
            'Zero'       => ['value' => '0', 'expected' => '0']
        ];
    }
}
