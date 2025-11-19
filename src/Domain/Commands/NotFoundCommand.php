<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Commands;

use Flexi\Contracts\Interfaces\DTOInterface;

/**
 * Command used when no handler is found for a given command identifier.
 * Represents a "null object" pattern implementation.
 */
class NotFoundCommand implements DTOInterface
{
    public function toArray(): array
    {
        return [
            'error' => 'Command not found',
            'handler' => false
        ];
    }

    public function __toString(): string
    {
        return 'NotFoundCommand: No handler registered for this command';
    }

    public static function fromArray(array $data): self
    {
        return new self();
    }

    public static function validate(array $data): bool
    {
        return true;
    }

    public function get(string $name)
    {
        $data = $this->toArray();
        return $data[$name] ?? null;
    }
}
