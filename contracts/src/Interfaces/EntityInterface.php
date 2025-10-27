<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Interfaces;

/**
 * Interface for Domain Entities
 * Objects with identity that can change over time.
 */
interface EntityInterface
{
    /**
     * Get the unique identifier.
     */
    public function getId();

    /**
     * Get creation timestamp.
     */
    public function getCreatedAt(): \DateTimeImmutable;

    /**
     * Get last update timestamp.
     */
    public function getUpdatedAt(): \DateTimeImmutable;

    /**
     * Update the timestamp.
     */
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): void;

    /**
     * Convert to array representation.
     */
    public function toArray(): array;
}
