<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Math\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Math\BigDecimal;
use TinyBlocks\Math\BigDecimals;
use TinyBlocks\Math\BigInteger;
use TinyBlocks\Math\BigRational;
use TinyBlocks\Math\Calculators;
use TinyBlocks\Math\Exceptions\CalculatorNotAvailable;
use TinyBlocks\Math\Internal\Backends\BcMathCalculator;
use TinyBlocks\Math\Internal\Backends\CalculatorSelection;
use TinyBlocks\Math\Internal\Backends\NativeCalculator;
use TinyBlocks\Math\RoundingMode;

final class NativeCalculatorTest extends TestCase
{
    protected function setUp(): void
    {
        Calculators::register(calculator: new NativeCalculator());
    }

    protected function tearDown(): void
    {
        Calculators::reset();
    }

    #[DataProvider('rootProvider')]
    public function testSquareRootThenTruncatesTowardZero(string $radicand, string $expected): void
    {
        /** @Given a radicand and its truncated integer root */

        /** @When the root is taken through the pure PHP backend */
        $actual = BigInteger::of(value: $radicand)->squareRoot();

        /** @Then the digit-by-digit pair extraction settles on the floor of the exact root */
        self::assertSame($expected, $actual->toString());
    }

    #[DataProvider('additionProvider')]
    public function testAdditionThenMatchesTheDocumentedResult(string $left, string $right, string $expected): void
    {
        /** @Given two integers and the exact sum */

        /** @When they are added through the pure PHP backend */
        $actual = BigInteger::of(value: $left)->plus(addend: BigInteger::of(value: $right));

        /** @Then the sum crosses every limb boundary intact */
        self::assertSame($expected, $actual->toString());
    }

    #[DataProvider('powerProvider')]
    public function testPowerThenMatchesRepeatedMultiplication(string $base, int $exponent, string $expected): void
    {
        /** @Given a base, an exponent, and the exact power */

        /** @When it is raised through the pure PHP backend */
        $actual = BigInteger::of(value: $base)->power(exponent: $exponent);

        /** @Then squaring and multiplying agree with repeated multiplication */
        self::assertSame($expected, $actual->toString());
    }

    #[DataProvider('comparisonProvider')]
    public function testComparisonThenOrdersAcrossTheSignBoundary(string $left, string $right, int $expected): void
    {
        /** @Given two integers and the expected ordering */

        /** @When they are compared through the pure PHP backend */
        $actual = BigInteger::of(value: $left)->compareTo(other: BigInteger::of(value: $right));

        /** @Then the ordering holds across signs and limb counts */
        self::assertSame($expected, $actual);
    }

    #[DataProvider('subtractionProvider')]
    public function testSubtractionThenMatchesTheDocumentedResult(string $left, string $right, string $expected): void
    {
        /** @Given two integers and the exact difference */

        /** @When one is subtracted from the other through the pure PHP backend */
        $actual = BigInteger::of(value: $left)->minus(subtrahend: BigInteger::of(value: $right));

        /** @Then every borrow propagates across the limbs */
        self::assertSame($expected, $actual->toString());
    }

    public function testDecimalArithmeticThenStaysExactWithoutBcMath(): void
    {
        /** @Given a price beyond the float mantissa */
        $price = BigDecimal::of(value: '19.99');

        /** @When it is scaled and rounded through the pure PHP backend */
        $actual = $price->multipliedBy(multiplier: BigDecimal::of(value: '0.875'));

        /** @Then the decimal path produces the same exact result the extension would */
        self::assertSame('17.49125', $actual->toString());
        self::assertSame('17.49', $actual->toScale(scale: 2, rounding: RoundingMode::HalfEven)->toString());
    }

    #[DataProvider('productProvider')]
    public function testMultiplicationThenMatchesTheDocumentedResult(
        string $left,
        string $right,
        string $expected
    ): void {
        /** @Given two integers and the exact product */

        /** @When they are multiplied through the pure PHP backend */
        $actual = BigInteger::of(value: $left)->multipliedBy(multiplier: BigInteger::of(value: $right));

        /** @Then every carry lands in the right limb */
        self::assertSame($expected, $actual->toString());
    }

    #[DataProvider('divisionProvider')]
    public function testDivisionThenFollowsTheDocumentedSignConventions(
        string $numerator,
        string $denominator,
        string $quotient,
        string $remainder
    ): void {
        /** @Given a dividend, a divisor, and both documented results */

        /** @When the integer division runs through the pure PHP backend */
        $actual = BigInteger::of(value: $numerator);

        /** @Then the quotient truncates toward zero and the remainder follows the dividend */
        self::assertSame($quotient, $actual->quotient(divisor: BigInteger::of(value: $denominator))->toString());
        self::assertSame($remainder, $actual->remainder(divisor: BigInteger::of(value: $denominator))->toString());
    }

