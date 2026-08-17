<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Math\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Math\BigDecimal;
use TinyBlocks\Math\Exceptions\NumberNotWellFormed;
use TinyBlocks\Math\Percentage;
use TinyBlocks\Math\Ratio;
use TinyBlocks\Math\RoundingMode;

final class PercentageTest extends TestCase
{
    public function testZeroThenHoldsZero(): void
    {
        /** @Given nothing but the factory */

        /** @When zero is created */
        $actual = Percentage::zero();

        /** @Then it holds zero percent */
        self::assertSame('0%', $actual->toString());
    }

    #[DataProvider('applicationProvider')]
    public function testApplyToThenStaysExact(
        string $value,
        string $amount,
        string $applied,
        string $increased,
        string $decreased
    ): void {
        /** @Given a percentage, an amount, and the three expected exact results */

        /** @When the percentage is applied to the amount */
        $actual = Percentage::of(value: $value);

        /** @Then every result is exact, with no rounding decision forced on the caller */
        self::assertSame($applied, $actual->applyTo(amount: BigDecimal::of(value: $amount))->toString());
        self::assertSame($increased, $actual->increase(amount: BigDecimal::of(value: $amount))->toString());
        self::assertSame($decreased, $actual->decrease(amount: BigDecimal::of(value: $amount))->toString());
    }

    public function testToRatioThenReducesToLowestTerms(): void
    {
        /** @Given a quarter expressed as a percentage */
        $percentage = Percentage::of(value: '25');

        /** @When it is converted to a ratio */
        $actual = $percentage->toRatio();

        /** @Then the ratio is in lowest terms */
        self::assertSame('1:4', $actual->toString());
    }

    #[DataProvider('signProvider')]
    public function testSignPredicatesThenAgreeOnTheSign(
        string $value,
        bool $isZero,
        bool $isNegative,
        bool $isPositive
    ): void {
        /** @Given a percentage literal and its expected sign */

        /** @When the sign predicates are asked */
        $actual = Percentage::of(value: $value);

        /** @Then all three agree, and a rate above one hundred stays legal */
        self::assertSame($isZero, $actual->isZero());
        self::assertSame($isNegative, $actual->isNegative());
        self::assertSame($isPositive, $actual->isPositive());
    }

    #[DataProvider('rateProvider')]
    public function testRateThenDividesTheValueByOneHundred(string|int $value, string $expected): void
    {
        /** @Given a percentage literal and the factor it multiplies by */

        /** @When its rate is taken */
        $actual = Percentage::of(value: $value)->rate();

        /** @Then the factor carries two more fractional digits */
        self::assertSame($expected, $actual->toString());
    }

    #[DataProvider('comparisonProvider')]
    public function testComparisonPredicatesThenIgnoreTheScale(string $value, string $other, int $expected): void
    {
        /** @Given a percentage literal */
        $rate = Percentage::of(value: $value);

        /** @And another to compare it against */
        $against = Percentage::of(value: $other);

        /** @When they are compared for equality */
        $actual = $rate->isEqualTo(other: $against);

        /** @Then every predicate agrees, and the scale plays no part */
        self::assertSame($expected === 0, $actual);
        self::assertSame($expected < 0, $rate->isLessThan(other: $against));
        self::assertSame($expected > 0, $rate->isGreaterThan(other: $against));
        self::assertSame($expected <= 0, $rate->isLessThanOrEqualTo(other: $against));
        self::assertSame($expected >= 0, $rate->isGreaterThanOrEqualTo(other: $against));
    }

    public function testEqualsWhenScalesDifferThenTheyAreNotEqual(): void
    {
        /** @Given a percentage at scale zero */
        $percentage = Percentage::of(value: '10');

        /** @And the same rate at scale one */
        $other = Percentage::of(value: '10.0');

        /** @When they are compared structurally */
        $actual = $percentage->equals(other: $other);

        /** @Then they are not equal, because the scale is part of the representation */
        self::assertFalse($actual);
    }

    public function testHashCodeWhenPercentagesMatchThenHashesMatch(): void
    {
        /** @Given a percentage */
        $percentage = Percentage::of(value: '12.5');

        /** @And another percentage holding the same rate and scale */
        $other = Percentage::of(value: '12.5');

        /** @When both hashes are taken */
        $actual = $percentage->hashCode();

        /** @Then they agree */
        self::assertSame($other->hashCode(), $actual);
    }

