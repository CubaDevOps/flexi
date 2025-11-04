<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Application\Commands;

use Flexi\Contracts\Interfaces\CliDTOInterface;

/**
 * Command DTO for validating all modules' configuration.
 */
class ValidateModulesCommand implements CliDTOInterface
{
    private array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function __toString(): string
    {
        return __CLASS__;
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public static function validate(array $data): bool
    {
        return true;
    }

    public function get(string $name)
    {
        return $this->data[$name] ?? null;
    }

    public function usage(): string
    {
        return 'Usage: modules:validate - Validate all modules\' configuration';
    }
}

