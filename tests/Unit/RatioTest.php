<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Math\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Math\BigDecimal;
use TinyBlocks\Math\BigInteger;
use TinyBlocks\Math\Exceptions\DivisionByZero;
use TinyBlocks\Math\Exceptions\InexactConversion;
use TinyBlocks\Math\Exceptions\NumberNotWellFormed;
use TinyBlocks\Math\Ratio;
use TinyBlocks\Math\RoundingMode;

final class RatioTest extends TestCase
{
    #[DataProvider('applicationProvider')]
    public function testApplyToThenStaysExact(int $antecedent, int $consequent, string $amount, string $expected): void
    {
        /** @Given a ratio and an amount to scale by it */
        $ratio = Ratio::of(antecedent: $antecedent, consequent: $consequent);

        /** @When the ratio is applied */
        $actual = $ratio->applyTo(amount: BigDecimal::of(value: $amount));

        /** @Then the exact fraction is returned, with no rounding decision forced */
        self::assertSame($expected, $actual->toString());
    }

    public function testInvertedThenSwapsTheTerms(): void
    {
        /** @Given a ratio */
        $ratio = Ratio::of(antecedent: 16, consequent: 9);

        /** @When it is inverted */
        $actual = $ratio->inverted();

        /** @Then the terms are swapped */
        self::assertSame('9:16', $actual->toString());
    }

    #[DataProvider('termProvider')]
    public function testOfThenReducesToLowestTerms(
        int|string $antecedent,
        int|string $consequent,
        string $expected,
        string $first,
        string $second
    ): void {
        /** @Given two terms with the reduced ratio they stand for */

        /** @When a ratio is created */
        $actual = Ratio::of(antecedent: $antecedent, consequent: $consequent);

        /** @Then the ratio is reduced and both terms read back */
        self::assertSame($expected, $actual->toString());
        self::assertSame($first, $actual->antecedent()->toString());
        self::assertSame($second, $actual->consequent()->toString());
    }

    public function testBetweenThenStaysExactWithoutAScale(): void
    {
        /** @Given a quantity */
        $antecedent = BigDecimal::of(value: '1.5');

        /** @And a second quantity three times as large */
        $consequent = BigDecimal::of(value: '4.5');

        /** @When the proportion between them is taken */
        $actual = Ratio::between(antecedent: $antecedent, consequent: $consequent);

        /** @Then the proportion is exact and reduced, with no rounding involved */
        self::assertSame('1:3', $actual->toString());
    }

    public function testToBigRationalThenExposesTheProportion(): void
    {
        /** @Given a ratio */
        $ratio = Ratio::of(antecedent: 16, consequent: 9);

        /** @When its proportion is taken */
        $actual = $ratio->toBigRational();

        /** @Then it reads as the equivalent fraction */
        self::assertSame('16/9', $actual->toString());
    }

    public function testOfWhenConsequentIsZeroThenDivisionByZero(): void
    {
        /** @Given a consequent of zero */
        $consequent = 0;

        /** @Then a failure describing the zero denominator is raised */
        $this->expectException(DivisionByZero::class);

        /** @When a ratio is created */
        Ratio::of(antecedent: 1, consequent: $consequent);
    }

    #[DataProvider('percentageProvider')]
    public function testToPercentageThenRoundsAtTheRequestedScale(
        int $antecedent,
        int $consequent,
        int $scale,
        string $expected
    ): void {
        /** @Given a ratio, a target scale, and the expected percentage */

        /** @When it is expressed per hundred */
        $actual = Ratio::of(antecedent: $antecedent, consequent: $consequent);

        /** @Then the percentage is rounded at that scale */
        self::assertSame(
            $expected,
            $actual->toPercentage(scale: $scale, rounding: RoundingMode::HalfEven)->toString()
        );
    }

    public function testEqualsWhenProportionsMatchThenTheyAreEqual(): void
    {
        /** @Given a ratio */
        $ratio = Ratio::of(antecedent: 16, consequent: 9);

        /** @And the same proportion written unreduced */
        $other = Ratio::of(antecedent: 32, consequent: 18);

        /** @When they are compared structurally */
        $actual = $ratio->equals(other: $other);

        /** @Then they are equal, because a ratio is always stored in lowest terms */
        self::assertTrue($actual);
    }

    public function testHashCodeWhenProportionsMatchThenHashesMatch(): void
    {
        /** @Given a ratio */
        $ratio = Ratio::of(antecedent: 16, consequent: 9);

        /** @And the same proportion written unreduced */
        $other = Ratio::of(antecedent: 32, consequent: 18);

        /** @When both hashes are taken */
        $actual = $ratio->hashCode();

        /** @Then they agree */
        self::assertSame($other->hashCode(), $actual);
    }

