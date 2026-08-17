<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Math\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Math\BigDecimal;
use TinyBlocks\Math\BigDecimals;
use TinyBlocks\Math\BigInteger;
use TinyBlocks\Math\BigRational;
use TinyBlocks\Math\Exceptions\DivisionByZero;
use TinyBlocks\Math\Exceptions\ExponentOutOfRange;
use TinyBlocks\Math\Exceptions\InexactConversion;
use TinyBlocks\Math\Exceptions\IntegerOverflow;
use TinyBlocks\Math\Exceptions\NegativeExponent;
use TinyBlocks\Math\Exceptions\NegativeRoot;
use TinyBlocks\Math\Exceptions\NegativeWeight;
use TinyBlocks\Math\Exceptions\NumberNotWellFormed;
use TinyBlocks\Math\Exceptions\ScaleOutOfRange;
use TinyBlocks\Math\Number;
use TinyBlocks\Math\RoundingMode;

final class BigDecimalTest extends TestCase
{
    public function testNegatedThenKeepsTheScale(): void
    {
        /** @Given a decimal with two fractional digits */
        $amount = BigDecimal::of(value: '1.50');

        /** @When it is negated */
        $actual = $amount->negated();

        /** @Then the sign flips and the scale is untouched */
        self::assertSame('-1.50', $actual->toString());
    }

    public function testAbsoluteThenKeepsTheScale(): void
    {
        /** @Given a negative decimal */
        $amount = BigDecimal::of(value: '-1.50');

        /** @When its magnitude is taken */
        $actual = $amount->absolute();

        /** @Then the sign is gone and the scale is untouched */
        self::assertSame('1.50', $actual->toString());
    }

    public function testOneThenHoldsOneAtScaleZero(): void
    {
        /** @Given nothing but the factory */

        /** @When one is created */
        $actual = BigDecimal::one();

        /** @Then it holds one with no fractional digits */
        self::assertSame('1', $actual->toString());
    }

    public function testZeroThenHoldsZeroAtScaleZero(): void
    {
        /** @Given nothing but the factory */

        /** @When zero is created */
        $actual = BigDecimal::zero();

        /** @Then it holds zero with no fractional digits */
        self::assertSame('0', $actual->toString());
    }

    #[DataProvider('literalProvider')]
    public function testOfThenReadsTheLiteralAndItsScale(string|int $value, string $expected, int $scale): void
    {
        /** @Given a decimal literal with the value and scale it stands for */

        /** @When a decimal is created from it */
        $actual = BigDecimal::of(value: $value);

        /** @Then both the value and the scale are read exactly */
        self::assertSame($expected, $actual->toString());
        self::assertSame($scale, $actual->scale());
    }

    public function testJsonSerializeThenEmitsAJsonString(): void
    {
        /** @Given a decimal whose trailing zero carries meaning */
        $amount = BigDecimal::of(value: '1.50');

        /** @When it is encoded */
        $actual = json_encode($amount);

        /** @Then it is a JSON string so the trailing zero survives */
        self::assertSame('"1.50"', $actual);
    }

    #[DataProvider('roundingProvider')]
    public function testToScaleThenAppliesTheRoundingMode(
        string $value,
        int $scale,
        RoundingMode $rounding,
        string $expected
    ): void {
        /** @Given a decimal, a target scale, and a rounding mode */

        /** @When it is taken to that scale */
        $actual = BigDecimal::of(value: $value)->toScale(scale: $scale, rounding: $rounding);

        /** @Then the discarded digits are resolved by the mode */
        self::assertSame($expected, $actual->toString());
    }

    public function testAllocateThenPartsSumBackToTheAmount(): void
    {
        /** @Given an amount that does not divide evenly */
        $amount = BigDecimal::of(value: '100.00');

        /** @And three equal weights */
        $weights = BigDecimals::of('1', '1', '1');

        /** @When the amount is allocated */
        $actual = $amount->allocate(scale: 2, weights: $weights);

        /** @Then the parts sum back to the amount with no unit lost */
        self::assertSame('100.00', $actual->sum()->toString());
    }

