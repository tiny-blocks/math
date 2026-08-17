# Upgrade from 3.x to 4.0

Version 4.0 replaces the whole public surface. Nothing from 3.x is preserved for compatibility,
because three of the operations that surface carried were not arbitrary precision at all.

## Why the surface was replaced

| Defect in 3.x | Where | What actually happened |
|---|---|---|
| Comparison lost precision past 53 bits. | `Internal\Number`, which compared with PHP's `<`, `>`, and `==`. | `'0.10000000000000000001' == '0.10000000000000000002'` is `true` in PHP, while `bccomp` at scale 30 returns `-1`. Every `isLessThan` and sibling on `BigNumber` was wrong past that point. |
| Rounding went through `float`. | `RoundingMode::round`, which called `toFloat()`. | `12345678901234567890.5` became `1.2345678901235E+19`. |
| Absolute value went through `float`. | `Internal\BigNumberBehavior::absolute`. | The same 53-bit ceiling. |
| Scale changes truncated instead of rounding. | `Internal\Scale::numberWithScale`. | It also indexed the fractional part without checking that one existed. |
| Sign subtypes broke substitutability. | `PositiveBigDecimal::add`. | Adding a larger negative addend produced a result the subtype's own constructor rejected. |

## Type mapping

| 3.x | 4.0 | Note |
|---|---|---|
| `BigNumber` | `Number` | An interface rather than a name that says "big". |
| `BigDecimal` | `BigDecimal` | Same name, new surface. |
| `PositiveBigDecimal` | Removed | A sign constraint is a consumer's domain invariant. Guard it where the domain lives, or call `isPositive()`. |
| `NegativeBigDecimal` | Removed | Same. |
| `TinyBlocks\Math\RoundingMode` | `TinyBlocks\Math\RoundingMode` | Same name, `string`-backed instead of `int`-backed, eight cases instead of four. Persisted values must be migrated. |
| Not present | `BigInteger` | New. |
| Not present | `BigRational` | New, and the reason division is total. |
| Not present | `Percentage` | New. |
| Not present | `Ratio` | New. |
| Not present | `BigDecimals` | New, for allocation weights and parts. |
| Not present | `Calculator`, `Calculators` | New, the pluggable calculation backend. |

## Method mapping

| 3.x | 4.0 | Note |
|---|---|---|
| `BigDecimal::fromString($value, $scale)` | `BigDecimal::of(value: $value)` | The scale comes from the literal. To change it, call `toScale` explicitly. |
| `BigDecimal::fromFloat($value, $scale)` | `BigDecimal::fromFloat(value: $value)` | No scale argument. Documented as the only lossy entry point. |
| `BigNumber::AUTOMATIC_SCALE` | No replacement | A null scale meaning "decide for me" is the ambient-context mistake in miniature. |
| `add(BigNumber $addend)` | `plus(BigDecimal $addend)` | |
| `subtract(BigNumber $subtrahend)` | `minus(BigDecimal $subtrahend)` | |
| `multiply(BigNumber $multiplier)` | `multipliedBy(BigDecimal $multiplier)` | |
| `divide(BigNumber $divisor)` | `dividedBy(BigDecimal $divisor)` | **Returns `BigRational`, not `BigDecimal`.** Follow it with `toDecimal(scale, rounding)` or `toDecimalExact()`. |
| `withRounding(RoundingMode $mode)` | `toScale(int $scale, RoundingMode $rounding)` | Rounding without naming a target scale is undefined, and 3.x resolved it through `float`. |
| `withScale(int $scale)` | `toScale(int $scale, RoundingMode $rounding)` or `toScaleExact(int $scale)` | Changing scale is a rounding operation, so it names its mode or refuses to lose digits. |
| `RoundingMode::round(BigNumber $value)` | `BigDecimal::toScale(int $scale, RoundingMode $rounding)` | Rounding is a method on the value, not on the mode. The mode is now an argument, and the arithmetic never touches `float`. |
| `getScale()` | `scale()` | |
| `absolute()` | `absolute()` | Same name, exact implementation. |
| `isZero()` | `isZero()` | |
| `isNegative()` | `isNegative()` | |
| `isPositive()` | `isPositive()` | |
| `isNegativeOrZero()` | `!$value->isPositive()` | |
| `isPositiveOrZero()` | `!$value->isNegative()` | |
| `isLessThan(BigNumber $other)` | `isLessThan(Number $other)` | Now exact past 53 bits. |
| `isLessThanOrEqual(BigNumber $other)` | `isLessThanOrEqualTo(Number $other)` | Renamed for symmetry with `isGreaterThanOrEqualTo`. |
| `isGreaterThan(BigNumber $other)` | `isGreaterThan(Number $other)` | Now exact past 53 bits. |
| `isGreaterThanOrEqual(BigNumber $other)` | `isGreaterThanOrEqualTo(Number $other)` | Renamed. |
| Not present | `isEqualTo(Number $other)` | Arithmetic equality. |
| Not present | `equals(BigDecimal $other)` | Structural equality, so `1.0` does not equal `1.00`. Declared on each concrete type with its own exact parameter type. |
| Not present | `compareTo(Number $other)` | |
| `toFloat()` | `toFloat()` | Now raises `IntegerOverflow` rather than returning infinity. |
| `toString()` | `toString()` | Always positional notation. |
| Not present | `jsonSerialize()` | Emits a JSON string. |

