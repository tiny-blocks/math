# tiny-blocks/math 2.0.0 design proposal

Date: 2026-08-13. Status: approved and implemented. Supersedes the 1.x/3.x public surface entirely.

## 1. Why 2.0 exists

The current library is not arbitrary precision. Three of its core operations route through `float`,
and every comparison uses PHP's string operators. Both defects were reproduced in this session
against the project's own tooling image.

| Defect | Site | Reproduction | Result |
|---|---|---|---|
| Comparison loses precision past 53 bits. | `Internal/Number::isLessThan` and siblings, which use `<`, `>`, `==` on strings. | `'0.10000000000000000001' == '0.10000000000000000002'` | `true` in PHP, while `bccomp(..., 30)` returns `-1`. |
| Rounding routes through `float`. | `RoundingMode::round`, which calls `toFloat()`. | `(string)(float)'12345678901234567890.5'` | `'1.2345678901235E+19'`, against `'12345678901234567890'` from `BcMath\Number::round`. |
| Absolute value routes through `float`. | `Internal/BigNumberBehavior::absolute`, which calls `abs((float)...)`. | Same mantissa ceiling as above. | Silent loss. |
| Scale change truncates and can fault. | `Internal/Scale::numberWithScale`, which slices the fractional digits and indexes `$result[1]`. | A value with no fractional part. | Truncation instead of rounding, plus an undefined index. |
| Sign subtypes break substitutability. | `PositiveBigDecimal::add` returns through `static::fromString`. | Adding a larger negative addend. | The invariant of the subtype rejects the result of its own operation. |

Beyond the defects, the surface is narrower than what PHP developers already reach for. There are
four rounding modes, all of them half modes, so directed rounding (floor, ceiling, truncate) is
unreachable. There is no integer type, no rational type, and no percentage type. There is no
`Stringable`, no `JsonSerializable`, and no equality contract.

## 2. What PHP 8.5 changed under the library's feet

Every fact in this section was measured in this session by running probes in the project's image,
not read from documentation.

`BcMath\Number` ships with the bundled `bcmath` extension since PHP 8.4 and is present in the image.
It is `final readonly`, implements `Stringable`, and exposes `public string $value` and
`public int $scale`. Its methods are `__construct(string|int)`, `add`, `sub`, `mul`, `div`, `mod`,
`divmod`, `powmod`, `pow`, `sqrt`, `floor`, `ceil`, `round`, `compare`, `__toString`, `__serialize`,
`__unserialize`. Binary operations take `BcMath\Number|string|int $num, ?int $scale = null`.

Native `RoundingMode` (a pure enum, PHP 8.4) has eight cases: `HalfAwayFromZero`, `HalfTowardsZero`,
`HalfEven`, `HalfOdd`, `TowardsZero`, `AwayFromZero`, `NegativeInfinity`, `PositiveInfinity`. All
eight were verified exact at scale 2 on `0.995`, `-0.995`, `0.125`, `-0.125`, and `sqrt(2)` was
verified exact at scale 100, 1000, and 10000.

