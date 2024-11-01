<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal;

use TinyBlocks\Math\BigNumber;
use TinyBlocks\Math\Internal\Exceptions\InvalidScale;

final readonly class Scale
{
    private const MINIMUM = 0;
    private const MAXIMUM = 2147483647;
    private const ZERO_DECIMAL_PLACE = 0;

    private function __construct(public ?int $value)
    {
        if (!$this->hasAutomaticScale() && ($this->value < self::MINIMUM || $this->value > self::MAXIMUM)) {
            throw new InvalidScale(value: $this->value, minimum: self::MINIMUM, maximum: self::MAXIMUM);
        }
    }

    public static function from(?int $value): Scale
    {
        return new Scale(value: $value);
    }

    public function scaleOf(string $value): Scale
    {
        $number = Number::from(value: $value);

        $exponent = $number->getExponent();
        $exponent = is_null($exponent) ? self::MINIMUM : $exponent;

        $fractional = $number->getFractional();
        $fractional = is_null($fractional) ? self::MINIMUM : strlen($fractional);

        return new Scale(value: max($fractional - $exponent, self::MINIMUM));
    }

    public function numberWithScale(Number $number, int $scale): Number
    {
        $result = explode('.', $number->value);

        if (count($result) <= 1) {
            return Number::from(value: $result[0]);
        }

        $decimal = $result[0];
        $decimalPlaces = substr($result[1], self::ZERO_DECIMAL_PLACE, $scale);

        $template = '%s.%s';
        $value = sprintf($template, $decimal, $decimalPlaces);

        return Number::from(value: $value);
    }

    public function add(Scale $other): Scale
    {
        return new Scale(value: $this->value + $other->value);
    }

    public function greaterScale(Scale $other): Scale
    {
        return new Scale(value: max($this->value, $other->value));
    }

    public function equals(Scale $other): bool
    {
        return $this->value == $other->value;
    }

    public function hasAutomaticScale(): bool
    {
        return $this->value === BigNumber::AUTOMATIC_SCALE;
    }
}
