<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Interfaces;

use Psr\EventDispatcher\StoppableEventInterface;

interface EventInterface extends DTOInterface, StoppableEventInterface
{
    public function getName(): string;

    public function occurredOn(): \DateTimeImmutable;

    public function firedBy(): string;

    public function serialize(): string;

    /**
     * Set a value in the event's data array.
     * Allows listeners to add or modify event data.
     *
     * @param string $key The key to set
     * @param mixed $value The value to set
     */
    public function set(string $key, $value): void;

    /**
     * Check if a key exists in the event's data.
     *
     * @param string $key The key to check
     * @return bool True if the key exists
     */
    public function has(string $key): bool;

    /**
     * Stop the propagation of this event to other listeners.
     * After calling this method, isPropagationStopped() should return true.
     */
    public function stopPropagation(): void;
}
