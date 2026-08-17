<?php

declare(strict_types=1);

namespace TinyBlocks\Math;

use JsonSerializable;

/**
 * Contract shared by every number this library produces.
 *
 * <p>Two notions of sameness live here and they are deliberately named apart.
 * <code>isEqualTo</code> is arithmetic and works across types, so <code>1.0</code> and
 * <code>1.00</code> are equal. Each implementation also carries an <code>equals</code> of its own,
 * typed to its exact class, which is structural and reads the representation, so the same pair is
 * not equal because the scales differ. Java documents this trap three times because it exposes
 * only one of the two under an ambiguous name.</p>
 *
 * <p>Serialization is a JSON string rather than a JSON number, because a JSON number is read back
 * as an IEEE-754 double and would discard exactly what this library preserves.</p>
 */
interface Number extends JsonSerializable
{
    /**
     * Tells whether this number is zero.
     *
     * @return bool True when the value is zero, at any scale.
     */
    public function isZero(): bool;

    /**
     * Returns this number with the opposite sign.
     *
     * @return Number A new instance with the sign flipped.
     */
    public function negated(): Number;

    /**
     * Returns the magnitude of this number.
     *
     * @return Number A new instance with the sign removed.
     */
    public function absolute(): Number;

    /**
     * Returns a deterministic hash of this number's representation.
     *
     * <p>Agrees with the implementation's own <code>equals</code>, so two numbers that are
     * structurally equal share a hash. Two numbers that are only arithmetically equal, such as
     * <code>1.0</code> and <code>1.00</code>, do not.</p>
     *
     * @return string The structural hash.
     */
    public function hashCode(): string;

    /**
     * Returns this number as a string.
     *
     * <p>Always positional notation, never scientific. The output round-trips through the
     * originating type's own factory.</p>
     *
     * @return string The canonical string form.
     */
    public function toString(): string;

    /**
     * Compares this number with another.
     *
     * <p>Exact whatever the pair of types. Two numbers of the same type are compared on their own
     * representation. Across types the comparison goes through {@see BigRational}, the only form
     * every number has exactly.</p>
     *
     * @param Number $other The number to compare against.
     * @return int A negative number, zero, or a positive number.
     */
    public function compareTo(Number $other): int;

    /**
     * Tells whether this number has the same value as another.
     *
     * <p>Arithmetic equality, so scale is irrelevant and the two numbers need not share a type.
     * For structural equality that distinguishes <code>1.0</code> from <code>1.00</code>, use the
     * implementation's own <code>equals</code>, which accepts only its exact type.</p>
     *
     * @param Number $other The number to compare against.
     * @return bool True when both represent the same value.
     */
    public function isEqualTo(Number $other): bool;

    /**
     * Tells whether this number is strictly less than another.
     *
     * @param Number $other The number to compare against.
     * @return bool True when this number is smaller.
     */
    public function isLessThan(Number $other): bool;

    /**
     * Tells whether this number is strictly less than zero.
     *
     * @return bool True when the value is negative.
     */
    public function isNegative(): bool;

    /**
     * Tells whether this number is strictly greater than zero.
     *
     * @return bool True when the value is positive.
     */
    public function isPositive(): bool;

    /**
     * Tells whether this number is strictly greater than another.
     *
     * @param Number $other The number to compare against.
     * @return bool True when this number is larger.
     */
    public function isGreaterThan(Number $other): bool;

    /**
     * Returns this number as an exact fraction.
     *
     * @return BigRational The exact rational representation.
     */
    public function toBigRational(): BigRational;

    /**
     * Tells whether this number is less than or equal to another.
     *
     * @param Number $other The number to compare against.
     * @return bool True when this number is smaller or equal.
     */
    public function isLessThanOrEqualTo(Number $other): bool;

    /**
     * Tells whether this number is greater than or equal to another.
     *
     * @param Number $other The number to compare against.
     * @return bool True when this number is larger or equal.
     */
    public function isGreaterThanOrEqualTo(Number $other): bool;
}