    public function testIsAvailableThenReportsWhetherIntegersAreWideEnough(): void
    {
        /** @Given the pure PHP backend */
        $calculator = new NativeCalculator();

        /** @When it is asked whether it can run */
        $actual = $calculator->isAvailable();

        /** @Then it runs here, because this platform carries 64-bit integers */
        self::assertTrue($actual);
        self::assertSame(8, PHP_INT_SIZE);
    }

    #[RequiresPhpExtension('bcmath')]
    public function testEveryCollaboratorThenProducesTheSameResultAsTheExtension(): void
    {
        /** @Given a workload that reaches rounding, fractions, bases, allocation and roots */
        $workload = static fn(): array => [
            'divided'    => BigDecimal::of(value: '123456789012345.6789')
                ->dividedBy(divisor: BigDecimal::of(value: '9876543.21'))
                ->toDecimal(scale: 12, rounding: RoundingMode::HalfEven)
                ->toString(),
            'reduced'    => BigRational::of(value: '123456789012345678/98765432109876')->toString(),
            'commonRoot' => BigInteger::of(value: '123456789012345678')
                ->greatestCommonDivisor(other: BigInteger::of(value: '98765432109876'))
                ->toString(),
            'base'       => BigInteger::of(value: '123456789012345678901234567890')->toBase(base: 36),
            'allocated'  => implode(',', array_map(
                static fn(BigDecimal $part): string => $part->toString(),
                BigDecimal::of(value: '123456789012345.67')
                    ->allocate(scale: 2, weights: BigDecimals::of('1', '2', '7'))
                    ->all()
            )),
            'root'       => BigDecimal::of(value: '123456789012345.6789')
                ->squareRoot(scale: 10, rounding: RoundingMode::HalfEven)
                ->toString()
        ];

        /** @When it runs once on the extension and once on the pure PHP backend */
        Calculators::register(calculator: new BcMathCalculator());
        $expected = $workload();

        Calculators::register(calculator: new NativeCalculator());
        $actual = $workload();

        /** @Then every collaborator that routes through the backend produces the same digits */
        self::assertSame($expected, $actual);
    }

    public function testResolutionWhenNoCandidateCanRunThenCalculatorNotAvailable(): void
    {
        /** @Given a candidate list where nothing can run */
        $unavailable = new UnavailableCalculatorMock();

        /** @Then a failure describing the empty resolution is raised */
        $this->expectException(CalculatorNotAvailable::class);

        /** @When the selection resolves over it */
        new CalculatorSelection()->resolved($unavailable);
    }

    public function testResolutionWhenBcMathIsMissingThenFallsBackToThePurePhpBackend(): void
    {
        /** @Given a candidate list whose first backend cannot run in this process */
        $unavailable = new UnavailableCalculatorMock();

        /** @When the selection resolves over it and the pure PHP backend */
        $actual = new CalculatorSelection()->resolved($unavailable, new NativeCalculator());

        /** @Then the pure PHP backend is taken, so the extension is not required */
        self::assertInstanceOf(NativeCalculator::class, $actual);
    }

    public static function rootProvider(): array
    {
        return [
            'Zero'            => ['radicand' => '0', 'expected' => '0'],
            'One'             => ['radicand' => '1', 'expected' => '1'],
            'Below a square'  => ['radicand' => '8', 'expected' => '2'],
            'Exact square'    => ['radicand' => '9', 'expected' => '3'],
            'Single limb'     => ['radicand' => '999999999', 'expected' => '31622'],
            'Across limbs'    => ['radicand' => '12345678901234567890123456789', 'expected' => '111111110611111']
        ];
    }

    public static function powerProvider(): array
    {
        return [
            'Zero exponent'    => ['base' => '7', 'exponent' => 0, 'expected' => '1'],
            'One exponent'     => ['base' => '-7', 'exponent' => 1, 'expected' => '-7'],
            'Even exponent'    => ['base' => '-3', 'exponent' => 4, 'expected' => '81'],
            'Odd exponent'     => ['base' => '-3', 'exponent' => 5, 'expected' => '-243'],
            'Across limbs'     => [
                'base'     => '1000000000',
                'exponent' => 3,
                'expected' => '1000000000000000000000000000'
            ],
            'Zero base'        => ['base' => '0', 'exponent' => 5, 'expected' => '0']
        ];
    }

