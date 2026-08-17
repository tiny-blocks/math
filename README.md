# Math

[![License](https://img.shields.io/badge/license-MIT-green)](https://github.com/tiny-blocks/math/blob/main/LICENSE)

* [Overview](#overview)
* [Installation](#installation)
* [How to use](#how-to-use)
    + [Number](#number)
    + [BigDecimal](#bigdecimal)
    + [BigInteger](#biginteger)
    + [BigRational](#bigrational)
    + [Percentage](#percentage)
    + [Ratio](#ratio)
    + [BigDecimals](#bigdecimals)
    + [RoundingMode](#roundingmode)
    + [Failures](#failures)
    + [Calculation backend](#calculation-backend)
* [FAQ](#faq)
* [License](#license)
* [Contributing](#contributing)

<div id='overview'></div>

## Overview

Arbitrary-precision numbers for PHP, where arithmetic is exact and rounding is explicit.

Addition, subtraction and multiplication never round. Division returns a `BigRational`, so it is exact for every pair of
operands and raises only on a zero divisor. Rounding happens where you ask for it, by naming both a scale and a
`RoundingMode`. There is no ambient precision context, no process-global scale, and no default rounding mode to forget.

The arithmetic runs through a single integer engine, and every value type is a thin facade over it. The engine is the
`bcmath` extension when it is loaded, and a pure PHP backend when it is not, so the library has no required extension
and no Composer dependency. Both produce identical results, so `bcmath` buys speed rather than correctness. On money
values the pure PHP backend stays within roughly an order of magnitude on arithmetic, rounding, comparison, allocation
and division. Square roots are the outlier, several dozen times slower. The gap widens with operand size, so install the
extension where wide operands meet throughput. Nothing in the library routes a number through `float`.

Pairs with [tiny-blocks/currency](https://github.com/tiny-blocks/currency), whose
`Currency::getFractionDigits()` is exactly the scale `toScale` wants. The library has no Composer
dependencies of its own.

<div id='installation'></div>

## Installation

```bash
composer require tiny-blocks/math
```

<div id='how-to-use'></div>

## How to use

Every numeric type is immutable, and every operation returns a new instance.

<div id='number'></div>

### Number

The contract `BigInteger`, `BigDecimal` and `BigRational` share. Every method below works across all three, so a
function can take a `Number` and compare or convert whatever arrives.

Comparison is exact whatever the pair of types. Two numbers of the same type are compared on their own representation.
Across types the comparison goes through `BigRational`, the only form every number has exactly, which is what keeps the
contract to one method instead of one per pair of types.

| Method                                  | Returns       | Notes                                            |
|-----------------------------------------|---------------|--------------------------------------------------|
| `compareTo(Number $other)`              | `int`         | Negative, zero, or positive. Exact across types. |
| `isEqualTo(Number $other)`              | `bool`        | Arithmetic, so `1.0` equals `1.00`.              |
| `isLessThan(Number $other)`             | `bool`        |                                                  |
| `isGreaterThan(Number $other)`          | `bool`        |                                                  |
| `isLessThanOrEqualTo(Number $other)`    | `bool`        |                                                  |
| `isGreaterThanOrEqualTo(Number $other)` | `bool`        |                                                  |
| `isZero()`                              | `bool`        |                                                  |
| `isNegative()`                          | `bool`        | Strictly less than zero.                         |
| `isPositive()`                          | `bool`        | Strictly greater than zero.                      |
| `negated()`                             | `Number`      |                                                  |
| `absolute()`                            | `Number`      |                                                  |
| `toBigRational()`                       | `BigRational` |                                                  |
| `toString()`                            | `string`      | Round-trips through the type's own factory.      |
| `hashCode()`                            | `string`      | Agrees with the type's own `equals`.             |
| `jsonSerialize()`                       | `string`      | A JSON string, never a JSON number.              |

Each implementation adds an `equals` of its own, typed to its exact class: `BigDecimal::equals(BigDecimal $other)`,
`BigInteger::equals(BigInteger $other)`, `BigRational::equals(BigRational $other)`. That one is structural, so `1.0`
does **not** equal `1.00`, and comparing across types is a type error rather than a silent `false`. `isEqualTo` and
`equals` answer different questions on purpose, and FAQ 02 explains why.

```php
<?php

declare(strict_types=1);

use TinyBlocks\Math\BigDecimal;
use TinyBlocks\Math\BigInteger;

BigInteger::of(value: 5)->isEqualTo(other: BigDecimal::of(value: '5.00'));
# true
```

`Percentage` and `Ratio` are not `Number` instances: a rate and a proportion are not quantities you add to an amount.
`Percentage` carries the same comparison predicates typed against `Percentage`. `Percentage` converts with `rate()` or
`toRatio()`, and `Ratio` with `toBigRational()` or `toPercentage()`.

<div id='bigdecimal'></div>

### BigDecimal

An arbitrary-precision decimal, held as an unscaled integer and a non-negative scale.

```php
<?php

declare(strict_types=1);

use TinyBlocks\Math\BigDecimal;
use TinyBlocks\Math\Percentage;
use TinyBlocks\Math\RoundingMode;

$price = BigDecimal::of(value: '19.99');
$final = Percentage::of(value: '12.5')->decrease(amount: $price);

$final->toScale(scale: 2, rounding: RoundingMode::HalfEven);
# 17.49
```

| Method                                              | Returns       | Result scale              |
|-----------------------------------------------------|---------------|---------------------------|
| `of(string\|int $value)`                            | `BigDecimal`  | The scale of the literal. |
| `one()`                                             | `BigDecimal`  | 0.                        |
| `zero()`                                            | `BigDecimal`  | 0.                        |
| `fromFloat(float $value)`                           | `BigDecimal`  | The shortest round-trip.  |
| `ofUnscaledValue(int $scale, BigInteger $unscaled)` | `BigDecimal`  | `$scale`.                 |
| `plus(BigDecimal $addend)`                          | `BigDecimal`  | `max(a, b)`.              |
| `minus(BigDecimal $subtrahend)`                     | `BigDecimal`  | `max(a, b)`.              |
| `multipliedBy(BigDecimal $multiplier)`              | `BigDecimal`  | `a + b`.                  |
| `dividedBy(BigDecimal $divisor)`                    | `BigRational` | Not applicable.           |
| `power(int $exponent)`                              | `BigDecimal`  | `a × exponent`.           |
| `squareRoot(int $scale, RoundingMode $rounding)`    | `BigDecimal`  | `$scale`.                 |
| `toScale(int $scale, RoundingMode $rounding)`       | `BigDecimal`  | `$scale`.                 |
| `toScaleExact(int $scale)`                          | `BigDecimal`  | `$scale`.                 |
| `withoutTrailingZeros()`                            | `BigDecimal`  | The smallest possible.    |
| `allocate(int $scale, BigDecimals $weights)`        | `BigDecimals` | `$scale`.                 |
| `negated()`                                         | `BigDecimal`  | Unchanged.                |
| `absolute()`                                        | `BigDecimal`  | Unchanged.                |
| `scale()`                                           | `int`         | Not applicable.           |
| `unscaledValue()`                                   | `BigInteger`  | Not applicable.           |
| `integralPart()`                                    | `BigInteger`  | Not applicable.           |
| `fractionalPart()`                                  | `BigDecimal`  | Unchanged.                |
| `toBigInteger()`                                    | `BigInteger`  | Not applicable.           |
| `toBigRational()`                                   | `BigRational` | Not applicable.           |
| `toFloat()`                                         | `float`       | Not applicable.           |
| `toString()`                                        | `string`      | Not applicable.           |

Scale propagation matches Java's `BigDecimal`, so nothing here surprises a reader who knows it.

`allocate` splits an amount so that the parts sum back to it exactly. Each part is the exact share truncated toward
negative infinity at the given scale, and the units left over are handed out one at a time in descending order of the
discarded remainder, ties broken by position. Plain rounding cannot preserve the total, which is why this exists.

```php
<?php

declare(strict_types=1);

use TinyBlocks\Math\BigDecimal;
use TinyBlocks\Math\BigDecimals;

$parts = BigDecimal::of(value: '100.00')->allocate(scale: 2, weights: BigDecimals::of('1', '1', '1'));

$parts->sum()->toString();
# 100.00, and the parts are 33.34, 33.33, 33.33
```

<div id='biginteger'></div>

### BigInteger

An arbitrary-precision integer, for problems that have no scale.

```php
<?php

declare(strict_types=1);

use TinyBlocks\Math\BigInteger;

BigInteger::of(value: '9007199254740993')
    ->multipliedBy(multiplier: BigInteger::of(value: '9007199254740993'))
    ->toString();
# 81129638414606699710187514626049
```

| Method                                     | Returns       | Notes                               |
|--------------------------------------------|---------------|-------------------------------------|
| `of(string\|int $value)`                   | `BigInteger`  | A zero fractional part is accepted. |
| `one()`                                    | `BigInteger`  |                                     |
| `zero()`                                   | `BigInteger`  |                                     |
| `fromBase(int $base, string $value)`       | `BigInteger`  | Base 2 to 36, case-insensitive.     |
| `plus(BigInteger $addend)`                 | `BigInteger`  |                                     |
| `minus(BigInteger $subtrahend)`            | `BigInteger`  |                                     |
| `multipliedBy(BigInteger $multiplier)`     | `BigInteger`  |                                     |
| `dividedBy(BigInteger $divisor)`           | `BigRational` | Exact, never rounds.                |
| `quotient(BigInteger $divisor)`            | `BigInteger`  | Truncated toward zero.              |
| `remainder(BigInteger $divisor)`           | `BigInteger`  | Sign follows the dividend.          |
| `modulo(BigInteger $modulus)`              | `BigInteger`  | Never negative.                     |
| `power(int $exponent)`                     | `BigInteger`  | Rejects a negative exponent.        |
| `squareRoot()`                             | `BigInteger`  | Floor.                              |
| `greatestCommonDivisor(BigInteger $other)` | `BigInteger`  | Never negative.                     |
| `isEven()`                                 | `bool`        |                                     |
| `isOdd()`                                  | `bool`        |                                     |
| `toBase(int $base)`                        | `string`      | Lowercase for bases above ten.      |
| `toBigDecimal()`                           | `BigDecimal`  | Scale zero.                         |
| `toBigRational()`                          | `BigRational` | Denominator one.                    |
| `toInt()`                                  | `int`         | Raises outside the native range.    |
| `toString()`                               | `string`      |                                     |

`remainder` and `modulo` are separate methods because the two conventions genuinely differ:
`bcmod` follows the dividend's sign while `gmp_mod` never returns a negative. Hiding both behind one name is how a
backend swap silently changes answers.

<div id='bigrational'></div>

### BigRational

An exact fraction, always in lowest terms with a strictly positive denominator. This is the type that lets division stay
total.

```php
<?php

declare(strict_types=1);

use TinyBlocks\Math\BigDecimal;
use TinyBlocks\Math\RoundingMode;

$share = BigDecimal::of(value: '100.00')->dividedBy(divisor: BigDecimal::of(value: '3'));

$share->toDecimal(scale: 2, rounding: RoundingMode::HalfEven)->toString();
# 33.33
```

| Method                                                       | Returns       | Notes                                      |
|--------------------------------------------------------------|---------------|--------------------------------------------|
| `of(string\|int $value)`                                     | `BigRational` | Accepts `3/4`, `0.75`, and `3`.            |
| `ofFraction(BigInteger $numerator, BigInteger $denominator)` | `BigRational` | Reduced on construction.                   |
| `one()`                                                      | `BigRational` |                                            |
| `zero()`                                                     | `BigRational` |                                            |
| `plus(BigRational $addend)`                                  | `BigRational` | Exact.                                     |
| `minus(BigRational $subtrahend)`                             | `BigRational` | Exact.                                     |
| `multipliedBy(BigRational $multiplier)`                      | `BigRational` | Exact.                                     |
| `dividedBy(BigRational $divisor)`                            | `BigRational` | Exact.                                     |
| `power(int $exponent)`                                       | `BigRational` | Negative exponents supported.              |
| `reciprocal()`                                               | `BigRational` | Raises on zero.                            |
| `numerator()`                                                | `BigInteger`  | Carries the sign.                          |
| `denominator()`                                              | `BigInteger`  | Always positive.                           |
| `hasTerminatingDecimal()`                                    | `bool`        | True when the denominator is 2^a·5^b.      |
| `toDecimal(int $scale, RoundingMode $rounding)`              | `BigDecimal`  |                                            |
| `toDecimalExact()`                                           | `BigDecimal`  | Raises when the expansion repeats.         |
| `toBigInteger()`                                             | `BigInteger`  | Raises when the denominator is not one.    |
| `toFloat()`                                                  | `float`       |                                            |
| `toString()`                                                 | `string`      | `3/4`, or `3` when the denominator is one. |

`hasTerminatingDecimal()` is the cheap way to ask before converting, instead of calling
`toDecimalExact()` and catching the failure.

<div id='percentage'></div>

### Percentage

A rate expressed per hundred, held as a `BigDecimal`. Applying it to an amount is a multiplication and therefore exact,
so no rounding decision is forced until the result is presented.

```php
<?php

declare(strict_types=1);

use TinyBlocks\Math\BigDecimal;
use TinyBlocks\Math\Percentage;
use TinyBlocks\Math\RoundingMode;

Percentage::of(value: '12.5')
    ->increase(amount: BigDecimal::of(value: '19.99'))
    ->toScale(scale: 2, rounding: RoundingMode::HalfEven)
    ->toString();
# 22.49
```

| Method                                                        | Returns      | Notes                                                              |
|---------------------------------------------------------------|--------------|--------------------------------------------------------------------|
| `of(string\|int $value)`                                      | `Percentage` | `'12.5'` is twelve and a half percent. A trailing `%` is accepted. |
| `zero()`                                                      | `Percentage` |                                                                    |
| `fromRatio(Ratio $ratio, int $scale, RoundingMode $rounding)` | `Percentage` | A scale is required, a ratio may not terminate.                    |
| `rate()`                                                      | `BigDecimal` | `0.125` for twelve and a half percent.                             |
| `applyTo(BigDecimal $amount)`                                 | `BigDecimal` | Exact.                                                             |
| `increase(BigDecimal $amount)`                                | `BigDecimal` | Exact.                                                             |
| `decrease(BigDecimal $amount)`                                | `BigDecimal` | Exact.                                                             |
| `toRatio()`                                                   | `Ratio`      | In lowest terms.                                                   |
| `isZero()`                                                    | `bool`       |                                                                    |
| `isNegative()`                                                | `bool`       | A negative rate is legal.                                          |
| `isPositive()`                                                | `bool`       |                                                                    |
| `isEqualTo(Percentage $other)`                                | `bool`       | Arithmetic, so the scale plays no part.                            |
| `isLessThan(Percentage $other)`                               | `bool`       |                                                                    |
| `isGreaterThan(Percentage $other)`                            | `bool`       |                                                                    |
| `isLessThanOrEqualTo(Percentage $other)`                      | `bool`       |                                                                    |
| `isGreaterThanOrEqualTo(Percentage $other)`                   | `bool`       |                                                                    |
| `toString()`                                                  | `string`     | `12.5%`.                                                           |
| `equals(Percentage $other)`                                   | `bool`       | Structural, so `'10'` does **not** equal `'10.0'`.                 |
| `hashCode()`                                                  | `string`     | Agrees with `equals`.                                              |
| `jsonSerialize()`                                             | `string`     | A JSON string, `12.5%`, which reads back through `of`.             |

Rates above one hundred and below zero are legal, because a one hundred and fifty percent increase and a negative growth
rate are both real.

<div id='ratio'></div>

### Ratio

An exact proportion between two quantities, written antecedent to consequent.

```php
<?php

declare(strict_types=1);

use TinyBlocks\Math\Ratio;
use TinyBlocks\Math\RoundingMode;

Ratio::of(antecedent: 16, consequent: 9)
    ->toPercentage(scale: 2, rounding: RoundingMode::HalfEven)
    ->toString();
# 177.78%
```

| Method                                                 | Returns       | Notes                                                   |
|--------------------------------------------------------|---------------|---------------------------------------------------------|
| `of(int\|string $antecedent, int\|string $consequent)` | `Ratio`       | Reduced on construction.                                |
| `from(string $value)`                                  | `Ratio`       | Reads the `16:9` form back.                             |
| `between(Number $antecedent, Number $consequent)`      | `Ratio`       | Exact, so no scale is involved.                         |
| `applyTo(Number $amount)`                              | `BigRational` | Exact.                                                  |
| `inverted()`                                           | `Ratio`       | Swaps the terms.                                        |
| `antecedent()`                                         | `BigInteger`  | Carries the sign.                                       |
| `consequent()`                                         | `BigInteger`  | Always positive.                                        |
| `toPercentage(int $scale, RoundingMode $rounding)`     | `Percentage`  |                                                         |
| `toBigRational()`                                      | `BigRational` |                                                         |
| `toString()`                                           | `string`      | `16:9`.                                                 |
| `equals(Ratio $other)`                                 | `bool`        | Structural, and a ratio is always in lowest terms.      |
| `hashCode()`                                           | `string`      | Agrees with `equals`.                                   |
| `jsonSerialize()`                                      | `string`      | A JSON string, `16:9`, which reads back through `from`. |

<div id='bigdecimals'></div>

### BigDecimals

An immutable, ordered collection of decimals. It carries the weights handed to `allocate` and the parts it produces.

```php
<?php

declare(strict_types=1);

use TinyBlocks\Math\BigDecimal;
use TinyBlocks\Math\BigDecimals;

BigDecimal::of(value: '100.00')
    ->allocate(scale: 2, weights: BigDecimals::of('1', '1', '1'))
    ->sum()
    ->toString();
# 100.00
```

| Method                        | Returns            | Notes                                                   |
|-------------------------------|--------------------|---------------------------------------------------------|
| `of(string\|int ...$values)`  | `BigDecimals`      | `BigDecimals::of('1', '1', '1')`.                       |
| `from(BigDecimal ...$values)` | `BigDecimals`      |                                                         |
| `sum()`                       | `BigDecimal`       | Exact, at the largest scale in the set.                 |
| `all()`                       | `list<BigDecimal>` | In order.                                               |
| `count()`                     | `int`              | The collection is `Countable`.                          |
| `getIterator()`               | `Traversable`      | The collection is iterable with `foreach`.              |
| `jsonSerialize()`             | `list<string>`     | A JSON array of strings, which reads back through `of`. |

<div id='roundingmode'></div>

### RoundingMode

Eight cases. A mode decides one thing: whether the discarded fraction pushes the kept digit away from zero, given how
that fraction compares with one half, the sign of the value, and the parity of the digit being kept. That is the
definition Java's `RoundingMode` javadoc gives each constant, and it is what `roundsAwayFromZero()` implements. The
table below rounds `-2.345` to two decimal places, and matches Java's published rounding table.

```php
<?php

declare(strict_types=1);

use TinyBlocks\Math\BigDecimal;
use TinyBlocks\Math\RoundingMode;

BigDecimal::of(value: '-2.345')->toScale(scale: 2, rounding: RoundingMode::HalfEven)->toString();
# -2.34
```

| Case       | Backing value | Native equivalent  | Result  |
|------------|---------------|--------------------|---------|
| `Up`       | `up`          | `AwayFromZero`     | `-2.35` |
| `Down`     | `down`        | `TowardsZero`      | `-2.34` |
| `Floor`    | `floor`       | `NegativeInfinity` | `-2.35` |
| `HalfUp`   | `half-up`     | `HalfAwayFromZero` | `-2.35` |
| `Ceiling`  | `ceiling`     | `PositiveInfinity` | `-2.34` |
| `HalfOdd`  | `half-odd`    | `HalfOdd`          | `-2.35` |
| `HalfDown` | `half-down`   | `HalfTowardsZero`  | `-2.34` |
| `HalfEven` | `half-even`   | `HalfEven`         | `-2.34` |

`fromNativeRoundingMode()` and `toNativeRoundingMode()` convert to and from PHP's native
`RoundingMode` enum, for consumers holding one from configuration or `Intl`. The library does not use them internally,
so a replacement calculation backend is never bypassed. There is no case meaning "do not round": that path is
`toScaleExact()` and `toDecimalExact()`, which raise instead of guessing.

<div id='failures'></div>

### Failures

Every failure implements `MathFailure`, so one catch clause covers the library. The interface exists because PHP splits
`Error` and `Exception` at the root and a zero divisor genuinely belongs under `DivisionByZeroError`.

```php
<?php

declare(strict_types=1);

use TinyBlocks\Math\BigDecimal;
use TinyBlocks\Math\Exceptions\MathFailure;

try {
    BigDecimal::of(value: '1.00')->dividedBy(divisor: BigDecimal::zero());
} catch (MathFailure $failure) {
    $failure->getMessage();
    # Cannot divide <1> by zero.
}
```

| Class                    | Extends                    | Raised when                                             |
|--------------------------|----------------------------|---------------------------------------------------------|
| `NumberNotWellFormed`    | `InvalidArgumentException` | A literal is not a number, the empty string included.   |
| `DivisionByZero`         | `DivisionByZeroError`      | A zero divisor, denominator, or reciprocal.             |
| `NonTerminatingDecimal`  | `DomainException`          | An exact decimal was demanded of a repeating expansion. |
| `InexactConversion`      | `DomainException`          | An exact conversion would discard digits.               |
| `ScaleOutOfRange`        | `InvalidArgumentException` | A scale is negative or beyond the supported range.      |
| `NegativeExponent`       | `InvalidArgumentException` | An integer or decimal was raised to a negative power.   |
| `ExponentOutOfRange`     | `InvalidArgumentException` | An exponent is beyond the supported magnitude.          |
| `NegativeRoot`           | `DomainException`          | The square root of a negative value.                    |
| `NegativeWeight`         | `InvalidArgumentException` | An allocation weight is negative.                       |
| `BaseOutOfRange`         | `InvalidArgumentException` | A positional base is outside 2 to 36.                   |
| `IntegerOverflow`        | `OverflowException`        | A conversion leaves the native integer or float range.  |
| `CalculatorNotAvailable` | `RuntimeException`         | The calculation backend cannot run in this process.     |

`NonTerminatingDecimal` and `InexactConversion` are separate on purpose. The first cannot be fixed by asking for more
digits, the second can.

<div id='calculation-backend'></div>

### Calculation backend

`Calculator` is the integer engine every value type runs on. The contract is integer only, because that is the boundary
every candidate backend shares: GMP has no fractional type, and a decimal is an integer plus a scale. Scale bookkeeping
therefore stays inside the library and two backends cannot disagree about a result.

Two backends ship. Resolution takes the first that can run: BCMath when the extension is loaded, otherwise a pure PHP
backend that needs nothing beyond 64-bit integers. The pure PHP backend is verified against libbcmath over a corpus
that crosses every limb boundary and both signs, so the two agree digit for digit and the extension is a speed
decision rather than a correctness one.

The gap is worth planning for. Measured on the project image, 2000 scale-2 money operations take about 280 ms on BCMath
and about 3.4 s on the pure PHP backend, and 200 exact divisions take about 60 ms against about 15 s. Division is the
widest gap because each quotient digit costs a full-width multiply and compare. Install `ext-bcmath` on any host that
does real volume, and treat the pure PHP backend as the guarantee that the library still runs where you cannot.

A registered backend always wins over the resolved one, and it is checked when it is registered rather than when it is
used, so a bootstrap mistake surfaces at bootstrap. When a GMP backend is added it goes at the front.

A backend implements nine methods. Every operand is a plain decimal integer string: an optional leading minus, then
digits, with no leading zero and no decimal point.

| Method                                              | Returns  | Notes                                         |
|-----------------------------------------------------|----------|-----------------------------------------------|
| `add(string $left, string $right)`                  | `string` |                                               |
| `subtract(string $minuend, string $subtrahend)`     | `string` |                                               |
| `multiply(string $left, string $right)`             | `string` |                                               |
| `quotient(string $numerator, string $denominator)`  | `string` | Truncated toward zero.                        |
| `remainder(string $numerator, string $denominator)` | `string` | Sign follows the numerator.                   |
| `power(string $base, int $exponent)`                | `string` | The exponent is never negative.               |
| `squareRoot(string $radicand)`                      | `string` | Truncated. The radicand is never negative.    |
| `compare(string $left, string $right)`              | `int`    | Exactly `-1`, `0`, or `1`.                    |
| `isAvailable()`                                     | `bool`   | Whether this backend can run in this process. |

`Calculators` is the resolution surface.

| Method                             | Returns      | Notes                                                   |
|------------------------------------|--------------|---------------------------------------------------------|
| `active()`                         | `Calculator` | Resolves on first call, then caches.                    |
| `register(Calculator $calculator)` | `void`       | Bootstrap only. Raises when the backend is unavailable. |
| `reset()`                          | `void`       | Returns to automatic resolution.                        |

```php
Calculators::register(calculator: new GmpCalculator());
```

`register` is bootstrap-only. What it replaces is a stateless, pure engine, so a replacement changes how fast a result
is produced and never what the result is.

<div id='faq'></div>

## FAQ

### 01. Why does division return a BigRational instead of a BigDecimal?

Because the exact answer always exists as a fraction, and hiding that costs the caller something either way. Libraries
that return a decimal must round silently, demand a scale at every division site, or raise when the quotient repeats. A
fraction is none of those: `dividedBy` has one signature on all three numeric types, takes one argument, and raises only
on a zero divisor. You leave exactness behind when you are ready, by naming a scale and a rounding mode, or by asking
for
`toDecimalExact()` and handling the case where no exact decimal exists.

### 02. Why is `equals` different from `isEqualTo`?

`isEqualTo` is arithmetic and lives on `Number`, so `1.0` and `1.00` are equal and a `BigDecimal` can be compared with
a `BigInteger`. `equals` is structural and lives on each concrete type, typed to that exact type, so the same pair is
not equal because the scales differ and a cross-type call does not compile. Both notions are useful and both are wrong
as the only one on offer, so each has its own name.

> Oracle, *java.math.BigDecimal Javadoc* (Oracle, 2024), "Note: this class has a natural ordering
> that is inconsistent with equals".

### 03. Why is scale part of the value at all?

Because `USD 3.00` is not `USD 3`. A model based on significant digits cannot express the difference, and loses it on
ordinary addition: in decimal.js at `precision: 5`,
`new Decimal('100000').plus('0.0001')` is `100000`. Fixed scale survives addition, which is what money needs.

### 04. Why does `jsonSerialize` emit a string rather than a number?

A JSON number is read back as an IEEE-754 double by every JavaScript consumer, which discards exactly what this library
preserves. `json_encode(BigDecimal::of(value: '1.50'))` is `"1.50"`.

### 05. Why is there no `__toString`?

The ecosystem's `phpcs` ruleset places magic methods after every other method, while the member ordering convention
places methods by name length. The two cannot both be satisfied for
`__toString`, so the magic method is absent and `toString()` is the single canonical string form.

### 06. Why does `fromFloat` exist if floats are the problem?

Because real programs receive floats from JSON and from other libraries, and refusing them only moves the conversion
somewhere less careful. It is the one lossy entry point, it is named so, and it reads the shortest decimal that
round-trips to the same float, so `0.1` becomes `'0.1'` rather than its exact binary expansion. Prefer a string literal
whenever one is available.

<div id='license'></div>

## License

Math is licensed under [MIT](LICENSE).

<div id='contributing'></div>

## Contributing

Please follow the [contributing guidelines](https://github.com/tiny-blocks/tiny-blocks/blob/main/CONTRIBUTING.md) to
contribute to the project.
