<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Application\Commands;

use Flexi\Contracts\Interfaces\CliDTOInterface;

/**
 * Command DTO for listing all available modules.
 */
class ListModulesCommand implements CliDTOInterface
{
    public function toArray(): array
    {
        return [];
    }

    public function __toString(): string
    {
        return __CLASS__;
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
        return null;
    }

    public function usage(): string
    {
        return 'Usage: modules:list';
    }
}