    public static function productProvider(): array
    {
        return [
            'By zero'         => ['left' => '12345678901234567890', 'right' => '0', 'expected' => '0'],
            'Zero by value'   => ['left' => '0', 'right' => '12345678901234567890', 'expected' => '0'],
            'Sign flips'      => ['left' => '-7', 'right' => '6', 'expected' => '-42'],
            'Both negative'   => ['left' => '-7', 'right' => '-6', 'expected' => '42'],
            'Limb boundary'   => ['left' => '1000000000', 'right' => '1000000000', 'expected' => '1000000000000000000'],
            'Full carry'      => [
                'left'     => '99999999999999999999',
                'right'    => '99999999999999999999',
                'expected' => '9999999999999999999800000000000000000001'
            ]
        ];
    }

    public static function additionProvider(): array
    {
        return [
            'Zero and zero'      => ['left' => '0', 'right' => '0', 'expected' => '0'],
            'Carry across limbs' => ['left' => '999999999', 'right' => '1', 'expected' => '1000000000'],
            'Opposite signs'     => ['left' => '1000000000', 'right' => '-1', 'expected' => '999999999'],
            'Both negative'      => ['left' => '-999999999', 'right' => '-1', 'expected' => '-1000000000'],
            'Cancelling'         => [
                'left'     => '-12345678901234567890',
                'right'    => '12345678901234567890',
                'expected' => '0'
            ],
            'Many limbs'         => [
                'left'     => '999999999999999999999999999',
                'right'    => '1',
                'expected' => '1000000000000000000000000000'
            ]
        ];
    }

    public static function divisionProvider(): array
    {
        return [
            'Exact' => [
                'numerator'   => '100',
                'denominator' => '5',
                'quotient'    => '20',
                'remainder'   => '0'
            ],
            'Truncates toward zero' => [
                'numerator'   => '7',
                'denominator' => '2',
                'quotient'    => '3',
                'remainder'   => '1'
            ],
            'Negative dividend' => [
                'numerator'   => '-7',
                'denominator' => '2',
                'quotient'    => '-3',
                'remainder'   => '-1'
            ],
            'Negative divisor' => [
                'numerator'   => '7',
                'denominator' => '-2',
                'quotient'    => '-3',
                'remainder'   => '1'
            ],
            'Both negative' => [
                'numerator'   => '-7',
                'denominator' => '-2',
                'quotient'    => '3',
                'remainder'   => '-1'
            ],
            'Divisor is larger' => [
                'numerator'   => '5',
                'denominator' => '1000000000000',
                'quotient'    => '0',
                'remainder'   => '5'
            ],
            'Across limbs'         => [
                'numerator'   => '12345678901234567890123456789',
                'denominator' => '987654321',
                'quotient'    => '12499999887343749990',
                'remainder'   => '156249999'
            ]
        ];
    }

    public static function comparisonProvider(): array
    {
        return [
            'Equal'              => ['left' => '0', 'right' => '0', 'expected' => 0],
            'Equal across limbs' => ['left' => '1000000000', 'right' => '1000000000', 'expected' => 0],
            'Negative to zero'   => ['left' => '-1', 'right' => '0', 'expected' => -1],
            'Zero to negative'   => ['left' => '0', 'right' => '-1', 'expected' => 1],
            'Both negative'      => ['left' => '-2', 'right' => '-1', 'expected' => -1],
            'Limb count decides' => ['left' => '1000000000', 'right' => '999999999', 'expected' => 1],
            'Deep limb decides'  => [
                'left'     => '1000000000000000000000000001',
                'right'    => '1000000000000000000000000000',
                'expected' => 1
            ]
        ];
    }

    public static function subtractionProvider(): array
    {
        return [
            'Borrow across limbs' => ['left' => '1000000000', 'right' => '1', 'expected' => '999999999'],
            'To zero'             => ['left' => '42', 'right' => '42', 'expected' => '0'],
            'Below zero'          => ['left' => '1', 'right' => '1000000000', 'expected' => '-999999999'],
            'Negative minuend'    => ['left' => '-1000000000', 'right' => '1', 'expected' => '-1000000001'],
            'Double negative'     => ['left' => '-1000000000', 'right' => '-1', 'expected' => '-999999999'],
            'Many limbs'          => [
                'left'     => '1000000000000000000000000000',
                'right'    => '1',
                'expected' => '999999999999999999999999999'
            ]
        ];
    }
}
