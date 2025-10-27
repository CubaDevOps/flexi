<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\TestData\Commands;

use CubaDevOps\Flexi\Contracts\Interfaces\DTOInterface;

/**
 * Simple test command for unit testing purposes.
 * This is a real implementation, not a test double.
 */
class TestCommand implements DTOInterface
{
    public function toArray(): array
    {
        return ['command' => 'test'];
    }

    public static function fromArray(array $data): self
    {
        return new self();
    }

    public function get(string $name)
    {
        $data = $this->toArray();
        return $data[$name] ?? null;
    }

    public static function validate(array $data): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return 'test';
    }
}