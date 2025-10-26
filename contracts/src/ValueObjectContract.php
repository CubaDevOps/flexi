<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts;

/**
 * Contract for Value Objects
 * Immutable objects that represent a concept by their value
 */
interface ValueObjectContract
{
    /**
     * Get the value of this value object
     */
    public function getValue();

    /**
     * Compare with another value object
     */
    public function equals(ValueObjectContract $other): bool;

    /**
     * String representation
     */
    public function __toString(): string;
}