    public function testDividedByThenReturnsAnExactFraction(): void
    {
        /** @Given an amount */
        $amount = BigDecimal::of(value: '100.00');

        /** @And a divisor whose quotient repeats forever */
        $divisor = BigDecimal::of(value: '3');

        /** @When it is divided */
        $actual = $amount->dividedBy(divisor: $divisor);

        /** @Then the exact fraction is returned instead of a rounded decimal */
        self::assertSame('100/3', $actual->toString());
    }

    #[DataProvider('equalityProvider')]
    public function testEqualsAndIsEqualToThenDisagreeOnScale(
        string $value,
        string $other,
        bool $structural,
        bool $arithmetic
    ): void {
        /** @Given two decimal literals with their structural and arithmetic verdicts */

        /** @When both notions of equality are asked */
        $actual = BigDecimal::of(value: $value);

        /** @Then structural equality sees the scale and arithmetic equality does not */
        self::assertSame($structural, $actual->equals(other: BigDecimal::of(value: $other)));
        self::assertSame($arithmetic, $actual->isEqualTo(other: BigDecimal::of(value: $other)));
    }

    public function testToBigRationalThenReducesToLowestTerms(): void
    {
        /** @Given a decimal */
        $amount = BigDecimal::of(value: '0.75');

        /** @When it is converted to a fraction */
        $actual = $amount->toBigRational();

        /** @Then the fraction is in lowest terms */
        self::assertSame('3/4', $actual->toString());
    }

    public function testUnscaledValueThenDropsTheDecimalPoint(): void
    {
        /** @Given a decimal with two fractional digits */
        $amount = BigDecimal::of(value: '19.99');

        /** @When its unscaled digits are taken */
        $actual = $amount->unscaledValue();

        /** @Then the decimal point is gone */
        self::assertSame('1999', $actual->toString());
    }

    #[DataProvider('shortestRoundTripProvider')]
    public function testFromFloatThenReadsTheShortestRoundTrip(float $value, string $expected): void
    {
        /** @Given a float and the shortest decimal that casts back to it */

        /** @When a decimal is created from it */
        $actual = BigDecimal::fromFloat(value: $value);

        /** @Then the shortest decimal that round-trips is used */
        self::assertSame($expected, $actual->toString());
    }

    #[DataProvider('squareRootProvider')]
    public function testSquareRootThenRoundsAtTheRequestedScale(
        string $value,
        int $scale,
        RoundingMode $rounding,
        string $expected
    ): void {
        /** @Given a radicand, a target scale, and a rounding mode */

        /** @When its square root is taken */
        $actual = BigDecimal::of(value: $value)->squareRoot(scale: $scale, rounding: $rounding);

        /** @Then the root is correct at that scale */
        self::assertSame($expected, $actual->toString());
    }

    public function testHashCodeWhenScalesDifferThenHashesDiffer(): void
    {
        /** @Given a decimal at scale one */
        $amount = BigDecimal::of(value: '1.0');

        /** @And the same value at scale two */
        $other = BigDecimal::of(value: '1.00');

        /** @When both hashes are taken */
        $actual = $amount->hashCode();

        /** @Then they differ, because the hash agrees with structural equality */
        self::assertNotSame($other->hashCode(), $actual);
    }

    public function testOfUnscaledValueThenPlacesTheDecimalPoint(): void
    {
        /** @Given unscaled digits */
        $unscaled = BigInteger::of(value: 1999);

        /** @When a decimal is built at scale two */
        $actual = BigDecimal::ofUnscaledValue(scale: 2, unscaled: $unscaled);

        /** @Then the point sits two digits from the right */
        self::assertSame('19.99', $actual->toString());
    }

    #[DataProvider('powerProvider')]
    public function testPowerThenMultipliesTheScaleByTheExponent(string $value, int $exponent, string $expected): void
    {
        /** @Given a decimal, an exponent, and the expected exact power */

        /** @When it is raised to that power */
        $actual = BigDecimal::of(value: $value)->power(exponent: $exponent);

        /** @Then the result is exact and its scale is the scale times the exponent */
        self::assertSame($expected, $actual->toString());
    }

