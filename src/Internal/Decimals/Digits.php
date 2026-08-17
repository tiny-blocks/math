<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Decimals;

use TinyBlocks\Math\Calculators;
use TinyBlocks\Math\Exceptions\IntegerOverflow;
use TinyBlocks\Math\Exceptions\NumberNotWellFormed;
use TinyBlocks\Math\Internal\Exponent;
use TinyBlocks\Math\Internal\GreatestCommonDivisor;
use TinyBlocks\Math\RoundingMode;

final readonly class Digits
{
    private const string ONE = '1';
    private const string ZERO = '0';
    private const string MINUS = '-';
    private const string SIGNS = '-+';
    private const string PATTERN = '/^(?<sign>[+-]?)(?=\.?\d)(?<integral>\d*)(?:\.(?<fractional>\d*))?'
        . '(?:[eE](?<exponent>[+-]?\d+))?$/';
    private const array EVEN_DIGITS = ['0', '2', '4', '6', '8'];
    private const int MAXIMUM_EXPANSION = 10000;
    private const array SIGNIFICANT_DIGITS = [16, 17];

    private function __construct(public Scale $scale, public string $unscaled)
    {
    }

    public static function of(Scale $scale, string $unscaled): Digits
    {
        return new Digits(scale: $scale, unscaled: $unscaled);
    }

    public static function from(string|int $value): Digits
    {
        $text = (string)$value;

        if (preg_match(self::PATTERN, $text, $parts) !== 1) {
            throw NumberNotWellFormed::becauseValueIsNotNumeric(value: $text);
        }

        $exponent = intval(($parts['exponent'] ?? ''));

        if (abs($exponent) > self::MAXIMUM_EXPANSION) {
            throw NumberNotWellFormed::becauseExponentIsTooLarge(value: $text, maximum: self::MAXIMUM_EXPANSION);
        }

        $fractional = ($parts['fractional'] ?? '');
        $places = (strlen($fractional) - $exponent);
        $template = '%s%s%s%s';

        return new Digits(
            scale: Scale::of(value: max(0, $places)),
            unscaled: Digits::normalized(
                text: sprintf(
                    $template,
                    $parts['sign'],
                    $parts['integral'],
                    $fractional,
                    str_repeat(self::ZERO, max(0, (0 - $places)))
                )
            )
        );
    }

    public static function tenTo(int $exponent): string
    {
        $template = '%s%s';

        return sprintf($template, self::ONE, str_repeat(self::ZERO, $exponent));
    }

    public static function fromFloat(float $value): Digits
    {
        $template = '%%.%dG';

        foreach (self::SIGNIFICANT_DIGITS as $digits) {
            $format = sprintf($template, $digits);
            $candidate = sprintf($format, $value);

            if ((float)$candidate === $value) {
                return Digits::from(value: $candidate);
            }
        }

        return Digits::from(value: (string)var_export($value, true));
    }

    public static function normalized(string $text): string
    {
        $magnitude = ltrim(ltrim($text, self::SIGNS), self::ZERO);
        $template = '%s%s';

        return $magnitude === ''
            ? self::ZERO
            : sprintf($template, str_starts_with($text, self::MINUS) ? self::MINUS : '', $magnitude);
    }

    public function plus(Digits $other): Digits
    {
        $scale = $this->scale->greatest(other: $other->scale);

        return new Digits(
            scale: $scale,
            unscaled: Calculators::active()->add(
                left: $this->shiftedBy(places: $scale->placesFrom(other: $this->scale)),
                right: $other->shiftedBy(places: $scale->placesFrom(other: $other->scale))
            )
        );
    }

    public function minus(Digits $other): Digits
    {
        return $this->plus(other: $other->negated());
    }

    public function power(int $exponent): Digits
    {
        $bounded = Exponent::of(value: $exponent);

        return new Digits(
            scale: $this->scale->multipliedBy(factor: $bounded->magnitude),
            unscaled: Calculators::active()->power(base: $this->unscaled, exponent: $bounded->magnitude)
        );
    }

    public function toInt(): int
    {
        $calculator = Calculators::active();
        $isOutside = $calculator->compare(left: $this->unscaled, right: (string)PHP_INT_MAX) > 0
            || $calculator->compare(left: $this->unscaled, right: (string)PHP_INT_MIN) < 0;

        if ($isOutside) {
            throw IntegerOverflow::becauseValueExceedsIntegerRange(value: $this->unscaled);
        }

        return intval($this->unscaled);
    }

    public function value(): string
    {
        if ($this->scale->value === 0) {
            return $this->unscaled;
        }

        $padded = str_pad(ltrim($this->unscaled, self::MINUS), ($this->scale->value + 1), self::ZERO, STR_PAD_LEFT);
        $template = '%s%s.%s';

        return sprintf(
            $template,
            $this->isNegative() ? self::MINUS : '',
            substr($padded, 0, -$this->scale->value),
            substr($padded, -$this->scale->value)
        );
    }

    public function isEven(): bool
    {
        return in_array(substr($this->unscaled, -1), self::EVEN_DIGITS, true);
    }

    public function isZero(): bool
    {
        return $this->unscaled === self::ZERO;
    }

    public function negated(): Digits
    {
        return new Digits(
            scale: $this->scale,
            unscaled: Calculators::active()->subtract(minuend: self::ZERO, subtrahend: $this->unscaled)
        );
    }

    public function absolute(): Digits
    {
        return new Digits(scale: $this->scale, unscaled: ltrim($this->unscaled, self::MINUS));
    }

    public function quotient(Digits $divisor): Digits
    {
        return new Digits(
            scale: $this->scale,
            unscaled: Calculators::active()->quotient(
                numerator: $this->unscaled,
                denominator: $divisor->unscaled
            )
        );
    }

    public function compareTo(Digits $other): int
    {
        $scale = $this->scale->greatest(other: $other->scale);

        return Calculators::active()->compare(
            left: $this->shiftedBy(places: $scale->placesFrom(other: $this->scale)),
            right: $other->shiftedBy(places: $scale->placesFrom(other: $other->scale))
        );
    }

    public function remainder(Digits $divisor): Digits
    {
        return new Digits(
            scale: $this->scale,
            unscaled: Calculators::active()->remainder(
                numerator: $this->unscaled,
                denominator: $divisor->unscaled
            )
        );
    }

    public function shiftedBy(int $places): string
    {
        $template = '%s%s';

        return Digits::normalized(text: sprintf($template, $this->unscaled, str_repeat(self::ZERO, $places)));
    }

    public function withScale(Scale $scale, RoundingMode $roundingMode): Digits
    {
        $shift = $scale->placesFrom(other: $this->scale);

        return new Digits(
            scale: $scale,
            unscaled: new Rounding()->trimmed(
                value: $this->shiftedBy(places: max(0, $shift)),
                places: Scale::of(value: max(0, -$shift)),
                roundingMode: $roundingMode
            )
        );
    }

    public function isNegative(): bool
    {
        return str_starts_with($this->unscaled, self::MINUS);
    }

    public function squareRoot(): Digits
    {
        return new Digits(scale: $this->scale, unscaled: Calculators::active()->squareRoot(radicand: $this->unscaled));
    }

    public function scaleFactor(): string
    {
        return Digits::tenTo(exponent: $this->scale->value);
    }

    public function integralPart(): string
    {
        return Calculators::active()->quotient(numerator: $this->unscaled, denominator: $this->scaleFactor());
    }

    public function multipliedBy(Digits $other): Digits
    {
        return new Digits(
            scale: $this->scale->plus(other: $other->scale),
            unscaled: Calculators::active()->multiply(left: $this->unscaled, right: $other->unscaled)
        );
    }

    public function withoutTrailingZeros(): Digits
    {
        if ($this->isZero()) {
            return new Digits(scale: Scale::zero(), unscaled: self::ZERO);
        }

        $length = strlen($this->unscaled);
        $removable = min($this->scale->value, ($length - strlen(rtrim($this->unscaled, self::ZERO))));

        return new Digits(
            scale: Scale::of(value: ($this->scale->value - $removable)),
            unscaled: substr($this->unscaled, 0, ($length - $removable))
        );
    }

    public function greatestCommonDivisor(Digits $other): Digits
    {
        return new Digits(
            scale: Scale::zero(),
            unscaled: new GreatestCommonDivisor()->of(
                left: ltrim($this->unscaled, self::MINUS),
                right: ltrim($other->unscaled, self::MINUS)
            )
        );
    }
}
