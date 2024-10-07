<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal;

use TinyBlocks\Math\Internal\Exceptions\InvalidNumber;

final class Number
{
    private const ZERO = 0.0;
    private const SIGN = '?<sign>[\-\+]';
    private const POINT = '?<point>\.';
    private const INTEGRAL = '?<integral>[0-9]+';
    private const EXPONENT = '?<exponent>[\-\+]?[0-9]+';
    private const NUMERATOR = '?<numerator>[0-9]+';
    private const FRACTIONAL = '?<fractional>[0-9]+';
    private const DENOMINATOR = '?<denominator>[0-9]+';
    private const VALID_NUMBER = '/^(%s)?(?:(?:(%s)?(%s)?(%s)?(?:[eE](%s))?)|(?:(%s)\/?(%s)))$/';

    private int $match;
    private array $matches = [];

    private function __construct(public readonly string $value)
    {
        $pattern = sprintf(
            self::VALID_NUMBER,
            self::SIGN,
            self::INTEGRAL,
            self::POINT,
            self::FRACTIONAL,
            self::EXPONENT,
            self::NUMERATOR,
            self::DENOMINATOR
        );

        $this->match = (int)preg_match($pattern, $this->value, $this->matches);

        if ($this->isInvalidNumber()) {
            throw new InvalidNumber(value: $this->value);
        }
    }

    public static function from(float|string $value): Number
    {
        return new Number(value: (string)$value);
    }

    public function getExponent(): ?string
    {
        return $this->match(key: 'exponent');
    }

    public function getFractional(): ?string
    {
        return $this->match(key: 'fractional');
    }

    public function isZero(): bool
    {
        return $this->value == self::ZERO;
    }

    public function isNegative(): bool
    {
        return $this->value < self::ZERO;
    }

    public function isNegativeOrZero(): bool
    {
        return $this->isZero() || $this->isNegative();
    }

    public function isLessThan(Number $other): bool
    {
        return $this->value < $other->value;
    }

    public function isGreaterThan(Number $other): bool
    {
        return $this->value > $other->value;
    }

    public function isLessThanOrEqual(Number $other): bool
    {
        return $this->value <= $other->value;
    }

    public function isGreaterThanOrEqual(Number $other): bool
    {
        return $this->value >= $other->value;
    }

    private function match(string $key): ?string
    {
        $math = $this->matches[$key] ?? null;

        return $math == '' ? null : $math;
    }

    private function isInvalidNumber(): bool
    {
        $integral = $this->match(key: 'integral');

        return ($this->match != 1) || (is_null($integral) && is_null($this->getFractional()));
    }
}