    public function testEqualsWhenValueAndScaleMatchThenTheyAreEqual(): void
    {
        /** @Given a percentage */
        $percentage = Percentage::of(value: '12.5');

        /** @And another percentage holding the same value and scale */
        $other = Percentage::of(value: '12.5');

        /** @When they are compared structurally */
        $actual = $percentage->equals(other: $other);

        /** @Then they are equal */
        self::assertTrue($actual);
    }

    public function testFromRatioThenExpressesTheProportionPerHundred(): void
    {
        /** @Given a proportion of one to four */
        $ratio = Ratio::of(antecedent: 1, consequent: 4);

        /** @When it is expressed as a percentage */
        $actual = Percentage::fromRatio(ratio: $ratio, scale: 0, rounding: RoundingMode::HalfEven);

        /** @Then it reads as twenty-five percent */
        self::assertSame('25%', $actual->toString());
    }

    public function testJsonSerializeThenEmitsAJsonStringThatReadsBack(): void
    {
        /** @Given a percentage */
        $percentage = Percentage::of(value: '12.5');

        /** @When it is encoded */
        $actual = json_encode($percentage);

        /** @Then it is a JSON string carrying the percent sign, and it round-trips */
        self::assertSame('"12.5%"', $actual);
        self::assertTrue(Percentage::of(value: json_decode((string)$actual))->equals(other: $percentage));
    }

    public function testOfWhenLiteralIsMalformedThenNumberNotWellFormed(): void
    {
        /** @Given a literal that is not a number */
        $value = 'twelve';

        /** @Then a failure describing the malformed literal is raised */
        $this->expectException(NumberNotWellFormed::class);

        /** @When a percentage is created from it */
        Percentage::of(value: $value);
    }

    public function testFromRatioWhenProportionRepeatsThenRoundsAtTheScale(): void
    {
        /** @Given a proportion whose percentage repeats forever */
        $ratio = Ratio::of(antecedent: 1, consequent: 3);

        /** @When it is expressed at scale two */
        $actual = Percentage::fromRatio(ratio: $ratio, scale: 2, rounding: RoundingMode::HalfEven);

        /** @Then it is rounded at that scale */
        self::assertSame('33.33%', $actual->toString());
    }

    public static function rateProvider(): array
    {
        return [
            'Whole'          => ['value' => '10', 'expected' => '0.10'],
            'Fractional'     => ['value' => '12.5', 'expected' => '0.125'],
            'Native integer' => ['value' => 25, 'expected' => '0.25'],
            'Hundred'        => ['value' => '100', 'expected' => '1.00'],
            'Above hundred'  => ['value' => '150', 'expected' => '1.50'],
            'Negative'       => ['value' => '-5', 'expected' => '-0.05'],
            'Zero'           => ['value' => '0', 'expected' => '0.00']
        ];
    }

    public static function signProvider(): array
    {
        return [
            'Zero'          => ['value' => '0', 'isZero' => true, 'isNegative' => false, 'isPositive' => false],
            'Negative'      => ['value' => '-5', 'isZero' => false, 'isNegative' => true, 'isPositive' => false],
            'Positive'      => ['value' => '5', 'isZero' => false, 'isNegative' => false, 'isPositive' => true],
            'Above hundred' => ['value' => '150', 'isZero' => false, 'isNegative' => false, 'isPositive' => true]
        ];
    }

    public static function comparisonProvider(): array
    {
        return [
            'Less than'      => ['value' => '10', 'other' => '12.5', 'expected' => -1],
            'Equal'          => ['value' => '10', 'other' => '10.00', 'expected' => 0],
            'Greater than'   => ['value' => '12.5', 'other' => '10', 'expected' => 1]
        ];
    }

    public static function applicationProvider(): array
    {
        return [
            'Discount'      => [
                'value'     => '12.5',
                'amount'    => '19.99',
                'applied'   => '2.49875',
                'increased' => '22.48875',
                'decreased' => '17.49125'
            ],
            'Whole rate'    => [
                'value'     => '10',
                'amount'    => '100.00',
                'applied'   => '10.0000',
                'increased' => '110.0000',
                'decreased' => '90.0000'
            ],
            'Negative rate' => [
                'value'     => '-10',
                'amount'    => '100.00',
                'applied'   => '-10.0000',
                'increased' => '90.0000',
                'decreased' => '110.0000'
            ],
            'Zero rate'     => [
                'value'     => '0',
                'amount'    => '100.00',
                'applied'   => '0.0000',
                'increased' => '100.0000',
                'decreased' => '100.0000'
            ]
        ];
    }
}
