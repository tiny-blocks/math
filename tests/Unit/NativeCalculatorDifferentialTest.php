<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Math\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Math\Calculator;
use TinyBlocks\Math\Exceptions\DivisionByZero;
use TinyBlocks\Math\Exceptions\NegativeExponent;
use TinyBlocks\Math\Exceptions\NegativeRoot;
use TinyBlocks\Math\Internal\Backends\BcMathCalculator;
use TinyBlocks\Math\Internal\Backends\NativeCalculator;

#[RequiresPhpExtension('bcmath')]
final class NativeCalculatorDifferentialTest extends TestCase
{
    private const array OPERANDS = [
        '0',
        '-0',
        '1',
        '-1',
        '7',
        '-7',
        '999999999',
        '1000000000',
        '-1000000001',
        '12345678901234567890',
        '-98765432109876543210987654321',
        '1000000001000000001000000001',
        '999999999999999999999999999999999999999999999'
    ];

    private const array EXPONENTS = [0, 1, 2, 3, 5, 8];

    public function testEveryPairThenAgreesWithTheExtension(): void
    {
        /** @Given the pure PHP backend */
        $native = new NativeCalculator();

        /** @And libbcmath as the reference implementation */
        $extension = new BcMathCalculator();

        /** @When the total operations run over every ordered pair in the corpus */
        $expected = [];
        $actual = [];

        foreach (self::OPERANDS as $left) {
            foreach (self::OPERANDS as $right) {
                $template = '%s(%s, %s)';

                $expected[sprintf($template, 'add', $left, $right)] = $extension->add(left: $left, right: $right);
                $actual[sprintf($template, 'add', $left, $right)] = $native->add(left: $left, right: $right);

                $expected[sprintf($template, 'subtract', $left, $right)] =
                    $extension->subtract(minuend: $left, subtrahend: $right);
                $actual[sprintf($template, 'subtract', $left, $right)] =
                    $native->subtract(minuend: $left, subtrahend: $right);

                $expected[sprintf($template, 'multiply', $left, $right)] =
                    $extension->multiply(left: $left, right: $right);
                $actual[sprintf($template, 'multiply', $left, $right)] =
                    $native->multiply(left: $left, right: $right);

                $expected[sprintf($template, 'compare', $left, $right)] =
                    $extension->compare(left: $left, right: $right);
                $actual[sprintf($template, 'compare', $left, $right)] =
                    $native->compare(left: $left, right: $right);
            }
        }

        /** @Then the pure PHP backend reproduces libbcmath digit for digit */
        self::assertSame($expected, $actual);
    }

    public function testEveryRootThenAgreesWithTheExtension(): void
    {
        /** @Given the pure PHP backend */
        $native = new NativeCalculator();

        /** @And libbcmath as the reference implementation */
        $extension = new BcMathCalculator();

        /** @When the truncated integer root runs over every non-negative radicand */
        $expected = [];
        $actual = [];
        $radicands = array_filter(
            self::OPERANDS,
            static fn(string $value): bool => !str_starts_with($value, '-') || ltrim($value, '-') === '0'
        );

        foreach ($radicands as $radicand) {
            $expected[$radicand] = $extension->squareRoot(radicand: $radicand);
            $actual[$radicand] = $native->squareRoot(radicand: $radicand);
        }

        /** @Then every root lands on the digit libbcmath reports */
        self::assertSame($expected, $actual);
    }

    public function testEveryPowerThenAgreesWithTheExtension(): void
    {
        /** @Given the pure PHP backend */
        $native = new NativeCalculator();

        /** @And libbcmath as the reference implementation */
        $extension = new BcMathCalculator();

        /** @When every base is raised to every exponent in the corpus */
        $expected = [];
        $actual = [];

        foreach (self::OPERANDS as $base) {
            foreach (self::EXPONENTS as $exponent) {
                $template = '%s^%d';

                $expected[sprintf($template, $base, $exponent)] =
                    $extension->power(base: $base, exponent: $exponent);
                $actual[sprintf($template, $base, $exponent)] =
                    $native->power(base: $base, exponent: $exponent);
            }
        }

        /** @Then squaring and multiplying reproduce libbcmath exactly */
        self::assertSame($expected, $actual);
    }

