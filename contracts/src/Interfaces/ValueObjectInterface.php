<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Interfaces;

/**
 * Contract for Value Objects
 * Immutable objects that represent a concept by their value.
 */
interface ValueObjectInterface
{
    /**
     * Get the value of this value object.
     */
    public function getValue();

    /**
     * Compare with another value object.
     */
    public function equals(ValueObjectInterface $other): bool;

    /**
     * String representation.
     */
    public function __toString(): string;
}
