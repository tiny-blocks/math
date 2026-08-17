<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Math\Unit;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use TinyBlocks\Math\BigDecimal;
use TinyBlocks\Math\Calculators;
use TinyBlocks\Math\Exceptions\CalculatorNotAvailable;
use TinyBlocks\Math\Internal\Backends\BcMathCalculator;

final class CalculatorsTest extends TestCase
{
    protected function tearDown(): void
    {
        Calculators::reset();
    }

    #[RequiresPhpExtension('bcmath')]
    public function testActiveThenResolvesTheBcMathBackend(): void
    {
        /** @Given a process with no backend registered */
        Calculators::reset();

        /** @When the active backend is asked for */
        $actual = Calculators::active();

        /** @Then BCMath wins the shipped order, because the extension is loaded here */
        self::assertInstanceOf(BcMathCalculator::class, $actual);
    }

    public function testActiveWhenCalledTwiceThenResolvesToTheSameBackend(): void
    {
        /** @Given a process with no backend registered */
        Calculators::reset();

        /** @When invoked twice */
        $first = Calculators::active();
        $second = Calculators::active();

        /** @Then both calls hand back the same instance */
        self::assertSame($first, $second);
    }

    public function testConstructorThenCannotBeReachedThroughAnyPublicPath(): void
    {
        /** @Given the static surface that resolves backends */
        $surface = new ReflectionClass(Calculators::class);

        /** @When its constructor is invoked by reflection, the only way to reach it at all */
        $surface->getConstructor()?->invoke($surface->newInstanceWithoutConstructor());

        /** @Then it is private, so no public path can do the same */
        self::assertTrue($surface->getConstructor()?->isPrivate());
    }

    #[RequiresPhpExtension('bcmath')]
    public function testRegisterWhenBackendIsAvailableThenArithmeticRunsThroughIt(): void
    {
        /** @Given a backend that records how often it is asked to add */
        $calculator = new CountingCalculatorMock();

        /** @And that backend registered for the process */
        Calculators::register(calculator: $calculator);

        /** @When an addition runs */
        BigDecimal::one()->plus(addend: BigDecimal::one());

        /** @Then the registered backend did the work */
        self::assertGreaterThan(0, $calculator->additions());
    }

    public function testRegisterWhenBackendIsUnavailableThenCalculatorNotAvailable(): void
    {
        /** @Given a backend that reports itself unavailable */
        $calculator = new UnavailableCalculatorMock();

        /** @Then a failure naming the rejected backend is raised */
        $this->expectException(CalculatorNotAvailable::class);
        $this->expectExceptionMessage('Calculator <Test\TinyBlocks\Math\Unit\UnavailableCalculatorMock> is not');

        /** @When it is registered */
        Calculators::register(calculator: $calculator);
    }

    #[RequiresPhpExtension('bcmath')]
    public function testResetWhenABackendWasRegisteredThenReturnsToAutomaticResolution(): void
    {
        /** @Given a registered backend that is not the one resolution would pick */
        Calculators::register(calculator: new CountingCalculatorMock());

        /** @When automatic resolution is restored */
        Calculators::reset();

        /** @Then the resolved backend is in use again */
        self::assertInstanceOf(BcMathCalculator::class, Calculators::active());
    }
}