    #[DataProvider('arithmeticProvider')]
    public function testArithmeticThenPropagatesScaleAsDocumented(
        string $left,
        string $right,
        string $sum,
        string $difference,
        string $product
    ): void {
        /** @Given two decimal literals and the expected exact results */

        /** @When the three exact operations run */
        $actual = BigDecimal::of(value: $left);

        /** @Then addition and subtraction keep the larger scale and multiplication sums them */
        self::assertSame($sum, $actual->plus(addend: BigDecimal::of(value: $right))->toString());
        self::assertSame($difference, $actual->minus(subtrahend: BigDecimal::of(value: $right))->toString());
        self::assertSame($product, $actual->multipliedBy(multiplier: BigDecimal::of(value: $right))->toString());
    }

    public function testAllocateWhenAWeightIsZeroThenThatPartIsZero(): void
    {
        /** @Given an amount */
        $amount = BigDecimal::of(value: '10.00');

        /** @And a weight of zero beside a positive one */
        $weights = BigDecimals::of('1', '0');

        /** @When the amount is allocated */
        $actual = $amount->allocate(scale: 2, weights: $weights);

        /** @Then the zero weight receives nothing */
        self::assertSame(
            ['10.00', '0.00'],
            array_map(static fn(BigDecimal $part): string => $part->toString(), $actual->all())
        );
    }

    public function testDividedByWhenDivisorIsZeroThenDivisionByZero(): void
    {
        /** @Given a decimal */
        $amount = BigDecimal::of(value: '1.00');

        /** @Then a failure naming the dividend is raised */
        $this->expectException(DivisionByZero::class);
        $this->expectExceptionMessage('Cannot divide <1> by zero.');

        /** @When it is divided by zero */
        $amount->dividedBy(divisor: BigDecimal::zero());
    }

    public function testSquareRootWhenValueIsNegativeThenNegativeRoot(): void
    {
        /** @Given a negative decimal */
        $amount = BigDecimal::of(value: '-1.00');

        /** @Then a failure naming the decimal, not its unscaled digits, is raised */
        $this->expectException(NegativeRoot::class);
        $this->expectExceptionMessage('Cannot take the square root of the negative value <-1.00>.');

        /** @When its square root is taken */
        $amount->squareRoot(scale: 2, rounding: RoundingMode::HalfEven);
    }

    public function testToScaleWhenScaleIsNegativeThenScaleOutOfRange(): void
    {
        /** @Given a decimal */
        $amount = BigDecimal::of(value: '1.00');

        /** @Then a failure describing the scale bounds is raised */
        $this->expectException(ScaleOutOfRange::class);
        $this->expectExceptionMessage('Scale must be between 0 and 10000, got <-1>.');

        /** @When it is taken to a negative scale */
        $amount->toScale(scale: -1, rounding: RoundingMode::Down);
    }

    public function testAllocateWhenWeightsSumToZeroThenDivisionByZero(): void
    {
        /** @Given an amount */
        $amount = BigDecimal::of(value: '10.00');

        /** @And weights that all hold zero */
        $weights = BigDecimals::of('0', '0');

        /** @Then a failure describing the empty total is raised */
        $this->expectException(DivisionByZero::class);
        $this->expectExceptionMessage('Allocation weights must not sum to zero.');

        /** @When the amount is allocated */
        $amount->allocate(scale: 2, weights: $weights);
    }

    public function testPowerWhenScaleWouldOverflowThenScaleOutOfRange(): void
    {
        /** @Given a decimal carrying two fractional digits */
        $amount = BigDecimal::of(value: '1.50');

        /** @Then a failure naming the scale the power would need is raised */
        $this->expectException(ScaleOutOfRange::class);
        $this->expectExceptionMessage('Scale must be between 0 and 10000, got <4294967294>.');

        /** @When it is raised to a power whose scale exceeds the supported range */
        $amount->power(exponent: 2147483647);
    }