    public function testBetweenWhenConsequentIsZeroThenDivisionByZero(): void
    {
        /** @Given a quantity */
        $antecedent = BigInteger::of(value: 1);

        /** @Then a failure describing the zero divisor is raised */
        $this->expectException(DivisionByZero::class);

        /** @When the proportion against zero is taken */
        Ratio::between(antecedent: $antecedent, consequent: BigInteger::zero());
    }

    public function testInvertedWhenAntecedentIsZeroThenDivisionByZero(): void
    {
        /** @Given a ratio whose antecedent is zero */
        $ratio = Ratio::of(antecedent: 0, consequent: 5);

        /** @Then a failure describing the undefined reciprocal is raised */
        $this->expectException(DivisionByZero::class);

        /** @When it is inverted */
        $ratio->inverted();
    }

    public function testJsonSerializeThenEmitsAJsonStringThatReadsBack(): void
    {
        /** @Given a ratio */
        $ratio = Ratio::of(antecedent: 16, consequent: 9);

        /** @When it is encoded */
        $actual = json_encode($ratio);

        /** @Then it is a JSON string carrying the colon, and it round-trips */
        self::assertSame('"16:9"', $actual);
        self::assertTrue(Ratio::from(value: json_decode((string)$actual))->equals(other: $ratio));
    }

    public function testOfWhenTermCarriesAFractionalPartThenInexactConversion(): void
    {
        /** @Given a term with a non-zero fractional part */
        $antecedent = '1.5';

        /** @Then a failure describing the lost fractional part is raised */
        $this->expectException(InexactConversion::class);

        /** @When a ratio is created */
        Ratio::of(antecedent: $antecedent, consequent: 2);
    }

    public function testFromWhenTheStringDoesNotCarryTwoTermsThenNumberNotWellFormed(): void
    {
        /** @Given a string carrying three terms */
        $value = '16:9:4';

        /** @Then a failure describing the malformed literal is raised */
        $this->expectException(NumberNotWellFormed::class);

        /** @When a ratio is created from it */
        Ratio::from(value: $value);
    }

    public static function termProvider(): array
    {
        return [
            'Already reduced'      => [
                'antecedent' => 16,
                'consequent' => 9,
                'expected'   => '16:9',
                'first'      => '16',
                'second'     => '9'
            ],
            'Reducible'            => [
                'antecedent' => 4,
                'consequent' => 2,
                'expected'   => '2:1',
                'first'      => '2',
                'second'     => '1'
            ],
            'Negative antecedent'  => [
                'antecedent' => -1,
                'consequent' => 2,
                'expected'   => '-1:2',
                'first'      => '-1',
                'second'     => '2'
            ],
            'Negative consequent'  => [
                'antecedent' => 1,
                'consequent' => -2,
                'expected'   => '-1:2',
                'first'      => '-1',
                'second'     => '2'
            ],
            'Zero antecedent'      => [
                'antecedent' => 0,
                'consequent' => 5,
                'expected'   => '0:1',
                'first'      => '0',
                'second'     => '1'
            ],
            'String terms'         => [
                'antecedent' => '1000000000000000000000',
                'consequent' => '2000000000000000000000',
                'expected'   => '1:2',
                'first'      => '1',
                'second'     => '2'
            ]
        ];
    }

    public static function percentageProvider(): array
    {
        return [
            'Quarter'   => ['antecedent' => 1, 'consequent' => 4, 'scale' => 0, 'expected' => '25%'],
            'Third'     => ['antecedent' => 1, 'consequent' => 3, 'scale' => 2, 'expected' => '33.33%'],
            'Whole'     => ['antecedent' => 1, 'consequent' => 1, 'scale' => 0, 'expected' => '100%'],
            'Above one' => ['antecedent' => 3, 'consequent' => 2, 'scale' => 1, 'expected' => '150.0%']
        ];
    }

    public static function applicationProvider(): array
    {
        return [
            'Exact third'   => ['antecedent' => 1, 'consequent' => 3, 'amount' => '90.00', 'expected' => '30'],
            'Repeating'     => ['antecedent' => 1, 'consequent' => 3, 'amount' => '100.00', 'expected' => '100/3'],
            'Scaling up'    => ['antecedent' => 16, 'consequent' => 9, 'amount' => '9.00', 'expected' => '16'],
            'Negative term' => ['antecedent' => -1, 'consequent' => 2, 'amount' => '10.00', 'expected' => '-5']
        ];
    }
}
