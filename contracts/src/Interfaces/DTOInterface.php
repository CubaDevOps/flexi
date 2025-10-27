<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Interfaces;

/**
 * Interface for DTOs (Data Transfer Objects)
 * Pure interface without dependencies.
 */
interface DTOInterface
{
    /**
     * Create a DTO instance from an array.
     *
     * @return static
     */
    public static function fromArray(array $data): self;

    /**
     * Validate the DTO data.
     */
    public static function validate(array $data): bool;

    /**
     * Convert the DTO to an array.
     */
    public function toArray(): array;

    /**
     * Convert the DTO to a string.
     */
    public function __toString(): string;

    /**
     * Get a specific property by name.
     */
    public function get(string $name);
}