    public function testAllocateWhenAWeightIsNegativeThenNegativeWeight(): void
    {
        /** @Given an amount */
        $amount = BigDecimal::of(value: '10.00');

        /** @And a negative weight */
        $weights = BigDecimals::of('-1', '1');

        /** @Then a failure naming the rejected weight is raised */
        $this->expectException(NegativeWeight::class);
        $this->expectExceptionMessage('Allocation weights must not be negative, got <-1>.');

        /** @When the amount is allocated */
        $amount->allocate(scale: 2, weights: $weights);
    }

    #[DataProvider('malformedLiteralProvider')]
    public function testOfWhenLiteralIsMalformedThenNumberNotWellFormed(string $value): void
    {
        /** @Given a literal that is not a number */

        /** @Then a failure describing the malformed literal is raised */
        $this->expectException(NumberNotWellFormed::class);

        /** @When a decimal is created from it */
        BigDecimal::of(value: $value);
    }

    public function testPowerWhenExponentIsNegativeThenNegativeExponent(): void
    {
        /** @Given a decimal */
        $amount = BigDecimal::of(value: '1.5');

        /** @Then a failure describing the negative exponent is raised */
        $this->expectException(NegativeExponent::class);

        /** @When it is raised to a negative power */
        $amount->power(exponent: -1);
    }

    #[DataProvider('allocationProvider')]
    public function testAllocateThenHandsLeftoversToTheLargestRemainders(
        string $amount,
        BigDecimals $weights,
        array $expected
    ): void {
        /** @Given an amount, its weights, and the expected parts */

        /** @When the amount is allocated at scale two */
        $actual = BigDecimal::of(value: $amount);

        /** @Then the parts match, in the order of the weights */
        self::assertSame(
            $expected,
            array_map(
                static fn(BigDecimal $part): string => $part->toString(),
                $actual->allocate(scale: 2, weights: $weights)->all()
            )
        );
    }

    #[DataProvider('crossTypeProvider')]
    public function testCompareToWhenTheOtherIsAnotherTypeThenStaysExact(
        string $value,
        Number $other,
        int $expected
    ): void {
        /** @Given a decimal and a number of another type with the expected comparison */

        /** @When they are compared */
        $actual = BigDecimal::of(value: $value)->compareTo(other: $other);

        /** @Then the comparison crosses the type boundary */
        self::assertSame($expected, $actual);
    }

    #[DataProvider('partsProvider')]
    public function testIntegralAndFractionalPartsThenReconstructTheValue(
        string $value,
        string $integral,
        string $fractional
    ): void {
        /** @Given a decimal literal with its expected parts */

        /** @When both parts are taken */
        $actual = BigDecimal::of(value: $value);

        /** @Then each part matches and the integral part truncates toward zero */
        self::assertSame($integral, $actual->integralPart()->toString());
        self::assertSame($fractional, $actual->fractionalPart()->toString());
    }

    #[DataProvider('trailingZeroProvider')]
    public function testWithoutTrailingZerosThenReducesToTheSmallestScale(string $value, string $expected): void
    {
        /** @Given a decimal literal and its smallest representation */

        /** @When trailing zeros are dropped */
        $actual = BigDecimal::of(value: $value)->withoutTrailingZeros();

        /** @Then the value is unchanged and the scale is minimal */
        self::assertSame($expected, $actual->toString());
    }

    public function testFractionalPartWhenValueIsNegativeThenCarriesTheSign(): void
    {
        /** @Given a negative decimal */
        $amount = BigDecimal::of(value: '-19.99');

        /** @When its fractional part is taken */
        $actual = $amount->fractionalPart();

        /** @Then the sign follows the value */
        self::assertSame('-0.99', $actual->toString());
    }

    public function testFromFloatWhenValueIsInfiniteThenNumberNotWellFormed(): void
    {
        /** @Given an infinite float */
        $value = INF;

        /** @Then a failure describing the malformed literal is raised */
        $this->expectException(NumberNotWellFormed::class);

        /** @When a decimal is created from it */
        BigDecimal::fromFloat(value: $value);
    }

