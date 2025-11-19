<?php

declare(strict_types=1);

namespace Flexi\Application\Commands;

use Flexi\Contracts\Interfaces\DTOInterface;

/**
 * Command DTO for updating module environment variables.
 *
 * Represents the parameters needed to update environment variables
 * of a specific module while preserving user modifications.
 */
class UpdateModuleEnvironmentCommand implements DTOInterface
{
    private string $moduleName;
    private ?string $modifiedBy;
    private bool $forceUpdate;

    public function __construct(array $args = [])
    {
        $this->moduleName = $args['module_name'] ?? $args[0] ?? '';
        $this->modifiedBy = $args['modified_by'] ?? null;
        $this->forceUpdate = (bool)($args['force'] ?? false);
    }

    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    public function getModifiedBy(): ?string
    {
        return $this->modifiedBy;
    }

    public function isForceUpdate(): bool
    {
        return $this->forceUpdate;
    }

    /**
     * Validate the command parameters.
     */
    public function isValid(): bool
    {
        return !empty($this->moduleName);
    }

    /**
     * Get validation error message if invalid.
     */
    public function getValidationError(): ?string
    {
        if (empty($this->moduleName)) {
            return 'Module name is required';
        }

        return null;
    }

    public function toArray(): array
    {
        return [
            'module_name' => $this->moduleName,
            'modified_by' => $this->modifiedBy,
            'force' => $this->forceUpdate,
        ];
    }

    // DTOInterface implementations

    public static function fromArray(array $data): DTOInterface
    {
        return new self($data);
    }

    public function get(string $name)
    {
        $data = $this->toArray();
        return $data[$name] ?? null;
    }

    public static function validate(array $data): bool
    {
        return !empty($data['module_name'] ?? $data[0] ?? '');
    }

    public function __toString(): string
    {
        return json_encode($this->toArray());
    }
}