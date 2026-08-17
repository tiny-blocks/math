# math redesign, resume state

## Checklist

- [x] 1. Read repository: CLAUDE.md, .claude/rules/, src/, tests/, composer.json, Makefile, phpstan.neon.dist, infection.json.dist, phpmd.xml, README.md
- [x] 2. Research reference libraries
- [x] 3. Write comparison table (section 3 of the design document)
- [x] 4. Write design proposal to docs/specs/2026-08-13-math-redesign-design.md
- [x] 5. GATE, present proposal in pt-BR, approved by the user
- [x] 6. Implement src/
- [x] 7. Implement tests/
- [x] 8. Run the quality gates from the Makefile
- [x] 9. Write README.md
- [x] 10. Write UPGRADE.md (1.x to 2.0 mapping)
- [x] 11. Refactoring pass driven by an adversarial design review (section 15 of the design document)

## Current position

Complete. Every change is unstaged in the working tree, as instructed. No branch, commit, or tag was created.

## Gate results

- `make review`: `make: ok`. phpcs (PSR-12 plus the curated sniffs) and phpstan `level: max` over `src` and `tests`, with `reportUnmatchedIgnoredErrors: true`.
- `make tests`: `OK (434 tests, 602 assertions)`, then Infection: `739 mutations were generated`, `732 mutants were killed by Test Framework`, `6 errors`, `1 time out`, `0 escaped`, `0 skipped`, `Mutation Code Coverage: 100%`, `Covered Code MSI: 100%`. The single time out sits on `Magnitude::compareTo`: negating its loop guard makes every comparison report equality, which leaves the Euclid loop in `GreatestCommonDivisor` without a decreasing measure. Whether an assertion reaches it before the clock does depends on the random test order, so `--with-timeouts` stays off until that loop is bounded by construction.

## Files created or modified

- `src/` rewritten in full: `Number.php`, `BigInteger.php`, `BigDecimal.php`, `BigDecimals.php`, `BigRational.php`, `Percentage.php`, `Ratio.php`, `RoundingMode.php`, `Calculator.php`, `Calculators.php`, twelve classes under `Exceptions/`, and fourteen collaborators under `Internal/`, segregated by context into `Allocations/`, `Backends/`, `Bases/`, `Decimals/` and `Fractions/`, with `Exponent`, `GreatestCommonDivisor`, `NumberComparison` and `StructuralHash` as the shared kernel at the `Internal/` root.
- `tests/Unit/` written in full: seven test classes plus two calculator test doubles.
- `composer.json`: new description and keywords, `autoload-dev` moved to the canonical `Test\TinyBlocks\Math\` namespace, the `suggest` block removed. The library keeps zero Composer dependencies: `tiny-blocks/value-object` was added during the redesign and removed again afterwards, in favor of an `equals` declared on each concrete type with its own exact parameter type. `ext-bcmath` moved from `require` to `suggest` once `Internal\Backends\NativeCalculator` shipped as a pure PHP fallback.
- `phpstan.neon.dist`: brought to the canonical `level: max` over `src` and `tests`, with two scoped `ignoreErrors` entries, each carrying a comment.
- `.gitattributes`: `/.claude`, `/docs`, and `/UPGRADE.md` added under `export-ignore`.
- `README.md` and `UPGRADE.md` rewritten.
- `docs/specs/2026-08-13-math-redesign-design.md` (the approved proposal, with deviations and the refactoring pass appended).
- `docs/specs/math-redesign-state.md` (this file).

## Decisions settled by the user at the gate

1. Division returns `BigRational`, exact and total, on `BigInteger` and `BigDecimal`.
2. The library owns `TinyBlocks\Math\RoundingMode`, string-backed, eight cases.
3. Ship `BcMathCalculator` only. `Calculator` stays a public seam.
4. Scope includes `Percentage`, `Ratio`, and N-way allocation.

## Deviations and later changes

Section 14 of the design document records the deviations from the approved proposal. Section 15 records the refactoring pass: the rule violations it fixed, the three defects the review demonstrated, and the measured cost reductions.

`#[\NoDiscard]` was applied to every public instance method in one pass and then removed at the user's request. On a library where every method is pure, the attribute lands on all of them, and the noise outweighs the diagnostic. Removing it also raised the mutant count from 400 to 467, because the attribute was suppressing generation on the methods it decorated, so the suite now proves more than it did with it.

## Verification performed beyond the gates

- An end-to-end script exercising every public method and every documented failure, 140 assertions, run against the built autoloader in the project image. Zero failures.
- A documentation conformance script asserting that every public method of every published type appears in the README, that every failure class the README names exists, that every 2.0 symbol `UPGRADE.md` promises exists on the type it is attributed to, and that every `RoundingMode` case the README lists is real. Zero failures. It caught two genuine gaps: the `Calculator` and `Calculators` method tables were missing from the README.
- A table-of-contents check confirming all sixteen headings are linked and that the FAQ is represented by a single entry, as the documentation rule requires.

## Suggestions left out of scope

- Ship a `GmpCalculator` once the pinned tooling image carries `ext-gmp`. The seam and the selection rule are already in place.
- Remove the internal round trip in `BigDecimal::toBigRational`, which validates strings the library produced. It still costs on the cross-type comparison path, since same-type comparison no longer goes through it. Doing it needs either a leak of `Internal\Fractions\Fraction` into a public signature or a visitor across the three numeric types.
- `BigDecimal::withPointMovedLeft` and `withPointMovedRight`.
- `BigRational::toRepeatingDecimalString`, for rendering `10/3` as `3.(3)`.
- `BigInteger::modularInverse` and `modularPower`, which are the operations a GMP backend would accelerate most.
- No change is needed in `doc/dependency-graph.svg` in the `tiny-blocks` meta repository: `tiny-blocks/math` still has no outgoing edge. That repository is not part of this checkout.

## Next action

None. Report the outcome.
