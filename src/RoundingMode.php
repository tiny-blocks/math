<?php

declare(strict_types=1);

namespace TinyBlocks\Math;

use RoundingMode as NativeRoundingMode;

/**
 * Policy deciding how a discarded fraction affects the last digit kept.
 *
 * <p>Nothing in this library rounds implicitly, so a mode is named only where a conversion is
 * asked for. There is no case meaning "do not round": that path is
 * <code>{@see BigDecimal::toScaleExact()}</code> and
 * <code>{@see BigRational::toDecimalExact()}</code>, which raise instead of guessing.</p>
 *
 * <p>Each case maps one to one onto PHP's native <code>RoundingMode</code>, so a policy crosses that
 * boundary in either direction. The rounding itself is decided here and applied exactly on integers,
 * never by PHP's float <code>round()</code>. The names follow the vocabulary used by the literature
 * on monetary rounding, and the backing values are stable strings so a policy survives a round trip
 * through configuration.</p>
 */
enum RoundingMode: string
{
    case Up = 'up';
    case Down = 'down';
    case Floor = 'floor';
    case HalfUp = 'half-up';
    case Ceiling = 'ceiling';
    case HalfOdd = 'half-odd';
    case HalfDown = 'half-down';
    case HalfEven = 'half-even';

    /**
     * Creates a RoundingMode from PHP's native rounding mode.
     *
     * @param NativeRoundingMode $mode The native mode to translate.
     * @return RoundingMode The equivalent case.
     */
    public static function fromNativeRoundingMode(NativeRoundingMode $mode): RoundingMode
    {
        return match ($mode) {
            NativeRoundingMode::AwayFromZero     => RoundingMode::Up,
            NativeRoundingMode::TowardsZero      => RoundingMode::Down,
            NativeRoundingMode::NegativeInfinity => RoundingMode::Floor,
            NativeRoundingMode::HalfAwayFromZero => RoundingMode::HalfUp,
            NativeRoundingMode::PositiveInfinity => RoundingMode::Ceiling,
            NativeRoundingMode::HalfOdd          => RoundingMode::HalfOdd,
            NativeRoundingMode::HalfTowardsZero  => RoundingMode::HalfDown,
            NativeRoundingMode::HalfEven         => RoundingMode::HalfEven
        };
    }

    /**
     * Tells whether a discarded fraction pushes the kept digit away from zero.
     *
     * <p>This is the whole definition of a rounding mode, expressed the way Java's
     * <code>RoundingMode</code> javadoc defines each constant: from how the discarded part compares
     * with one half, the sign of the value, and the parity of the digit being kept.</p>
     *
     * <p>A comparison of zero is a tie. The two tie-breaking modes raise their threshold by one
     * when the tie should stay put, so <code>HalfEven</code> keeps an even digit and
     * <code>HalfOdd</code> keeps an odd one.</p>
     *
     * @param bool $isEven Whether the last kept digit is even.
     * @param int $comparison How the discarded fraction compares with one half, never zero digits.
     * @param bool $isNegative Whether the value is negative.
     * @return bool True when the magnitude grows.
     */
    public function roundsAwayFromZero(bool $isEven, int $comparison, bool $isNegative): bool
    {
        return match ($this) {
            RoundingMode::Up       => true,
            RoundingMode::Down     => false,
            RoundingMode::Floor    => $isNegative,
            RoundingMode::HalfUp   => $comparison >= 0,
            RoundingMode::Ceiling  => !$isNegative,
            RoundingMode::HalfOdd  => $comparison >= intval(!$isEven),
            RoundingMode::HalfDown => $comparison > 0,
            RoundingMode::HalfEven => $comparison >= intval($isEven)
        };
    }

    /**
     * Returns the native rounding mode equivalent to this case.
     *
     * @return NativeRoundingMode The equivalent native mode.
     */
    public function toNativeRoundingMode(): NativeRoundingMode
    {
        return match ($this) {
            RoundingMode::Up       => NativeRoundingMode::AwayFromZero,
            RoundingMode::Down     => NativeRoundingMode::TowardsZero,
            RoundingMode::Floor    => NativeRoundingMode::NegativeInfinity,
            RoundingMode::HalfUp   => NativeRoundingMode::HalfAwayFromZero,
            RoundingMode::Ceiling  => NativeRoundingMode::PositiveInfinity,
            RoundingMode::HalfOdd  => NativeRoundingMode::HalfOdd,
            RoundingMode::HalfDown => NativeRoundingMode::HalfTowardsZero,
            RoundingMode::HalfEven => NativeRoundingMode::HalfEven
        };
    }
}
