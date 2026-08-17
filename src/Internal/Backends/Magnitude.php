<?php

declare(strict_types=1);

namespace TinyBlocks\Math\Internal\Backends;

final readonly class Magnitude
{
    private const int TEN = 10;
    private const int BASE = 1000000000;
    private const int HALF = 2;
    private const int PAIR = 100;
    private const string ZERO = '0';
    private const array DIGITS = [9, 8, 7, 6, 5, 4, 3, 2, 1];
    private const int TWENTY = 20;
    private const int DIGITS_PER_LIMB = 9;

    /** @param list<int> $limbs */
    private function __construct(private array $limbs)
    {
    }

    public static function of(string $magnitude): Magnitude
    {
        $limbs = array_map(
            static fn(string $chunk): int => intval(strrev($chunk)),
            str_split(strrev($magnitude), self::DIGITS_PER_LIMB)
        );

        return Magnitude::from(limbs: $limbs);
    }

    /** @param list<int> $limbs */
    private static function from(array $limbs): Magnitude
    {
        $significant = array_key_last(array_filter($limbs, static fn(int $limb): bool => $limb !== 0));

        return new Magnitude(limbs: is_null($significant) ? [] : array_slice($limbs, 0, ($significant + 1)));
    }

    public function plus(Magnitude $other): Magnitude
    {
        $total = max(count($this->limbs), count($other->limbs));
        $addend = array_pad($other->limbs, $total, 0);
        $limbs = [];
        $carry = 0;

        foreach (array_pad($this->limbs, $total, 0) as $index => $limb) {
            $sum = ($carry + $limb + $addend[$index]);
            $limbs[] = ($sum % self::BASE);
            $carry = intdiv($sum, self::BASE);
        }

        return Magnitude::from(limbs: [...$limbs, $carry]);
    }

    private function minus(Magnitude $other): Magnitude
    {
        return $this->differenceFrom(other: $other)->magnitude;
    }

    public function isZero(): bool
    {
        return $this->limbs === [];
    }

    public function toText(): string
    {
        if ($this->isZero()) {
            return self::ZERO;
        }

        $parts = array_map(
            static fn(int $limb): string => str_pad((string)$limb, self::DIGITS_PER_LIMB, self::ZERO, STR_PAD_LEFT),
            array_reverse($this->limbs)
        );

        return ltrim(implode('', $parts), self::ZERO);
    }

    private function shifted(): Magnitude
    {
        return $this->isZero() ? $this : Magnitude::from(limbs: array_merge([0], $this->limbs));
    }

    private function scaledBy(int $factor): Magnitude
    {
        $limbs = [];
        $carry = 0;

        foreach ($this->limbs as $limb) {
            $product = (($limb * $factor) + $carry);
            $limbs[] = ($product % self::BASE);
            $carry = intdiv($product, self::BASE);
        }

        return Magnitude::from(limbs: [...$limbs, $carry]);
    }

    private function trialFor(int $digit): Magnitude
    {
        return $this->plus(other: Magnitude::from(limbs: [$digit]))->scaledBy(factor: $digit);
    }

    public function compareTo(Magnitude $other): int
    {
        if (count($this->limbs) !== count($other->limbs)) {
            return (count($this->limbs) <=> count($other->limbs));
        }

        for ($index = (count($this->limbs) - 1); $index >= 0; $index--) {
            if ($this->limbs[$index] !== $other->limbs[$index]) {
                return ($this->limbs[$index] <=> $other->limbs[$index]);
            }
        }

        return 0;
    }

    public function dividedBy(Magnitude $divisor): Division
    {
        return count($divisor->limbs) === 1
            ? $this->dividedByLimb(divisor: $divisor->limbs[0])
            : $this->longDivision(divisor: $divisor);
    }

    public function squareRoot(): Magnitude
    {
        $text = $this->toText();
        $padded = str_pad($text, (strlen($text) + (strlen($text) % self::HALF)), self::ZERO, STR_PAD_LEFT);
        $root = Magnitude::from(limbs: []);
        $remainder = Magnitude::from(limbs: []);

        foreach (str_split($padded, self::HALF) as $pair) {
            $remainder = $remainder->scaledBy(factor: self::PAIR)
                ->plus(other: Magnitude::from(limbs: [intval($pair)]));
            $doubled = $root->scaledBy(factor: self::TWENTY);
            $digit = $doubled->largestRootDigitFor(remainder: $remainder);
            $remainder = $remainder->minus(other: $doubled->trialFor(digit: $digit));
            $root = $root->scaledBy(factor: self::TEN)->plus(other: Magnitude::from(limbs: [$digit]));
        }

        return $root;
    }

    /**
     * @param list<int> $limbs
     * @return list<int>
     */
    private function complemented(array $limbs): array
    {
        $complement = [];
        $borrow = 0;

        foreach ($limbs as $limb) {
            $difference = ((0 - $borrow) - $limb);
            $borrow = intval($difference < 0);
            $complement[] = ($difference + ($borrow * self::BASE));
        }

        return $complement;
    }

    private function leadingValue(Magnitude $divisor): int
    {
        $tail = array_slice($this->limbs, (count($divisor->limbs) - 1));

        return ((array_sum(array_slice($tail, 1)) * self::BASE) + $tail[0]);
    }

    private function longDivision(Magnitude $divisor): Division
    {
        $normalizer = intdiv(self::BASE, ($divisor->limbs[(count($divisor->limbs) - 1)] + 1));
        $scaled = $divisor->scaledBy(factor: $normalizer);
        $quotient = [];
        $remainder = Magnitude::from(limbs: []);

        foreach (array_reverse($this->scaledBy(factor: $normalizer)->limbs) as $limb) {
            $remainder = $remainder->shifted()->plus(other: Magnitude::from(limbs: [$limb]));
            $factor = $remainder->largestFactorOf(divisor: $scaled);
            $quotient[] = $factor;
            $remainder = $remainder->minus(other: $scaled->scaledBy(factor: $factor));
        }

        return Division::of(
            quotient: Magnitude::from(limbs: array_reverse($quotient)),
            remainder: $remainder->dividedByLimb(divisor: $normalizer)->quotient
        );
    }

    public function multipliedBy(Magnitude $other): Magnitude
    {
        $limbs = array_fill(0, (count($this->limbs) + count($other->limbs)), 0);
        $multipliers = array_pad($other->limbs, (count($other->limbs) + 1), 0);

        foreach ($this->limbs as $left => $multiplicand) {
            $carry = 0;

            foreach ($multipliers as $right => $multiplier) {
                $product = ($limbs[($left + $right)] + ($multiplicand * $multiplier) + $carry);
                $limbs[($left + $right)] = ($product % self::BASE);
                $carry = intdiv($product, self::BASE);
            }
        }

        return Magnitude::from(limbs: $limbs);
    }

    private function dividedByLimb(int $divisor): Division
    {
        $quotient = [];
        $remainder = 0;

        foreach (array_reverse($this->limbs) as $limb) {
            $current = (($remainder * self::BASE) + $limb);
            $quotient[] = intdiv($current, $divisor);
            $remainder = ($current % $divisor);
        }

        return Division::of(
            quotient: Magnitude::from(limbs: array_reverse($quotient)),
            remainder: Magnitude::from(limbs: [$remainder])
        );
    }

    public function differenceFrom(Magnitude $other): Difference
    {
        $width = max(count($this->limbs), count($other->limbs));
        $subtrahend = array_pad($other->limbs, $width, 0);
        $limbs = [];
        $borrow = 0;

        foreach (array_pad($this->limbs, $width, 0) as $index => $limb) {
            $difference = ($limb - $borrow - $subtrahend[$index]);
            $borrow = intval($difference < 0);
            $limbs[] = ($difference + ($borrow * self::BASE));
        }

        return $borrow === 0
            ? Difference::of(magnitude: Magnitude::from(limbs: $limbs), isNegative: false)
            : Difference::of(magnitude: Magnitude::from(limbs: $this->complemented(limbs: $limbs)), isNegative: true);
    }

    private function largestFactorOf(Magnitude $divisor): int
    {
        if ($this->compareTo(other: $divisor) < 0) {
            return 0;
        }

        $head = $divisor->limbs[(count($divisor->limbs) - 1)];
        $estimate = intdiv($this->leadingValue(divisor: $divisor), $head);
        $once = ($estimate - intval($divisor->scaledBy(factor: $estimate)->compareTo(other: $this) > 0));

        return ($once - intval($divisor->scaledBy(factor: $once)->compareTo(other: $this) > 0));
    }

    private function largestRootDigitFor(Magnitude $remainder): int
    {
        foreach (self::DIGITS as $candidate) {
            if ($this->trialFor(digit: $candidate)->compareTo(other: $remainder) <= 0) {
                return $candidate;
            }
        }

        return 0;
    }
}