    public function testFromFloatWhenValueIsNotFiniteThenNumberNotWellFormed(): void
    {
        /** @Given a float that is not a number */
        $value = NAN;

        /** @Then a failure describing the malformed literal is raised */
        $this->expectException(NumberNotWellFormed::class);

        /** @When a decimal is created from it */
        BigDecimal::fromFloat(value: $value);
    }

    public function testFromFloatWhenAdditionLostPrecisionThenTheLossIsVisible(): void
    {
        /** @Given the classic float addition that is not exact */
        $value = (0.1 + 0.2);

        /** @When a decimal is created from it */
        $actual = BigDecimal::fromFloat(value: $value);

        /** @Then the library shows the loss rather than hiding it */
        self::assertSame('0.30000000000000004', $actual->toString());
    }

    public function testToScaleExactWhenDigitsWouldBeLostThenInexactConversion(): void
    {
        /** @Given a decimal with three fractional digits */
        $amount = BigDecimal::of(value: '1.234');

        /** @Then a failure describing the discarded digits is raised */
        $this->expectException(InexactConversion::class);
        $this->expectExceptionMessage('Value <1.234> cannot be represented at scale <2> without discarding digits.');

        /** @When it is taken to scale two without rounding */
        $amount->toScaleExact(scale: 2);
    }

    public function testAllocateWhenAmountIsNegativeThenPartsSumBackToTheAmount(): void
    {
        /** @Given a negative amount that does not divide evenly */
        $amount = BigDecimal::of(value: '-100.00');

        /** @And three equal weights */
        $weights = BigDecimals::of('1', '1', '1');

        /** @When the amount is allocated */
        $actual = $amount->allocate(scale: 2, weights: $weights);

        /** @Then the parts still sum back to the amount */
        self::assertSame('-100.00', $actual->sum()->toString());
    }

    public function testToFloatWhenValueExceedsTheFloatRangeThenIntegerOverflow(): void
    {
        /** @Given a decimal beyond the largest representable float */
        $amount = BigDecimal::of(value: '1e400');

        /** @Then a failure describing the overflow is raised */
        $this->expectException(IntegerOverflow::class);

        /** @When it is converted to a float */
        $amount->toFloat();
    }

    public function testOfWhenExponentIsAtTheNegativeLimitThenTheScaleIsAccepted(): void
    {
        /** @Given a literal carrying the smallest exponent the library expands */
        $value = '1e-10000';

        /** @When a decimal is created from it */
        $actual = BigDecimal::of(value: $value)->scale();

        /** @Then it is accepted and the exponent became the scale */
        self::assertSame(10000, $actual);
    }

    public function testAllocateWhenAmountDoesNotFitTheScaleThenInexactConversion(): void
    {
        /** @Given an amount with more digits than the allocation scale */
        $amount = BigDecimal::of(value: '10.005');

        /** @And a single weight */
        $weights = BigDecimals::of('1');

        /** @Then a failure describing the discarded digits is raised */
        $this->expectException(InexactConversion::class);

        /** @When the amount is allocated */
        $amount->allocate(scale: 2, weights: $weights);
    }

    public function testOfWhenExponentIsAtTheSupportedLimitThenTheLiteralIsAccepted(): void
    {
        /** @Given a literal carrying the largest exponent the library expands */
        $value = '1e10000';

        /** @When a decimal is created from it */
        $actual = BigDecimal::of(value: $value);

        /** @Then it is accepted and carries every digit */
        self::assertSame(10001, strlen($actual->toString()));
    }

    public function testToBigIntegerWhenFractionalPartIsNotZeroThenInexactConversion(): void
    {
        /** @Given a decimal with a non-zero fractional part */
        $amount = BigDecimal::of(value: '1.5');

        /** @Then a failure describing the lost fractional part is raised */
        $this->expectException(InexactConversion::class);

        /** @When it is converted to an integer */
        $amount->toBigInteger();
    }

    public function testOfUnscaledValueWhenScaleIsTheLargestSupportedThenItIsAccepted(): void
    {
        /** @Given the largest scale the library supports */
        $scale = 10000;

        /** @When a decimal is built at that scale */
        $actual = BigDecimal::ofUnscaledValue(scale: $scale, unscaled: BigInteger::one())->scale();

        /** @Then the boundary itself is accepted */
        self::assertSame($scale, $actual);
    }

