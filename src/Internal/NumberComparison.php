<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal;

use TinyBlocks\Math\Number;

trait NumberComparison
{
    abstract public function isZero(): bool;

    abstract public function compareTo(Number $other): int;

    public function isEqualTo(Number $other): bool
    {
        return $this->compareTo(other: $other) === 0;
    }

    public function isLessThan(Number $other): bool
    {
        return $this->compareTo(other: $other) < 0;
    }

    abstract public function isNegative(): bool;

    public function isPositive(): bool
    {
        return !$this->isZero() && !$this->isNegative();
    }

    public function isGreaterThan(Number $other): bool
    {
        return $this->compareTo(other: $other) > 0;
    }

    public function isLessThanOrEqualTo(Number $other): bool
    {
        return $this->compareTo(other: $other) <= 0;
    }

    public function isGreaterThanOrEqualTo(Number $other): bool
    {
        return $this->compareTo(other: $other) >= 0;
    }
}