Measured scale propagation: `add` and `sub` give `max(scaleA, scaleB)`, `mul` gives
`scaleA + scaleB`, and `div` computes at `scaleA + 10` (the divisor's scale is ignored) then strips
trailing zeros, so `1/7` gives scale 10, `1.0/7` gives scale 11, and `1/2048` truncates to
`0.0004882812`.

Measured failure modes: `div` and `mod` by zero raise `DivisionByZeroError`. `'1e3'`, `'1E-3'`,
`'1_000'`, `'abc'`, and `'1/2'` all raise `ValueError`. `'.5'` and `'+5'` are accepted. The empty
string silently yields `0`. `pow` rejects a fractional exponent. `sqrt(-1)` raises `ValueError`.
`json_encode` emits `{"value":"1.50","scale":2}`, not a numeric string. `compare('1.0', '1.00')`
returns `0`, so comparison is numeric rather than lexical. Operator overloading works for
`+ - * / **` and for `== < >`.

Performance, 200000 iterations per case, script `backend-bench.php`, run through the project's
tooling image with `php -d memory_limit=1G backend-bench.php`:

```
PHP 8.5.5  iterations=200000
bcmath=1 gmp=0

--- addition ---
bcadd(string, string, scale)                       333.22 ms
BcMath\Number->add(BcMath\Number) [fresh objects]  699.49 ms
BcMath\Number->add(BcMath\Number) [reused]         342.38 ms
BcMath\Number + BcMath\Number [operator]           164.69 ms

--- multiplication ---
bcmul(string, string, scale)                       856.75 ms
BcMath\Number->mul(BcMath\Number) [reused]         490.01 ms

--- construction cost ---
new BcMath\Number(string)                          419.30 ms
preg_match numeric validation                      604.87 ms

--- big integer work (256-bit factorial-ish chain) ---
bcmul chain 1..300                                 187.05 ms
BcMath\Number->mul chain 1..300                    160.89 ms
```

Two readings drive the design. `BcMath\Number` beats the raw `bc*` string functions only when the
operand stays parsed between calls (342 ms against 333 ms for addition, and 490 ms against 857 ms
for multiplication), and loses when each call starts from strings (699 ms against 333 ms). The
calculation backend's contract is string in and string out, so it never reuses an operand, and
section 15 records the consequence. And constructing a `BcMath\Number` costs less than the
`preg_match` the 1.x `Internal\Number` runs on every instance, so the 1.x regex is a net loss
before any arithmetic happens.

Rational arithmetic was also measured viable without GMP. A Euclidean gcd over `BcMath\Number`
costs about 7.6 microseconds on 30-digit operands, and detecting a terminating decimal expansion
(strip factors 2 and 5, check the remainder is 1) is exact and cheap. Script
`rational-viability.php`, same command shape.

**`ext-gmp` is not present in the project's image.** This is measured, and it constrains section 7.

## 3. Comparison of the references

Every row below is backed by a source fetched or a file read in this session. Sources are listed in
section 12.

| | brick/math 0.19.1 | ext-decimal 2.0.1 | BcMath\Number (PHP 8.5) | bcmath functions | maba/math | Java BigDecimal (21) | Python decimal | decimal.js 10.6.0 |
|---|---|---|---|---|---|---|---|---|
| **Type surface** | `BigNumber` (abstract), `BigInteger`, `BigDecimal`, `BigRational`, `RoundingMode`, 11 exceptions. | `Decimal\Number` (abstract), `Decimal\Decimal`, `Decimal\Rational`. | One class. | None, strings only. | No value object at all, four service interfaces over raw strings. | `BigDecimal`, `BigInteger`, `MathContext`, `RoundingMode`. | `Decimal`, `Context`. | `Decimal` constructor plus clones. |
| **Construction** | `of()` dispatches by string shape, `float` rejected, plus `fromFloatExact` and `fromFloatShortest`. Constructors `protected`. | Private constructors, `valueOf()`. | Public constructor taking `string\|int`. Empty string yields 0. | Any string matching `/^[+-]?[0-9]*(\.[0-9]*)?$/`, which also accepts `''`, `'+'`, and `'.'`. | Raw strings, regex validated twice per operation. | Public `BigDecimal(double)` kept despite its own javadoc advising against it. | `Decimal(3.14)` silently ingests a float by default. | `new Decimal(0.7 + 0.1)` yields `'0.7999999999999999'`. |
| **Precision model** | Fixed scale per instance. No context, no ambient default. | Significant figures, default 34, propagated as the **minimum** of the two operands. | Fixed scale per instance, per-operation propagation lattice. | Fixed scale from the process-global `bcscale()` / `bcmath.scale`, default `0`. | Single scale fixed at construction of the service. | Fixed scale on the value, significant digits in a per-call `MathContext`. | Significant digits in a thread-global ambient `Context`, default 28. | Significant digits in a constructor-global `precision`, default 20. |
| **Rounding modes** | 11, own pure enum, default `Unnecessary` everywhere. | 9, integer class constants, default `HALF_EVEN`, arithmetic rounding hardcoded and not configurable. | 8, the native pure enum, default `HalfAwayFromZero`. | 8 through `bcround`, only PHP 8.4+, and arithmetic itself truncates. | An unvalidated `int`, unknown values silently fall through. | 8, own enum, plus 8 deprecated `int` constants kept forever. | 8, bare strings, no type. | 10, bare integers typed as a union. |
| **Immutability** | `readonly` classes. | `final` classes, embedded `mpd_t`. | `final readonly`. | Not applicable. | Services are mutable, values are strings. | Immutable, but `setScale` is named as a mutator. | Immutable values, mutable ambient context. | Properties are writable at runtime, `readonly` only in the type definitions. |
| **Error model** | `interface MathException extends Throwable`, 10 final classes, named static constructors. | No library exception type. Nine failure modes across stock SPL classes. Precision overflow is an `E_WARNING`, and the computation continues. | `ValueError` and `DivisionByZeroError`, both extending `Error`. | Same two, distinguishable only by message. | `MathException` does not extend `Throwable`. The division-by-zero guard is dead code on PHP 8. | Everything is `ArithmeticException`, separable only by message string. | Signals plus traps. Untrapped conditions return `NaN` or `Infinity`. | Bare `Error` with a `[DecimalError]` string prefix. |
| **Backend selection** | `CalculatorRegistry`, GMP then BCMath then native, in a mutable public static, `@internal`. GMP and BCMath appear nowhere in `composer.json`. | libmpdec only. | libbcmath only. | libbcmath or libgmp, not selectable. | Manual injection, one implementation, `ext-bcmath` only suggested. | Not applicable. | C `_decimal` or pure Python, with observable behavior differences. | Not applicable. |
| **Formatting** | One string form. `jsonSerialize` returns a JSON string. No formatting layer. | `toString`, `toFixed`, `toScientific`. `toScientific`'s declared argument does not work. | `__toString` only. `json_encode` leaks the internal shape. | None. | A separate formatter service. | `toString` emits scientific notation for `0.00123`. `toPlainString` is the secondary method. | `str` emits `2E+2` for `Decimal('200').normalize()`. | Notation flips on global `toExpNeg`/`toExpPos`. |
| **What it gets wrong** | `dividedBy` has three incompatible signatures across the three types and is absent from the base, so generic code cannot divide. `1.0` and `1.00` are `isEqualTo` but have no `equals`. Permanently 0.x. | Documentation describes v1 while PECL ships v2 with inverted precision propagation. Comparing to `int` or `float` routes through `double`. A failed comparison is indistinguishable from "greater than". | No `abs`, no `negate`, no predicates, no `equals`. Not `JsonSerializable`. The `+10` division constant is arbitrary. Empty string is 0. | Ambient global scale. Silent truncation with no signal. Three different modulo sign conventions across `bcmod`, `gmp_mod`, and `gmp_div_r`. | `abs('5')` and `abs('-5')` return strings of different scale, so `===` on library output is wrong. Dead since 2014. | `equals` and `compareTo` disagree by design. `divide(divisor, mode)` silently takes the receiver's scale, so `2.0/3` and `2.00/3` give different answers. | Global mutable context makes addition non-associative: with `prec = 3`, `3.104 + 2.104` is `5.21` but `3.104 + 0 + 2.104` is `5.20`. | Addition is lossy: at `precision: 5`, `100000 + 0.0001` is `100000`. Trailing zeros unrepresentable, so `USD 3.00` cannot round-trip. |

Adoption, read off Packagist on 2026-08-13: `brick/math` 571,930,439 downloads, `moneyphp/money`
96,428,226, `brick/money` 43,814,758, `moontoast/math` 26,470,797 (abandoned, replaced by
brick/math), `litipk/php-bignumbers` 2,187,334 (abandoned), `php-decimal/php-decimal` 1,134,136,
`maba/math` 72,994 (last release 2014), `tiny-blocks/math` 22,665.

### What none of them does well

1. **Every reference puts precision policy in the wrong place.** Python, decimal.js, and ext-decimal
   put it in ambient global state, which makes arithmetic depend on code you did not write. Java
   puts it in a per-call `MathContext` you must thread through every link of a chain. brick/math
   puts it in a mandatory positional `$scale` on one method of one type. bcmath puts it in a
   process-global INI setting that defaults to `0`. Nobody makes the exact answer the default and
   the rounding an explicit, local, typed decision.
2. **Nobody makes inexactness a type.** brick/math and Java both signal it with an exception, and
   both collapse two different failures into one class: a quotient that repeats forever (which the
   caller cannot fix) and a quotient that terminates but needs more digits (which the caller fixes
   by asking for more digits). Telling them apart requires matching message strings.
3. **Nobody separates representation equality from value equality by name.** Java has `equals`
   against `compareTo` and documents the trap three times. brick/math has only `isEqualTo`, so
   `1.0` and `1.00` are equal but not substitutable. ext-decimal orders values by their precision
   field while its own source comment claims precision is ignored.
4. **No PHP library models a percentage or a proportion.** Every business codebase writes
   `$amount * $rate / 100` by hand, with the rounding decision made implicitly by whatever the
   division did.
5. **Nobody exploits what PHP 8.5 already ships.** `BcMath\Number` is faster than the `bc*` string
   functions it replaces, and the eight native rounding modes are exact. A PHP library written in
   2026 that reimplements decimal string plumbing is doing work the runtime already did.

## 4. Design stance

Four rules decide every call below.

1. **Arithmetic never rounds.** `plus`, `minus`, and `multipliedBy` are exact by construction.
   Division returns a `BigRational`, which is exact for every pair of operands. No operation in the
   library silently loses a digit, and none throws because it would have to.
2. **Rounding is an explicit, local, typed conversion.** It happens only when the caller names both
   a scale and a `RoundingMode`. There is no default rounding mode, no ambient context, and no
   per-value policy to forget.
3. **Inexactness is a type, not an exception.** Where the references throw
   `RoundingNecessaryException` or `ArithmeticException`, this library hands back a `BigRational`
   that holds the exact answer. The caller decides when to leave exactness behind.
4. **Delegate to the runtime.** `BcMath\Number` does the arithmetic and the native `RoundingMode`
   performs the rounding. The library owns the vocabulary and the value semantics, not the digit
   shuffling.

## 5. Type surface

Namespace `TinyBlocks\Math`. Layout follows the architecture rule: contracts, public enums, and
thin value objects at the `src/` root, all algorithms in `src/Internal/`, public exceptions in
`src/Exceptions/`.

```
src/
├── Number.php                      # shared contract
├── BigInteger.php
├── BigDecimal.php
├── BigDecimals.php                 # collection, allocation weights and allocation result
├── BigRational.php
├── Percentage.php
├── Ratio.php
├── RoundingMode.php                # public enum
├── Calculator.php                  # backend contract
├── Calculators.php                 # backend resolution, static surface
├── Exceptions/
│   ├── MathFailure.php             # marker interface over Error and Exception lineages
│   ├── CalculatorNotAvailable.php
│   ├── DivisionByZero.php
│   ├── InexactConversion.php
│   ├── IntegerOverflow.php
│   ├── NegativeRoot.php
│   ├── NegativeWeight.php
│   ├── NonTerminatingDecimal.php
│   ├── NumberNotWellFormed.php
│   └── ScaleOutOfRange.php
└── Internal/
    ├── Allocation.php              # largest remainder distribution
    ├── BcMathCalculator.php
    ├── Digits.php                  # normalized decimal string, wraps BcMath\Number
    ├── Fraction.php                # gcd, lowest terms, terminating-expansion test
    ├── NumberComparison.php        # trait carrying the nine shared predicates
    └── Scale.php                   # scale arithmetic and bounds
```

### 5.1 `Number` (interface)

The shared contract. Extends `Stringable`, `JsonSerializable`, and `TinyBlocks\Vo\ValueObject`.
It exists so a consumer can write a function over "any number this library produces" without
caring which of the three concrete types arrived. brick/math uses an abstract class for this and
has to carry three `protected` constructor proxies to work around PHP's lack of friend access. An
interface plus composition avoids that entirely.

| Method | Returns | Notes |
|---|---|---|
| `compareTo(Number $other)` | `int` | `-1`, `0`, or `1`. Numeric, never lexical. |
| `isZero()` | `bool` | |
| `isPositive()` | `bool` | Strictly greater than zero. |
| `isNegative()` | `bool` | Strictly less than zero. |
| `isEqualTo(Number $other)` | `bool` | **Value** equality. `1.0` equals `1.00`. |
| `isLessThan(Number $other)` | `bool` | |
| `isGreaterThan(Number $other)` | `bool` | |
| `isLessThanOrEqualTo(Number $other)` | `bool` | |
| `isGreaterThanOrEqualTo(Number $other)` | `bool` | |
| `negated()` | `Number` | |
| `absolute()` | `Number` | Exact, never through `float`. |
| `toBigRational()` | `BigRational` | Total on all three types. |
| `toString()` | `string` | Round-trips through the type's own `of()`. |
| `equals(ValueObject $other)` | `bool` | **Representation** equality from `tiny-blocks/value-object`. `1.0` does not equal `1.00`. |
| `hashCode()` | `string` | Agrees with `equals`, per the ecosystem contract. |
| `jsonSerialize()` | `string` | A JSON string, never a JSON number. |

The `isEqualTo` against `equals` split is deliberate and is the answer to Java's most documented
trap. The names carry the difference: `isEqualTo` is arithmetic, `equals` is the ecosystem's
structural contract. Both are documented on the interface with the `1.0` against `1.00` example.

### 5.2 `BigInteger`

Exists because integer problems (identifiers, counters, combinatorics, modular arithmetic) have no
scale, and forcing them through a decimal type means carrying a scale that is always zero and
paying for fractional handling that never runs.

| Member | Signature | Complexity |
|---|---|---|
| `of` | `static of(string\|int $value): BigInteger` | O(n) in digits. |
| `zero` | `static zero(): BigInteger` | O(1). |
| `one` | `static one(): BigInteger` | O(1). |
| `fromBase` | `static fromBase(string $value, int $base): BigInteger` | O(n) in digits. |
| `plus` | `plus(BigInteger $addend): BigInteger` | O(n). |
| `minus` | `minus(BigInteger $subtrahend): BigInteger` | O(n). |
| `multipliedBy` | `multipliedBy(BigInteger $multiplier): BigInteger` | O(n·m) via libbcmath. |
| `dividedBy` | `dividedBy(BigInteger $divisor): BigRational` | Exact. Throws `DivisionByZero`. |
| `quotient` | `quotient(BigInteger $divisor): BigInteger` | Truncated toward zero. |
| `remainder` | `remainder(BigInteger $divisor): BigInteger` | Sign follows the dividend, matching `bcmod`. |
| `modulo` | `modulo(BigInteger $modulus): BigInteger` | Always non-negative, matching `gmp_mod`. |
| `power` | `power(int $exponent): BigInteger` | Rejects a negative exponent. |
| `squareRoot` | `squareRoot(): BigInteger` | Floor. Throws `NegativeRoot`. |
| `greatestCommonDivisor` | `greatestCommonDivisor(BigInteger $other): BigInteger` | O(log n) Euclidean, measured at 7.6 microseconds on 30-digit operands. |
| `isEven` | `isEven(): bool` | O(1). |
| `isOdd` | `isOdd(): bool` | O(1). |
| `toBigDecimal` | `toBigDecimal(): BigDecimal` | Scale 0. |
| `toBase` | `toBase(int $base): string` | |
| `toInt` | `toInt(): int` | Throws `IntegerOverflow` outside the native range. |

Plus the full `Number` contract. `remainder` and `modulo` are separate methods with documented sign
conventions, because the research found three different conventions across `bcmod`, `gmp_mod`, and
`gmp_div_r`, and hiding that behind one name is how backend swaps change answers.

### 5.3 `BigDecimal`

The primary type. Representation is an unscaled `BigInteger` plus a non-negative `int` scale, the
same model Java and brick/math use and the one `BcMath\Number` exposes.

| Member | Signature | Result scale |
|---|---|---|
| `of` | `static of(string\|int $value): BigDecimal` | The scale of the literal. Exponent notation accepted and normalized. |
| `zero` | `static zero(): BigDecimal` | 0. |
| `one` | `static one(): BigDecimal` | 0. |
| `ofUnscaledValue` | `static ofUnscaledValue(BigInteger $unscaled, int $scale): BigDecimal` | `$scale`. |
| `fromFloat` | `static fromFloat(float $value): BigDecimal` | The shortest decimal that round-trips to the same float. The only lossy entry point, and named so. |
| `plus` | `plus(BigDecimal $addend): BigDecimal` | `max(a, b)`. |
| `minus` | `minus(BigDecimal $subtrahend): BigDecimal` | `max(a, b)`. |
| `multipliedBy` | `multipliedBy(BigDecimal $multiplier): BigDecimal` | `a + b`. |
| `dividedBy` | `dividedBy(BigDecimal $divisor): BigRational` | Exact. Throws `DivisionByZero`. |
| `power` | `power(int $exponent): BigDecimal` | `a × exponent`. |
| `squareRoot` | `squareRoot(int $scale, RoundingMode $rounding): BigDecimal` | `$scale`. Throws `NegativeRoot`. |
| `toScale` | `toScale(int $scale, RoundingMode $rounding): BigDecimal` | `$scale`. |
| `toScaleExact` | `toScaleExact(int $scale): BigDecimal` | `$scale`. Throws `InexactConversion` when digits would be lost. |
| `withoutTrailingZeros` | `withoutTrailingZeros(): BigDecimal` | Minimal scale representing the same value. |
| `scale` | `scale(): int` | |
| `unscaledValue` | `unscaledValue(): BigInteger` | |
| `integralPart` | `integralPart(): BigInteger` | Truncated toward zero. |
| `fractionalPart` | `fractionalPart(): BigDecimal` | Same scale, same sign as the receiver. |
| `allocate` | `allocate(BigDecimals $weights, int $scale): BigDecimals` | `$scale`. Largest remainder. The parts sum exactly to the receiver. |
| `toBigInteger` | `toBigInteger(): BigInteger` | Throws `InexactConversion` when the fractional part is non-zero. |
| `toFloat` | `toFloat(): float` | Throws on overflow, unlike brick/math which returns infinity. |

Plus the full `Number` contract. `toString` always emits plain positional notation, never
scientific. Java's canonical `toString` emits `1.23E-3` for `0.00123`, which is wrong for every
money use case, and forces `toPlainString` as a secondary method. This library has one string form
and it is the one you want.

There is no `dividedBy(divisor, scale, rounding)` overload. Division has one signature on all three
numeric types, takes one argument, and is total except for a zero divisor. This is the single
largest ergonomic gain over brick/math, where the same method has three incompatible signatures and
is absent from the shared base.

### 5.4 `BigRational`

Exists because it is what makes rule 1 possible. Without it, division must either round silently
(bcmath, decimal.js), demand a scale up front (brick/math), or throw (Java). With it, division has
an exact answer for every input, and the caller converts when ready.

Always stored in lowest terms with a strictly positive denominator.

| Member | Signature | Notes |
|---|---|---|
| `of` | `static of(string $value): BigRational` | Accepts `'3/4'`, `'0.75'`, and `'3'`. |
| `ofFraction` | `static ofFraction(BigInteger $numerator, BigInteger $denominator): BigRational` | Throws `DivisionByZero` on a zero denominator. |
| `zero` | `static zero(): BigRational` | |
| `one` | `static one(): BigRational` | |
| `plus` | `plus(BigRational $addend): BigRational` | Exact. |
| `minus` | `minus(BigRational $subtrahend): BigRational` | Exact. |
| `multipliedBy` | `multipliedBy(BigRational $multiplier): BigRational` | Exact. |
| `dividedBy` | `dividedBy(BigRational $divisor): BigRational` | Exact. Throws `DivisionByZero`. |
| `power` | `power(int $exponent): BigRational` | Negative exponents supported, unlike the other two types. |
| `reciprocal` | `reciprocal(): BigRational` | Throws `DivisionByZero` on zero. |
| `numerator` | `numerator(): BigInteger` | |
| `denominator` | `denominator(): BigInteger` | Always positive. |
| `hasTerminatingDecimal` | `hasTerminatingDecimal(): bool` | True when the reduced denominator is 2^a·5^b. Measured exact and cheap. |
| `toDecimal` | `toDecimal(int $scale, RoundingMode $rounding): BigDecimal` | |
| `toDecimalExact` | `toDecimalExact(): BigDecimal` | Throws `NonTerminatingDecimal` when the expansion repeats. |
| `toBigInteger` | `toBigInteger(): BigInteger` | Throws `InexactConversion` when the denominator is not 1. |
| `toFloat` | `toFloat(): float` | |

Plus the full `Number` contract. `toString` emits `3/4`, collapsing to `3` when the denominator is
1. `hasTerminatingDecimal` is the API brick/math lacks: there, the only way to ask is to call
`dividedByExact` and catch an exception whose two meanings differ only by message text.

### 5.5 `Percentage`

Exists because applying a rate to an amount is the most common decimal operation in business code
and no PHP arbitrary-precision library models it. Backed by a `BigDecimal` rate, so application to
an amount is exact: a percentage is a decimal, a decimal times a decimal is exact, and no rounding
decision is forced on the caller until presentation.

| Member | Signature | Notes |
|---|---|---|
| `of` | `static of(string\|int $value): Percentage` | `Percentage::of(value: '12.5')` is 12.5 percent. |
| `zero` | `static zero(): Percentage` | |
| `fromRatio` | `static fromRatio(Ratio $ratio, int $scale, RoundingMode $rounding): Percentage` | Requires a scale because a ratio may not terminate. |
| `applyTo` | `applyTo(BigDecimal $amount): BigDecimal` | Exact. Scale is `amount + rate + 2`. |
| `increase` | `increase(BigDecimal $amount): BigDecimal` | Exact. `amount + applyTo(amount)`. |
| `decrease` | `decrease(BigDecimal $amount): BigDecimal` | Exact. `amount - applyTo(amount)`. |
| `rate` | `rate(): BigDecimal` | `0.125` for 12.5 percent. |
| `toRatio` | `toRatio(): Ratio` | |
| `toString` | `toString(): string` | `12.5%`. |

Plus the comparison predicates of `Number`, typed against `Percentage`. Negative percentages and
percentages above 100 are legal, because a negative growth rate and a 150 percent increase are both
real. There is no invariant to enforce and therefore no exception.

### 5.6 `Ratio`

An exact proportion between two quantities, stored as a `BigRational` in lowest terms with a
`a:b` string form.

| Member | Signature | Notes |
|---|---|---|
| `of` | `static of(BigInteger\|int\|string $antecedent, BigInteger\|int\|string $consequent): Ratio` | Throws `DivisionByZero` on a zero consequent. |
| `between` | `static between(Number $antecedent, Number $consequent): Ratio` | Exact, no rounding decision. |
| `applyTo` | `applyTo(BigDecimal $amount): BigRational` | Exact, `amount × antecedent / consequent`. |
| `inverted` | `inverted(): Ratio` | |
| `antecedent` | `antecedent(): BigInteger` | |
| `consequent` | `consequent(): BigInteger` | |
| `toPercentage` | `toPercentage(int $scale, RoundingMode $rounding): Percentage` | |
| `toBigRational` | `toBigRational(): BigRational` | |
| `toString` | `toString(): string` | `16:9`. |

Over `BigRational` it adds the `a:b` string form, the `between` factory, and the semantic that it
is a relationship between two quantities rather than a quantity. `Ratio::between` is the one place
in the library where a proportion is derived from two arbitrary numbers without anyone having to
name a scale, because the result stays exact.

### 5.7 `BigDecimals`

An immutable, `Countable` collection of `BigDecimal`, wrapping a `Collectible` from
`tiny-blocks/collection`, in the same shape as `Timezones` in `tiny-blocks/time`. It is both the
input to allocation (the weights) and its output (the parts).

| Member | Signature | Notes |
|---|---|---|
| `of` | `static of(string\|int ...$values): BigDecimals` | `BigDecimals::of('1', '1', '1')`. |
| `from` | `static from(BigDecimal ...$values): BigDecimals` | |
| `sum` | `sum(): BigDecimal` | Exact. Scale is the maximum of the elements. Zero for an empty collection. |
| `all` | `all(): list<BigDecimal>` | |
| `count` | `count(): int` | |

Weights are `BigDecimal` rather than `BigInteger` so that a `1.5 : 2.5` split is expressible.

**Allocation.** `BigDecimal::allocate(BigDecimals $weights, int $scale)` computes each exact share
as a `BigRational`, truncates it toward negative infinity at `$scale`, then hands the leftover
minor units out one at a time in descending order of the discarded remainder, ties broken by
position. The result therefore sums to the receiver exactly, which plain rounding cannot guarantee:
`100.00` split three ways at scale 2 gives `33.34`, `33.33`, `33.33`, never three times `33.33`
with a cent missing. A negative weight raises `NegativeWeight`. Weights summing to zero raise
`DivisionByZero`. A weight of zero is legal and receives nothing.

No reference library in section 3 offers this at the number level. `moneyphp/money` offers it only
on `Money`, which forces a currency on a problem that does not need one.

### 5.8 `RoundingMode`

A string-backed enum owned by the library, with the eight cases below. Every case maps one to one
onto PHP's native `RoundingMode`, and the mapping was verified in this session, in both directions,
against `-2.345` at scale 2. The results match Java's published rounding table for the same input.

| Case | Backing value | Native equivalent | `round('-2.345', 2)` |
|---|---|---|---|
| `Up` | `up` | `AwayFromZero` | `-2.35` |
| `Down` | `down` | `TowardsZero` | `-2.34` |
| `Floor` | `floor` | `NegativeInfinity` | `-2.35` |
| `HalfUp` | `half-up` | `HalfAwayFromZero` | `-2.35` |
| `Ceiling` | `ceiling` | `PositiveInfinity` | `-2.34` |
| `HalfOdd` | `half-odd` | `HalfOdd` | `-2.35` |
| `HalfDown` | `half-down` | `HalfTowardsZero` | `-2.34` |
| `HalfEven` | `half-even` | `HalfEven` | `-2.34` |

| Member | Signature | Notes |
|---|---|---|
| `fromNativeRoundingMode` | `static fromNativeRoundingMode(NativeRoundingMode $mode): RoundingMode` | For consumers holding a native mode from configuration or `Intl`. |
| `toNativeRoundingMode` | `toNativeRoundingMode(): NativeRoundingMode` | The value each case owns, per the tell-don't-ask rule for enums. |

The names follow Java and brick/math (`Up`, `Down`, `Ceiling`, `Floor`, `Half*`) rather than the
native `AwayFromZero` and `PositiveInfinity` spelling, because that vocabulary is what the
literature on monetary rounding uses. The backing values are stable strings, so a rounding policy
survives a round trip through configuration or a database column. There is no `Unnecessary` case:
in this design nothing rounds implicitly, and the exact-or-fail path is `toScaleExact` and
`toDecimalExact`, which are methods rather than a mode meaning "do not round".

The file imports the native enum as `use RoundingMode as NativeRoundingMode;`. This was verified to
compile alongside the library's own `RoundingMode` declaration in the same file.

## 6. Precision and rounding

The precision model is **fixed scale carried on the value**, never significant digits. Significant
digits are what Python, decimal.js, and ext-decimal use, and all three demonstrate the same failure:
at `precision: 5`, decimal.js computes `100000 + 0.0001` as `100000`, silently. Money needs a scale
that survives addition, and a scale is what `USD 3.00` is.

Scale propagation, identical to Java's preferred-scale table and to the measured `BcMath\Number`
behavior, so nothing here will surprise a reader who knows either:

| Operation | Result scale |
|---|---|
| `plus`, `minus` | `max(a, b)` |
| `multipliedBy` | `a + b` |
| `power(n)` | `a × n` |
| `dividedBy` | Not applicable, the result is a `BigRational`. |
| `toScale(s, r)`, `toScaleExact(s)`, `squareRoot(s, r)` | `s` |
| `withoutTrailingZeros()` | The minimum representing the same value. |

Scale is bounded. `Internal\Scale` rejects a negative scale and a scale above libbcmath's ceiling
with `ScaleOutOfRange`. Scale arithmetic (`a + b` on multiply, `a × n` on power) is overflow-checked
and raises `ScaleOutOfRange` rather than producing a wrong scale, because brick/math's own
`Safe::mul` exists for exactly this reason.

**Rounding modes are `TinyBlocks\Math\RoundingMode`**, defined in section 5.8. Eight cases against
the four of 1.x, adding the directed modes (`Up`, `Down`, `Ceiling`, `Floor`) that monetary and tax
code needs and 1.x cannot express. The arithmetic is still the runtime's: each case resolves to the
native `RoundingMode` and `BcMath\Number::round` does the work, so the library owns the vocabulary
and not the digit handling. The translation table is eight rows, exhaustive by `match` over a
sealed enum, and covered in both directions by tests.

**When an operation cannot be represented exactly**, the library never guesses:

- Division always can be represented exactly, as a `BigRational`. Nothing is thrown and nothing
  is lost.
- `toDecimalExact()` on a repeating expansion throws `NonTerminatingDecimal`. The caller cannot fix
  this by asking for more digits.
- `toScaleExact()` on a value needing more digits, and `toBigInteger()` on a value with a fractional
  part, throw `InexactConversion`. The caller can fix this by asking for more digits or by rounding.
- Those two failures are separate classes. brick/math and Java both collapse them into one type,
  and section 3 records that the only way to tell them apart there is matching message strings.

## 7. Calculation backend

`Calculator` is a public interface at the `src/` root. It is the seam a consumer with `ext-gmp`
uses to accelerate the integer path, and the seam the library uses to keep `BcMath\Number` out of
the value objects.

```php
<?php

declare(strict_types=1);

interface Calculator
{
    public function isAvailable(): bool;

    public function add(string $augend, string $addend, int $scale): string;

    public function compare(string $left, string $right, int $scale): int;

    public function divide(string $dividend, string $divisor, int $scale): string;

    public function modulo(string $dividend, string $divisor, int $scale): string;

    public function multiply(string $multiplicand, string $multiplier, int $scale): string;

    public function power(string $base, int $exponent, int $scale): string;

    public function squareRoot(string $radicand, int $scale): string;

    public function subtract(string $minuend, string $subtrahend, int $scale): string;
}
```

The interchange format is a plain decimal string, which is what makes the seam portable and what
lets `BcMathCalculator` hand values straight to `BcMath\Number`.

**Selection rule.** Candidates are consulted in a fixed priority order, and the first whose
`isAvailable()` returns true wins: GMP, then BCMath, then a native PHP fallback. Resolution happens
once per process and the result is inspectable. When no candidate is available, the library raises
`CalculatorNotAvailable` at first use rather than fataling on an undefined function, which is what
`maba/math` does today.

**What 2.0 ships: `Internal\BcMathCalculator` only.** `ext-gmp` is not present in the tooling image
`make review` and `make tests` run in. A shipped
`GmpCalculator` would be code no test can execute, which means uncovered lines and unkillable
mutants, which fails `minMsi: 100` and `minCoveredMsi: 100`. The testing rule forbids
`@codeCoverageIgnore` and forbids suppressing mutants, and the tooling rule forbids lowering the
thresholds. The interface, the priority order, and the resolution are built regardless, so a
consumer who registers a GMP calculator gets it selected. Shipping one is a follow-up gated on the
CI image carrying the extension.

A native PHP fallback is likewise not shipped. `ext-bcmath` is already a hard requirement in
`composer.json`, a pure-PHP arbitrary-precision implementation is several hundred lines of
algorithmic code, and every line of it would need to be mutation-tested to 100 percent for a
platform configuration the ecosystem does not target.

**Override.** `Calculators` is a static-only surface at the `src/` root with a private constructor.

| Member | Signature | Notes |
|---|---|---|
| `use` | `static use(Calculator $calculator): void` | Bootstrap only. Replaces the resolved backend for the process. |
| `active` | `static active(): Calculator` | Resolves on first call and caches. Raises `CalculatorNotAvailable` when no candidate reports `isAvailable()`. |
| `reset` | `static reset(): void` | Returns to automatic resolution. Exists so a test can undo `use` without leaking into the next test. |

This is process-wide mutable state, and that is a real cost, the same one section 3 records against
brick/math's `CalculatorRegistry` and decimal.js's constructor globals. Three things bound it. The
state is a single stateless, pure collaborator rather than a precision policy, so swapping it cannot
change a result, only its speed. `active()` is inspectable, which brick/math's equivalent is not.
And `reset()` makes the override reversible, so a test that sets it can put it back.

## 8. Error model

Every failure is an exception. No typed results, no nullable returns, no error codes. The typed
result in this design is `BigRational`, which is a value, not an error channel.

`Exceptions\MathFailure` is an interface extending `Throwable`, implemented by every class below.
It exists because PHP splits `Error` and `Exception` at the root, and `DivisionByZero` genuinely
belongs under `DivisionByZeroError` while the rest belong under `Exception`. Without the marker, a
consumer cannot catch "anything this library threw" in one clause. This is brick/math's single best
idea and it is worth taking.

| Class | Extends | Raised when |
|---|---|---|
| `MathFailure` | `Throwable` (interface) | Never. It is the catch-all handle. |
| `NumberNotWellFormed` | `InvalidArgumentException` | A literal is not a number. Covers the empty string, which `BcMath\Number` silently reads as 0. |
| `DivisionByZero` | `DivisionByZeroError` | A zero divisor, a zero denominator, or the reciprocal of zero. |
| `NonTerminatingDecimal` | `DomainException` | An exact decimal was demanded of a repeating expansion. Unfixable by the caller. |
| `InexactConversion` | `DomainException` | An exact conversion would discard digits. Fixable by the caller. |
| `ScaleOutOfRange` | `InvalidArgumentException` | A negative scale, or a scale beyond the backend's ceiling, including one produced by scale propagation. |
| `NegativeRoot` | `DomainException` | The square root of a negative value. |
| `IntegerOverflow` | `OverflowException` | `toInt()` or `toFloat()` outside the native range. |
| `CalculatorNotAvailable` | `RuntimeException` | No backend satisfied the selection rule. |

Each class carries named static constructors in the ecosystem's idiom
(`InexactConversion::becauseScaleWouldDiscardDigits(...)`), so the throw sites are enumerable and
the message text lives beside the class. Messages carry the violating value.

## 9. Interop

**String.** `toString()` is the canonical form and round-trips through the type's own `of()`.
Positional notation always, never scientific. `__toString()` returns the same string.

**JSON.** `jsonSerialize()` returns a **string**, not a number. `json_encode(BigDecimal::of('1.50'))`
gives `"1.50"`. A JSON number would be parsed as an IEEE-754 double by every JavaScript consumer,
which throws away exactly what the library exists to preserve. `BcMath\Number` itself leaks
`{"value":"1.50","scale":2}` here, which is why the library never lets one reach a serializer.

**Comparison and equality.** `compareTo` is numeric and total across the three numeric types.
`isEqualTo` is numeric, so `1.0` and `1.00` are equal. `equals` is the structural contract from
`tiny-blocks/value-object`, so `1.0` and `1.00` are not equal, and `hashCode` agrees with `equals`.
Both are documented with that exact example on the `Number` interface.

**`tiny-blocks/currency`.** `Currency::getFractionDigits()` returns the ISO-4217 fraction digits,
which is precisely the scale argument `toScale` wants. The README shows the pairing, and the
libraries stay decoupled: no `Money` type, no dependency in either direction.

```php
$total->toScale(scale: Currency::BRL->getFractionDigits(), rounding: RoundingMode::HalfEven);
```

**`tiny-blocks/value-object`.** Added to `require` as `^5.0`. Every public numeric type implements
`ValueObject`. The trait is not used: it requires public properties, and the internal representation
is not part of the contract. `equals` and `hashCode` are implemented against the private state.

**`tiny-blocks/collection`.** Added to `require` as `^2.6`, for `BigDecimals`. The collection is
held privately and never leaks, so `Collectible` is not part of this library's contract.

These add two edges to the ecosystem dependency graph: `tiny-blocks/math` requires
`tiny-blocks/value-object`, and `tiny-blocks/math` requires `tiny-blocks/collection`. The meta
repository is not in this checkout, so the graph is not updated here and the new edges are reported
instead. Both packages are first-party and therefore exempt from the seven-day dependency cooldown.

## 10. Minimum PHP version

**`^8.5`.** Four reasons, in descending weight:

1. `BcMath\Number` and the native `RoundingMode` enum arrived in **8.4** and the design rests on
   both. Anything below 8.4 is impossible without reimplementing them.
2. The ecosystem floor is 8.5. `tiny-blocks/value-object`, `tiny-blocks/currency`, and
   `tiny-blocks/time` all require `^8.5`, and the tooling rule pins `require.php` to the canonical
   asset value. A library that pairs with them must not sit below them.
3. The project's Docker image is PHP 8.5.5, so 8.5 is what actually gets tested.

`#[\NoDiscard]`, which is 8.5, was applied to every public instance method in a first pass and then
removed: on a library where every method is pure and returns a new instance, the attribute lands on
all of them, and a hundred and three annotations buy a diagnostic that costs more in noise than the
mistake costs in practice.

## 11. Decisions that could reasonably have gone another way

Items 1 to 4 were settled by the user at the approval gate. The alternative each was weighed
against is recorded so a later reader knows the call was made rather than defaulted into.

1. **Division returns `BigRational`, not `BigDecimal`.** Settled. The alternative was brick/math's
   mandatory scale plus rounding mode on every division. Chosen because it makes division total and
   moves the rounding decision to the point of presentation. The cost is that `1/4` also comes back
   as a rational and needs `toDecimalExact()`.
2. **The library owns `TinyBlocks\Math\RoundingMode`.** Settled against using PHP's native enum
   directly. The cost is an eight-row translation table, exhaustive and tested in both directions.
   The gain is the `Ceiling`/`Floor`/`HalfUp` vocabulary the monetary literature uses, and stable
   backing strings that survive a database column.
3. **Only `BcMathCalculator` ships.** Settled against adding `ext-gmp` to the CI image first.
   Driven by the measured absence of the extension against the MSI 100 threshold.
4. **`Percentage`, `Ratio`, and N-way allocation are all in scope.** Settled. Allocation brings a
   `BigDecimals` collection and a dependency on `tiny-blocks/collection`.
5. **The backend override is a process-wide static.** Not put to the user, because the alternative
   (no override at all) would make the public `Calculator` interface decorative. Bounded as
   described in section 7.
6. **`PositiveBigDecimal` and `NegativeBigDecimal` are removed** rather than fixed. A sign
   constraint is a consumer's domain invariant, and as subtypes they break substitutability.
7. **`fromFloat` is kept**, as the single named lossy entry point, rather than banning float
   outright as brick/math's `of()` does.
8. **Weights for allocation are `BigDecimal`, not `BigInteger`**, so a `1.5 : 2.5` split is
   expressible and only one collection type is needed.

## 12. Removed from 1.x

| 1.x symbol | 2.0 replacement | Reason |
|---|---|---|
| `BigNumber` (interface) | `Number` | The name said "big", the contract said "number". An interface, not an abstract class. |
| `BigNumber::AUTOMATIC_SCALE` | Removed | Scale comes from the literal or from an explicit conversion. A null scale meaning "decide for me" is the ambient-context mistake in miniature. |
| `BigDecimal::fromString` | `BigDecimal::of` | One factory, one name. |
| `BigDecimal::fromFloat` | `BigDecimal::fromFloat` | Kept, but no longer takes a scale and is documented as the only lossy entry point. |
| `PositiveBigDecimal` | Removed | Breaks substitutability, see section 11 item 7. |
| `NegativeBigDecimal` | Removed | Same. |
| `TinyBlocks\Math\RoundingMode` (int-backed, 4 cases) | `TinyBlocks\Math\RoundingMode` (string-backed, 8 cases) | Four half modes become eight, adding directed rounding. Backing type changes from `int` to `string`, so persisted values must be migrated. The rounding itself no longer routes through `float`. |
| `add`, `subtract`, `multiply`, `divide` | `plus`, `minus`, `multipliedBy`, `dividedBy` | Matches `Duration::plus` in `tiny-blocks/time` and reads as a copy operation on an immutable. |
| `withRounding(RoundingMode)` | `toScale(int, RoundingMode)` | Rounding without naming a target scale is undefined, and 1.x resolved it through `float`. |
| `withScale(int)` | `toScale(int, RoundingMode)` or `toScaleExact(int)` | Changing scale is a rounding operation, so it names its mode. |
| `getScale()` | `scale()` | No `get` prefix on a value accessor. |
| `absolute()` | `absolute()` | Same name, exact implementation. |
| `Internal\Exceptions\*` | `Exceptions\*` | Consumers catch these, so they belong on the public boundary. |
| `MathOperationsNotAvailable` | `CalculatorNotAvailable` | Named after the invariant. |
| `InvalidNumber` | `NumberNotWellFormed` | Same. |
| `InvalidScale` | `ScaleOutOfRange` | Same. |
| `NonPositiveValue`, `NonNegativeValue` | Removed | Their only callers were the removed sign subtypes. |

## 13. Usage

Four tasks, written as a user writes them.

**Apply a discount and settle to the currency's scale.**

```php
<?php

declare(strict_types=1);

use TinyBlocks\Currency\Currency;
use TinyBlocks\Math\BigDecimal;
use TinyBlocks\Math\Percentage;
use TinyBlocks\Math\RoundingMode;

$price = BigDecimal::of(value: '19.99');
$final = Percentage::of(value: '12.5')->decrease(amount: $price);

$final->toScale(scale: Currency::BRL->getFractionDigits(), rounding: RoundingMode::HalfEven);
# 17.49
```

**Divide exactly, then present.**

```php
<?php

declare(strict_types=1);

use TinyBlocks\Math\BigDecimal;
use TinyBlocks\Math\RoundingMode;

$share = BigDecimal::of(value: '100.00')->dividedBy(divisor: BigDecimal::of(value: '3'));

$share->toDecimal(scale: 2, rounding: RoundingMode::HalfEven)->toString();
# 33.33
```

**Split an amount with no minor unit lost.**

```php
<?php

declare(strict_types=1);

use TinyBlocks\Math\BigDecimal;
use TinyBlocks\Math\BigDecimals;

$parts = BigDecimal::of(value: '100.00')->allocate(weights: BigDecimals::of('1', '1', '1'), scale: 2);

$parts->sum()->toString();
# 100.00, and the parts are 33.34, 33.33, 33.33
```

**Total a set of lines with no rounding anywhere.**

```php
<?php

declare(strict_types=1);

use TinyBlocks\Math\BigDecimal;

$total = BigDecimal::of(value: '19.99')
    ->plus(addend: BigDecimal::of(value: '5.10'))
    ->plus(addend: BigDecimal::of(value: '0.01'));

$total->toString();
# 25.10
```

## 14. Deviations recorded during implementation

The proposal above was approved as written. Five things changed while building it, each forced by a
repository rule or by a measurement, and each is recorded here so the document stays true to the
code.

1. **`Number` no longer extends `Stringable`, and no type defines `__toString`.** The canonical
   `phpcs.xml` places magic methods after every other method, while the member ordering rule places
   methods by name length ascending. Both are gates, and no arrangement of `__toString` satisfies
   both. `toString()` is the single canonical string form.
2. **`tiny-blocks/collection` is not a dependency.** `BigDecimals` wraps a private array. It is
   itself the typed collection the code style rule asks for, and pulling in a dependency to hold
   five elements is not KISS.
3. **`tiny-blocks/value-object` is not a dependency either.** The interface it exposes adds nothing
   the numeric types do not already declare themselves, and `equals` typed against the exact class
   is stronger than one typed against the interface. Section 12 still describes it as added to
   `require` as `^5.0`, and that is superseded here: `require` is `php` alone, so the ecosystem
   graph gains no edge at all rather than the two that section claims.
4. **`Calculator` is an integer engine, not a decimal one.** The interface in section 7 carried a
   `$scale` on every operation. Integer only is the boundary GMP and BCMath actually share, it
   keeps scale bookkeeping inside the library so two backends cannot disagree, and it is what makes
   a future GMP backend meaningful at all.
5. **Two exception classes were added**, `NegativeExponent` and `BaseOutOfRange`. Section 5.2
   specified that `power` rejects a negative exponent and that `fromBase` takes a base from 2 to 36
   without naming the failures those rules produce.
6. **`Ratio::of` takes `int|string`, not `BigInteger|int|string`.** Accepting the value object too
   would have meant an `instanceof` for coercion in a constructor, which the polymorphism rule
   discourages, for no gain over `$integer->toString()` at the call site.

One addition carries its own justification. `Digits` bounds the exponent it expands at 10000, so a
literal such as `'1e10001'` is refused rather than allocating without limit.

## 15. Refactoring pass

A design review after the first working version drove a second pass. Each change below is either a
rule the first version broke, a defect the review demonstrated, or a cost a measurement exposed.

**Rules the first version broke.** `BigInteger` carried a private `guardAgainstZero`, which the
code style rule permits only inside `src/Internal/`. `PositionalBase` passed two `sprintf` format
strings inline instead of through a `$template` variable. Both are fixed by moving the operations
they belonged to onto `Internal\Digits`, which is where the architecture rule wants the algorithm
anyway. `BigInteger` and `BigDecimal` are now facades that hold a `Digits` and delegate.

**Defects the review demonstrated.** `BigInteger::fromBase(base: 16, value: '--ff')` parsed as
`-255`, because the sign was stripped with `ltrim` rather than once. `Internal\Digits::shiftedBy`
handed the calculator operands carrying leading zeros, which the `Calculator` contract forbids and
which a GMP backend would read as octal. The trait `NumberComparison` implemented `compareTo`
through `toBigRational()`, which recurses forever for a `BigRational`, and was saved only by that
class happening to override it. The trait now declares `compareTo` abstract and supplies only the
predicates derived from it, so the recursion is unreachable by construction.

**Costs a measurement exposed.** Benchmarks over 50000 iterations, same command shape as section 2:

| Operation | Before | After |
|---|---|---|
| `Calculators::active()` | 2.30 us | 0.70 us |
| `BigDecimal::plus` | 32.65 us | 19.24 us |
| `BigDecimal::toScale` | 33.03 us | 19.80 us |
| `BigDecimal::compareTo` | 191.75 us | 139.50 us |
| `BigDecimal::dividedBy` | 267.57 us | 181.35 us |

Four changes produced it. `BcMathCalculator` moved from `BcMath\Number` to the raw `bc*`
functions, because the contract is string in and string out and the object re-parses both operands
on every call, which the section 2 benchmark had already shown. `Calculators::active()` stopped
asking the backend whether it is available on every call and now checks at registration, so a
bootstrap mistake surfaces at bootstrap. Scale alignment and powers of ten became string operations
rather than arbitrary-precision multiplications. And `Digits::from` parses with one regex carrying
named groups and applies the exponent as a shift of the decimal point, so the common path no longer
touches `bcmath` at all.

**One cost stands.** `compareTo` remains an order dearer than `multipliedBy`, because comparing
three numeric types exactly means comparing them as fractions, and building a fraction from a
decimal routes through the public `BigInteger` factory, which validates a string the library itself
produced. Removing that round trip needs either a leak of `Internal\Fraction` into a public
signature or a visitor across the three types. Neither is worth the surface, so the cost is
documented in the README instead.

**What the verification pass added.** Of thirty-five findings, three survived independent
refutation. `Internal\Digits::zero()` was unreferenced and is gone. `Internal\Allocation` carried
three docblocks that the conventions prohibit on a concrete collaborator inside `src/Internal/`, so
the allocation now yields its parts through a `Generator` whose only annotation is the type
parameter the carve-out permits, and the working lists route through `ignoreErrors` as the rule
prescribes. And `BigRational::toDecimalExact` factorized the denominator by two and by five four
times for one conversion, because `hasTerminatingDecimal()` and `minimalScale()` each ran both
passes. `Internal\Fraction::exactScale()` now does it once and raises when no exact decimal
exists, so the conversion costs half of what it did.

**Where the rounding decision lives.** `Internal\Rounding` first resolved a tie by handing a
surrogate decimal to `BcMath\Number::round`. That hard-bound the calculation seam to bcmath and
re-parsed the whole magnitude for a one-digit decision. The decision now lives on
`RoundingMode::roundsAwayFromZero()`, which is where the polymorphism rule wants a value a case
owns, and it is expressed the way Java's javadoc defines each constant.

## 16. Sources

Fetched or executed in this session.

**Executed.** A `php -r '<probe>'` run in the project's tooling image for the runtime facts in
section 2, and the three scripts `backend-bench.php`, `rounding-matrix.php`,
`rational-viability.php` for the measurements.

**brick/math 0.19.1.** `raw.githubusercontent.com/brick/math/master/` for `README.md`,
`composer.json`, `CHANGELOG.md`, `src/BigNumber.php`, `src/BigInteger.php`, `src/BigDecimal.php`,
`src/BigRational.php`, `src/RoundingMode.php`, the eleven files under `src/Exception/`,
`src/Internal/Calculator.php`, `src/Internal/CalculatorRegistry.php`, and the three files under
`src/Internal/Calculator/`.

**ext-decimal 2.0.1.** `github.com/php-decimal/ext-decimal` source (`php_decimal.c`, `src/context.h`,
`src/limits.h`, `src/round.h`, `src/errors.c`, `compare.c`, `decimal.c`), plus
`php-decimal.github.io`.

**PHP core.** `php.net/manual/en/class.bcmath-number.php` and its method pages,
`wiki.php.net/rfc/support_object_type_in_bcmath`, `php.net/manual/en/book.bc.php`,
`php.net/manual/en/book.gmp.php`, and `php-src` `ext/gmp/gmp.c`.

**maba/math and the PHP survey.** `github.com/mariusbalcytis/math`, and Packagist pages for
`brick/math`, `moneyphp/money`, `brick/money`, `moontoast/math`, `litipk/php-bignumbers`,
`krowinski/bcmath-extended`, `php-decimal/php-decimal`, `maba/math`, `tiny-blocks/math`, all read
on 2026-08-13.

**Java SE 21.** `docs.oracle.com` Javadoc for `BigDecimal`, `BigInteger`, `MathContext`,
`RoundingMode`, `DecimalFormat`, cross-checked against OpenJDK tag `jdk-21+35`.

**Python.** `docs.python.org/3/library/decimal.html` and CPython `Lib/_pydecimal.py`.

**JavaScript.** `mikemcl.github.io/decimal.js/`, `github.com/MikeMcl/decimal.js`, MDN `BigInt`,
and `github.com/tc39/proposal-decimal`.

**Local files.** `tiny-blocks/time` (`src/Duration.php`, `src/Timezone.php`, `src/Precision.php`,
`src/Exceptions/InvalidSeconds.php`, `src/Internal/Seconds.php`, `phpstan.neon.dist`,
`composer.json`), `tiny-blocks/value-object/src/ValueObject.php`,
`tiny-blocks/currency/src/Currency.php`, and the canonical config assets in
`.claude/skills/tiny-blocks-create/assets/config/`.