    public function testPowerWhenExponentExceedsTheMagnitudeCeilingThenExponentOutOfRange(): void
    {
        /** @Given a decimal carrying a fractional digit */
        $amount = BigDecimal::of(value: '1.5');

        /** @Then a failure naming the rejected exponent is raised */
        $this->expectException(ExponentOutOfRange::class);
        $this->expectExceptionMessage('Exponent magnitude must not exceed 2147483647, got <2147483648>.');

        /** @When it is raised to an exponent beyond the supported magnitude */
        $amount->power(exponent: 2147483648);
    }

    public function testPowerWhenExponentWouldOverflowTheScaleProductThenExponentOutOfRange(): void
    {
        /** @Given a decimal carrying two fractional digits */
        $amount = BigDecimal::of(value: '1.50');

        /** @Then a failure naming the rejected exponent is raised */
        $this->expectException(ExponentOutOfRange::class);
        $this->expectExceptionMessage('Exponent magnitude must not exceed 2147483647, got <9223372036854775807>.');

        /** @When it is raised to an exponent whose product with the scale would overflow */
        $amount->power(exponent: PHP_INT_MAX);
    }

    public static function partsProvider(): array
    {
        return [
            'Positive'     => ['value' => '19.99', 'integral' => '19', 'fractional' => '0.99'],
            'Negative'     => ['value' => '-19.99', 'integral' => '-19', 'fractional' => '-0.99'],
            'No fraction'  => ['value' => '19', 'integral' => '19', 'fractional' => '0'],
            'Below one'    => ['value' => '0.99', 'integral' => '0', 'fractional' => '0.99']
        ];
    }

    public static function powerProvider(): array
    {
        return [
            'Zero exponent' => ['value' => '1.5', 'exponent' => 0, 'expected' => '1'],
            'Identity'      => ['value' => '1.50', 'exponent' => 1, 'expected' => '1.50'],
            'Cube'          => ['value' => '1.5', 'exponent' => 3, 'expected' => '3.375'],
            'Negative base' => ['value' => '-1.5', 'exponent' => 2, 'expected' => '2.25']
        ];
    }

    public static function literalProvider(): array
    {
        return [
            'Native integer'      => ['value' => 42, 'expected' => '42', 'scale' => 0],
            'Trailing zero kept'  => ['value' => '1.50', 'expected' => '1.50', 'scale' => 2],
            'Leading point'       => ['value' => '.5', 'expected' => '0.5', 'scale' => 1],
            'Leading plus'        => ['value' => '+5', 'expected' => '5', 'scale' => 0],
            'Leading zeros'       => ['value' => '007.500', 'expected' => '7.500', 'scale' => 3],
            'Negative zero'       => ['value' => '-0', 'expected' => '0', 'scale' => 0],
            'Zero with scale'     => ['value' => '0.000', 'expected' => '0.000', 'scale' => 3],
            'Positive exponent'   => ['value' => '1e3', 'expected' => '1000', 'scale' => 0],
            'Negative exponent'   => ['value' => '1.5E-3', 'expected' => '0.0015', 'scale' => 4],
            'Trailing point'      => ['value' => '5.', 'expected' => '5', 'scale' => 0]
        ];
    }

    public static function equalityProvider(): array
    {
        return [
            'Same scale'              => [
                'value'      => '1.0',
                'other'      => '1.0',
                'structural' => true,
                'arithmetic' => true
            ],
            'Different scale'         => [
                'value'      => '1.0',
                'other'      => '1.00',
                'structural' => false,
                'arithmetic' => true
            ],
            'Different value'         => [
                'value'      => '1.0',
                'other'      => '2.0',
                'structural' => false,
                'arithmetic' => false
            ],
            'Past the float mantissa' => [
                'value'      => '0.10000000000000000001',
                'other'      => '0.10000000000000000002',
                'structural' => false,
                'arithmetic' => false
            ]
        ];
    }

