<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Math\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RoundingMode as NativeRoundingMode;
use TinyBlocks\Math\BigDecimal;
use TinyBlocks\Math\RoundingMode;

final class RoundingModeTest extends TestCase
{
    #[DataProvider('nativeEquivalentProvider')]
    public function testToNativeRoundingModeThenMatchesTheNativeCase(
        RoundingMode $mode,
        NativeRoundingMode $expected
    ): void {
        /** @Given a rounding mode and the native case it stands for */

        /** @When the native equivalent is requested */
        $actual = $mode->toNativeRoundingMode();

        /** @Then the native case is returned */
        self::assertSame($expected, $actual);
    }

    #[DataProvider('nativeEquivalentProvider')]
    public function testFromNativeRoundingModeThenMatchesTheLibraryCase(
        RoundingMode $mode,
        NativeRoundingMode $expected
    ): void {
        /** @Given a library rounding mode and the native case it stands for */

        /** @When the library equivalent of the native case is requested */
        $actual = RoundingMode::fromNativeRoundingMode(mode: $expected);

        /** @Then the library case is returned */
        self::assertSame($mode, $actual);
    }

    #[DataProvider('halfwayValueProvider')]
    public function testRoundingWhenTheDiscardedPartIsExactlyHalfThenEachModeDiffers(
        RoundingMode $mode,
        string $expected
    ): void {
        /** @Given a value whose discarded part at scale two is exactly half */

        /** @When it is taken to scale two under the mode */
        $actual = BigDecimal::of(value: '-2.345')->toScale(scale: 2, rounding: $mode);

        /** @Then the mode decides the last digit */
        self::assertSame($expected, $actual->toString());
    }

    public static function halfwayValueProvider(): array
    {
        return [
            'Away from zero'      => ['mode' => RoundingMode::Up, 'expected' => '-2.35'],
            'Toward zero'         => ['mode' => RoundingMode::Down, 'expected' => '-2.34'],
            'Toward negative'     => ['mode' => RoundingMode::Floor, 'expected' => '-2.35'],
            'Half away from zero' => ['mode' => RoundingMode::HalfUp, 'expected' => '-2.35'],
            'Toward positive'     => ['mode' => RoundingMode::Ceiling, 'expected' => '-2.34'],
            'Half to odd'         => ['mode' => RoundingMode::HalfOdd, 'expected' => '-2.35'],
            'Half toward zero'    => ['mode' => RoundingMode::HalfDown, 'expected' => '-2.34'],
            'Half to even'        => ['mode' => RoundingMode::HalfEven, 'expected' => '-2.34']
        ];
    }

    public static function nativeEquivalentProvider(): array
    {
        return [
            'Up'        => ['mode' => RoundingMode::Up, 'expected' => NativeRoundingMode::AwayFromZero],
            'Down'      => ['mode' => RoundingMode::Down, 'expected' => NativeRoundingMode::TowardsZero],
            'Floor'     => ['mode' => RoundingMode::Floor, 'expected' => NativeRoundingMode::NegativeInfinity],
            'HalfUp'    => ['mode' => RoundingMode::HalfUp, 'expected' => NativeRoundingMode::HalfAwayFromZero],
            'Ceiling'   => ['mode' => RoundingMode::Ceiling, 'expected' => NativeRoundingMode::PositiveInfinity],
            'HalfOdd'   => ['mode' => RoundingMode::HalfOdd, 'expected' => NativeRoundingMode::HalfOdd],
            'HalfDown'  => ['mode' => RoundingMode::HalfDown, 'expected' => NativeRoundingMode::HalfTowardsZero],
            'HalfEven'  => ['mode' => RoundingMode::HalfEven, 'expected' => NativeRoundingMode::HalfEven]
        ];
    }
}