    public function testEveryDivisionThenAgreesWithTheExtension(): void
    {
        /** @Given the pure PHP backend */
        $native = new NativeCalculator();

        /** @And libbcmath as the reference implementation */
        $extension = new BcMathCalculator();

        /** @When the truncating division runs over every ordered pair with a non-zero divisor */
        $expected = [];
        $actual = [];
        $divisors = array_filter(self::OPERANDS, static fn(string $value): bool => ltrim($value, '-') !== '0');

        foreach (self::OPERANDS as $numerator) {
            foreach ($divisors as $denominator) {
                $template = '%s(%s, %s)';

                $expected[sprintf($template, 'quotient', $numerator, $denominator)] =
                    $extension->quotient(numerator: $numerator, denominator: $denominator);
                $actual[sprintf($template, 'quotient', $numerator, $denominator)] =
                    $native->quotient(numerator: $numerator, denominator: $denominator);

                $expected[sprintf($template, 'remainder', $numerator, $denominator)] =
                    $extension->remainder(numerator: $numerator, denominator: $denominator);
                $actual[sprintf($template, 'remainder', $numerator, $denominator)] =
                    $native->remainder(numerator: $numerator, denominator: $denominator);
            }
        }

        /** @Then quotient and remainder match libbcmath, signs included */
        self::assertSame($expected, $actual);
    }

    #[DataProvider('backendProvider')]
    public function testQuotientWhenDivisorIsZeroThenBothRefuse(Calculator $calculator): void
    {
        /** @Given a backend and a divisor the contract forbids */

        /** @Then a failure naming the dividend is raised */
        $this->expectException(DivisionByZero::class);
        $this->expectExceptionMessage('Cannot divide <7> by zero.');

        /** @When the truncating division runs */
        $calculator->quotient(numerator: '7', denominator: '0');
    }

    #[DataProvider('backendProvider')]
    public function testRemainderWhenDivisorIsZeroThenBothRefuse(Calculator $calculator): void
    {
        /** @Given a backend and a divisor the contract forbids */

        /** @Then a failure naming the dividend is raised */
        $this->expectException(DivisionByZero::class);
        $this->expectExceptionMessage('Cannot divide <7> by zero.');

        /** @When the remainder runs */
        $calculator->remainder(numerator: '7', denominator: '0');
    }

    #[DataProvider('backendProvider')]
    public function testPowerWhenExponentIsNegativeThenBothRefuse(Calculator $calculator): void
    {
        /** @Given a backend and an exponent the contract forbids */

        /** @Then a failure naming the exponent is raised */
        $this->expectException(NegativeExponent::class);

        /** @When the power runs */
        $calculator->power(base: '1', exponent: -1);
    }

    #[DataProvider('backendProvider')]
    public function testSquareRootWhenRadicandIsNegativeThenBothRefuse(Calculator $calculator): void
    {
        /** @Given a backend and a radicand the contract forbids */

        /** @Then a failure naming the radicand is raised */
        $this->expectException(NegativeRoot::class);
        $this->expectExceptionMessage('Cannot take the square root of the negative value <-4>.');

        /** @When the root runs */
        $calculator->squareRoot(radicand: '-4');
    }

    #[DataProvider('hardDivisionProvider')]
    public function testDivisionWhenTheDigitEstimateOvershootsThenAgreesWithTheExtension(
        string $numerator,
        string $denominator
    ): void {
        /** @Given a pair whose quotient digit needs the full correction the estimate allows */

        /** @When the truncating division runs on the pure PHP backend */
        $actual = new NativeCalculator()->quotient(numerator: $numerator, denominator: $denominator);

        /** @Then it still lands on the digits libbcmath reports */
        self::assertSame(new BcMathCalculator()->quotient(numerator: $numerator, denominator: $denominator), $actual);
    }

    public static function backendProvider(): array
    {
        return [
            'Extension' => ['calculator' => new BcMathCalculator()],
            'Pure PHP'  => ['calculator' => new NativeCalculator()]
        ];
    }

    public static function hardDivisionProvider(): array
    {
        return [
            'Two limb divisor'   => [
                'numerator'   => '459714127023879285092811644',
                'denominator' => '501933633760608253'
            ],
            'Three limb divisor' => [
                'numerator'   => '504742242870603765137420602077423734',
                'denominator' => '589524224922116716183555725'
            ],
            'Narrow overshoot'   => [
                'numerator'   => '595293886058520101586123288',
                'denominator' => '695280179905058033'
            ],
            'Wide overshoot'     => [
                'numerator'   => '528914152814300517288849623674805144',
                'denominator' => '569339172710255931955000324'
            ]
        ];
    }
}