    public static function roundingProvider(): array
    {
        return [
            'Truncating up'    => [
                'value'    => '1.23456',
                'scale'    => 2,
                'rounding' => RoundingMode::HalfEven,
                'expected' => '1.23'
            ],
            'Half away'        => [
                'value'    => '0.995',
                'scale'    => 2,
                'rounding' => RoundingMode::HalfUp,
                'expected' => '1.00'
            ],
            'Half toward'      => [
                'value'    => '0.995',
                'scale'    => 2,
                'rounding' => RoundingMode::HalfDown,
                'expected' => '0.99'
            ],
            'Widening'         => [
                'value'    => '1.2',
                'scale'    => 4,
                'rounding' => RoundingMode::Down,
                'expected' => '1.2000'
            ],
            'Nothing to lose'  => [
                'value'    => '1.00',
                'scale'    => 0,
                'rounding' => RoundingMode::Up,
                'expected' => '1'
            ],
            'Everything lost'  => [
                'value'    => '0.5',
                'scale'    => 0,
                'rounding' => RoundingMode::HalfUp,
                'expected' => '1'
            ],
            'Discarded wider than the digits' => [
                'value'    => '0.0045',
                'scale'    => 0,
                'rounding' => RoundingMode::HalfUp,
                'expected' => '0'
            ],
            'Everything lost negative' => [
                'value'    => '-0.5',
                'scale'    => 0,
                'rounding' => RoundingMode::HalfUp,
                'expected' => '-1'
            ],
            'Beyond the float' => [
                'value'    => '12345678901234567890.5',
                'scale'    => 0,
                'rounding' => RoundingMode::HalfEven,
                'expected' => '12345678901234567890'
            ],
            'Exact already'    => [
                'value'    => '1.25',
                'scale'    => 2,
                'rounding' => RoundingMode::Up,
                'expected' => '1.25'
            ],
            'Toward zero'      => [
                'value'    => '-0.4',
                'scale'    => 0,
                'rounding' => RoundingMode::Down,
                'expected' => '0'
            ],
            'Above half'       => [
                'value'    => '1.226',
                'scale'    => 2,
                'rounding' => RoundingMode::HalfEven,
                'expected' => '1.23'
            ],
            'Below half'       => [
                'value'    => '1.234',
                'scale'    => 2,
                'rounding' => RoundingMode::HalfEven,
                'expected' => '1.23'
            ]
        ];
    }

    public static function crossTypeProvider(): array
    {
        return [
            'Equal to a fraction'     => ['value' => '1.50', 'other' => BigRational::of(value: '3/2'), 'expected' => 0],
            'Equal to an integer'     => ['value' => '2.00', 'other' => BigInteger::of(value: 2), 'expected' => 0],
            'Less than an integer'    => ['value' => '1.50', 'other' => BigInteger::of(value: 2), 'expected' => -1],
            'Greater than a fraction' => ['value' => '1.50', 'other' => BigRational::of(value: '1/3'), 'expected' => 1]
        ];
    }

    public static function allocationProvider(): array
    {
        return [
            'Three equal parts'   => [
                'amount'   => '100.00',
                'weights'  => BigDecimals::of('1', '1', '1'),
                'expected' => ['33.34', '33.33', '33.33']
            ],
            'Uneven weights'      => [
                'amount'   => '0.05',
                'weights'  => BigDecimals::of('3', '7'),
                'expected' => ['0.02', '0.03']
            ],
            'Fractional weights'  => [
                'amount'   => '10.00',
                'weights'  => BigDecimals::of('1.5', '2.5'),
                'expected' => ['3.75', '6.25']
            ],
            'Negative amount'     => [
                'amount'   => '-100.00',
                'weights'  => BigDecimals::of('1', '1', '1'),
                'expected' => ['-33.33', '-33.33', '-33.34']
            ],
            'Single weight'       => [
                'amount'   => '100.00',
                'weights'  => BigDecimals::of('1'),
                'expected' => ['100.00']
            ],
            'Exact split'         => [
                'amount'   => '100.00',
                'weights'  => BigDecimals::of('1', '1'),
                'expected' => ['50.00', '50.00']
            ],
            'Remainder ordering'  => [
                'amount'   => '0.10',
                'weights'  => BigDecimals::of('1', '2'),
                'expected' => ['0.03', '0.07']
            ]
        ];
    }

