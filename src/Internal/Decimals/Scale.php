<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Decimals;

use TinyBlocks\Math\Exceptions\ScaleOutOfRange;

final readonly class Scale
{
    private const int MAXIMUM = 10000;
    private const int MINIMUM = 0;

    private function __construct(public int $value)
    {
        if ($this->value < self::MINIMUM || $this->value > self::MAXIMUM) {
            throw ScaleOutOfRange::becauseValueIsOutsideBounds(value: $this->value, maximum: self::MAXIMUM);
        }
    }

    public static function of(int $value): Scale
    {
        return new Scale(value: $value);
    }

    public static function zero(): Scale
    {
        return new Scale(value: self::MINIMUM);
    }

    public function plus(Scale $other): Scale
    {
        return new Scale(value: ($this->value + $other->value));
    }

    public function equals(Scale $other): bool
    {
        return $this->value === $other->value;
    }

    public function greatest(Scale $other): Scale
    {
        return new Scale(value: max($this->value, $other->value));
    }

    public function placesFrom(Scale $other): int
    {
        return ($this->value - $other->value);
    }

    public function multipliedBy(int $factor): Scale
    {
        return new Scale(value: ($this->value * $factor));
    }
}
