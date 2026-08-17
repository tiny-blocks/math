<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Bases;

use TinyBlocks\Math\Calculators;
use TinyBlocks\Math\Exceptions\BaseOutOfRange;
use TinyBlocks\Math\Exceptions\NumberNotWellFormed;
use TinyBlocks\Math\Internal\Decimals\Digits;
use TinyBlocks\Math\Internal\Decimals\Scale;

final readonly class PositionalBase
{
    private const string ZERO = '0';
    private const string MINUS = '-';
    private const string BOUNDS = '2 to 36';
    private const int MAXIMUM = 36;
    private const int MINIMUM = 2;
    private const string ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyz';

    private function __construct(private int $base)
    {
        if ($this->base < self::MINIMUM || $this->base > self::MAXIMUM) {
            throw BaseOutOfRange::becauseBaseIsOutsideBounds(base: $this->base, bounds: self::BOUNDS);
        }
    }

    public static function of(int $base): PositionalBase
    {
        return new PositionalBase(base: $base);
    }

    public function toText(Digits $digits): string
    {
        if ($digits->isZero()) {
            return self::ZERO;
        }

        $calculator = Calculators::active();
        $remaining = ltrim($digits->unscaled, self::MINUS);
        $text = '';

        while ($remaining !== self::ZERO) {
            $index = intval($calculator->remainder(numerator: $remaining, denominator: (string)$this->base));
            $template = '%s%s';
            $text = sprintf($template, self::ALPHABET[$index], $text);
            $remaining = $calculator->quotient(numerator: $remaining, denominator: (string)$this->base);
        }

        $template = '%s%s';

        return sprintf($template, $digits->isNegative() ? self::MINUS : '', $text);
    }

    private function position(string $value, string $character): int
    {
        $position = strpos(substr(self::ALPHABET, 0, $this->base), $character);

        if ($position === false) {
            throw NumberNotWellFormed::becauseValueIsNotNumeric(value: $value);
        }

        return $position;
    }

    public function toDigits(string $value): Digits
    {
        $isNegative = str_starts_with($value, self::MINUS);
        $magnitude = strtolower(substr($value, intval($isNegative)));

        if ($magnitude === '') {
            throw NumberNotWellFormed::becauseValueIsNotNumeric(value: $value);
        }

        $calculator = Calculators::active();
        $unscaled = self::ZERO;

        foreach (str_split($magnitude) as $character) {
            $unscaled = $calculator->add(
                left: $calculator->multiply(left: $unscaled, right: (string)$this->base),
                right: (string)$this->position(value: $value, character: $character)
            );
        }

        return Digits::of(
            scale: Scale::zero(),
            unscaled: $isNegative ? $calculator->subtract(minuend: self::ZERO, subtrahend: $unscaled) : $unscaled
        );
    }
}
