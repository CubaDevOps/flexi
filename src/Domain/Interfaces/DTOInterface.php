<?php

namespace CubaDevOps\Flexi\Domain\Interfaces;

interface DTOInterface
{
    /**
     * Create a DTO instance from an array.
     *
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): self;

    /**
     * Validate the DTO data.
     *
     * @param array $data
     * @return bool
     */
    public static function validate(array $data): bool;

    /**
     * Convert the DTO to an array.
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Convert the DTO to a string.
     *
     * @return string
     */
    public function __toString(): string;

    /**
     * Get a specific property by name.
     *
     * @param string $name
     * @return mixed
     */
    public function get(string $name);
}