    public static function arithmeticProvider(): array
    {
        return [
            'Different scales' => [
                'left'       => '1.50',
                'right'      => '2.250',
                'sum'        => '3.750',
                'difference' => '-0.750',
                'product'    => '3.37500'
            ],
            'Same scale'       => [
                'left'       => '19.99',
                'right'      => '5.10',
                'sum'        => '25.09',
                'difference' => '14.89',
                'product'    => '101.9490'
            ],
            'Negative'         => [
                'left'       => '-1.5',
                'right'      => '2.5',
                'sum'        => '1.0',
                'difference' => '-4.0',
                'product'    => '-3.75'
            ],
            'Zero'             => [
                'left'       => '0.00',
                'right'      => '0.0',
                'sum'        => '0.00',
                'difference' => '0.00',
                'product'    => '0.000'
            ]
        ];
    }

    public static function squareRootProvider(): array
    {
        return [
            'Irrational'       => [
                'value'    => '2',
                'scale'    => 10,
                'rounding' => RoundingMode::HalfEven,
                'expected' => '1.4142135624'
            ],
            'Exact root'       => [
                'value'    => '2.25',
                'scale'    => 1,
                'rounding' => RoundingMode::HalfEven,
                'expected' => '1.5'
            ],
            'Zero'             => [
                'value'    => '0',
                'scale'    => 2,
                'rounding' => RoundingMode::HalfEven,
                'expected' => '0.00'
            ],
            'Truncated'        => [
                'value'    => '17',
                'scale'    => 0,
                'rounding' => RoundingMode::Down,
                'expected' => '4'
            ],
            'Rounded up'       => [
                'value'    => '17',
                'scale'    => 0,
                'rounding' => RoundingMode::Up,
                'expected' => '5'
            ],
            'Exact under up'   => [
                'value'    => '2.25',
                'scale'    => 1,
                'rounding' => RoundingMode::Up,
                'expected' => '1.5'
            ],
            'Scale below half' => [
                'value'    => '0.0001',
                'scale'    => 1,
                'rounding' => RoundingMode::HalfEven,
                'expected' => '0.0'
            ]
        ];
    }

    public static function trailingZeroProvider(): array
    {
        return [
            'Some zeros' => ['value' => '1.200', 'expected' => '1.2'],
            'All zeros'  => ['value' => '1.000', 'expected' => '1'],
            'Integral'   => ['value' => '100', 'expected' => '100'],
            'Zero'       => ['value' => '0.000', 'expected' => '0'],
            'Negative'   => ['value' => '-1.500', 'expected' => '-1.5']
        ];
    }

    public static function malformedLiteralProvider(): array
    {
        return [
            'Empty'           => ['value' => ''],
            'Sign only'       => ['value' => '+'],
            'Point only'      => ['value' => '.'],
            'Letters'         => ['value' => 'abc'],
            'Dangling e'      => ['value' => '1e'],
            'Underscores'     => ['value' => '1_000'],
            'Fraction'        => ['value' => '1/2'],
            'Two points'      => ['value' => '1.2.3'],
            'Leading space'   => ['value' => ' 1'],
            'Huge exponent'   => ['value' => '1e10001'],
            'Tiny exponent'   => ['value' => '1e-10001']
        ];
    }

    public static function shortestRoundTripProvider(): array
    {
        return [
            'Whole'         => ['value' => 5.0, 'expected' => '5'],
            'One tenth'     => ['value' => 0.1, 'expected' => '0.1'],
            'One third'     => ['value' => (1 / 3), 'expected' => '0.3333333333333333'],
            'Negative zero' => ['value' => -0.0, 'expected' => '0'],
            'Inexact sum'   => ['value' => (0.1 + 0.2), 'expected' => '0.30000000000000004']
        ];
    }
}