## Rounding mode mapping

The enum is now `string`-backed and gains the four directed modes 3.x could not express. The cases
are also renamed from `SCREAMING_SNAKE_CASE` to `PascalCase`, following PER Coding Style 2.0, which
requires PascalCase for enum cases, and matching PHP's own native `RoundingMode`. The backing
strings carry the persisted value, so the case name is a source-level rename only.

| 3.x case      | 3.x value | 4.0 case              | 4.0 value   |
|---------------|-----------|-----------------------|-------------|
| `HALF_UP`     | `1`       | `RoundingMode::HalfUp`   | `half-up`   |
| `HALF_DOWN`   | `2`       | `RoundingMode::HalfDown` | `half-down` |
| `HALF_EVEN`   | `3`       | `RoundingMode::HalfEven` | `half-even` |
| `HALF_ODD`    | `4`       | `RoundingMode::HalfOdd`  | `half-odd`  |
| Not present   |           | `RoundingMode::Up`       | `up`        |
| Not present   |           | `RoundingMode::Down`     | `down`      |
| Not present   |           | `RoundingMode::Ceiling`  | `ceiling`   |
| Not present   |           | `RoundingMode::Floor`    | `floor`     |

A rounding mode persisted as the 3.x integer must be migrated to the 4.0 string. There is no
automatic bridge, because the integers were an implementation detail and reusing them would tie the
new enum to the old numbering forever.

## Exception mapping

Every exception moved from `TinyBlocks\Math\Internal\Exceptions` to `TinyBlocks\Math\Exceptions`,
because consumers catch them and they therefore belong on the public boundary. All of them now
implement `MathFailure`, so a single catch clause covers the library.

| 3.x | 4.0 | Note |
|---|---|---|
| `Internal\Exceptions\DivisionByZero` | `Exceptions\DivisionByZero` | Now extends `DivisionByZeroError`. |
| `Internal\Exceptions\InvalidNumber` | `Exceptions\NumberNotWellFormed` | Named after the invariant. |
| `Internal\Exceptions\InvalidScale` | `Exceptions\ScaleOutOfRange` | Named after the invariant. |
| `Internal\Exceptions\MathOperationsNotAvailable` | `Exceptions\CalculatorNotAvailable` | Named after the invariant. |
| `Internal\Exceptions\NonPositiveValue` | Removed | Its only caller was `PositiveBigDecimal`. |
| `Internal\Exceptions\NonNegativeValue` | Removed | Its only caller was `NegativeBigDecimal`. |
| Not present | `Exceptions\NonTerminatingDecimal` | An exact decimal was demanded of a repeating expansion. |
| Not present | `Exceptions\InexactConversion` | An exact conversion would discard digits. |
| Not present | `Exceptions\NegativeExponent` | |
| Not present | `Exceptions\NegativeRoot` | |
| Not present | `Exceptions\ExponentOutOfRange` | An exponent is beyond the supported magnitude. |
| Not present | `Exceptions\NegativeWeight` | |
| Not present | `Exceptions\BaseOutOfRange` | |
| Not present | `Exceptions\IntegerOverflow` | |
| Not present | `Exceptions\MathFailure` | The marker interface every failure implements. |

## Requirements

| | 3.x | 4.0 |
|---|---|---|
| PHP | `^8.5` | `^8.5` |
| Extensions | `ext-bcmath` | None required, `ext-bcmath` suggested |
| Dependencies | None | None |

The PHP floor is unchanged, and the ecosystem pins 8.5. The extension is no longer required. All
eight rounding modes are decided by the library itself, in `RoundingMode::roundsAwayFromZero`, and
applied over the four primitives every backend provides, which is why the pure PHP backend produces
identical digits. That is what let `ext-bcmath` move from `require` to `suggest`. Install it for
speed: money arithmetic, rounding, comparison, allocation and division stay within roughly an order
of magnitude without it, while square roots run several dozen times slower.

## Worked example

3.x, where division silently produced a value at whatever scale the operands happened to carry, and
rounding went through a float:

```php
$total = BigDecimal::fromString(value: '100.00')
    ->divide(divisor: BigDecimal::fromString(value: '3'))
    ->withRounding(mode: RoundingMode::HALF_EVEN);
```

4.0, where the quotient is exact until you say otherwise, and the rounding names both a scale and a
mode:

```php
<?php

declare(strict_types=1);

use TinyBlocks\Math\BigDecimal;
use TinyBlocks\Math\RoundingMode;

$total = BigDecimal::of(value: '100.00')
    ->dividedBy(divisor: BigDecimal::of(value: '3'))
    ->toDecimal(scale: 2, rounding: RoundingMode::HalfEven);
# 33.33
```

