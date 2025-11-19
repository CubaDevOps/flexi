<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Application\Commands;

use Flexi\Contracts\Interfaces\CliDTOInterface;
use Flexi\Contracts\Interfaces\DTOInterface;

/**
 * Command DTO for getting module status information.
 *
 * Represents the parameters needed to get status information about modules.
 * Optionally can filter by specific module name.
 */
class ModuleStatusCommand implements CliDTOInterface
{
    private ?string $moduleName;
    private bool $showDetails;
    private bool $showConflicts;
    private ?string $filterByType;

    public function __construct(array $args = [])
    {
        $this->moduleName = $args['module_name'] ?? $args[0] ?? null;
        $this->showDetails = (bool) ($args['details'] ?? $args['--details'] ?? false);
        $this->showConflicts = (bool) ($args['conflicts'] ?? $args['--conflicts'] ?? false);
        $this->filterByType = $args['type'] ?? $args['--type'] ?? null;
    }

    public function getModuleName(): ?string
    {
        return $this->moduleName;
    }

    public function shouldShowDetails(): bool
    {
        return $this->showDetails;
    }

    public function shouldShowConflicts(): bool
    {
        return $this->showConflicts;
    }

    public function getFilterByType(): ?string
    {
        return $this->filterByType;
    }

    /**
     * Check if this is a request for all modules.
     */
    public function isAllModules(): bool
    {
        return empty($this->moduleName);
    }

    /**
     * Check if this is a request for a specific module.
     */
    public function isSpecificModule(): bool
    {
        return !empty($this->moduleName);
    }

    /**
     * Validate the command parameters.
     */
    public function isValid(): bool
    {
        // Type filter validation if provided
        if ($this->filterByType && !in_array($this->filterByType, ['local', 'vendor', 'mixed'])) {
            return false;
        }

        return true;
    }

    /**
     * Get validation error message if invalid.
     */
    public function getValidationError(): ?string
    {
        if ($this->filterByType && !in_array($this->filterByType, ['local', 'vendor', 'mixed'])) {
            return "Invalid type filter '{$this->filterByType}'. Valid types: local, vendor, mixed";
        }

        return null;
    }

    public function toArray(): array
    {
        return [
            'module_name' => $this->moduleName,
            'details' => $this->showDetails,
            'conflicts' => $this->showConflicts,
            'type' => $this->filterByType,
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
        $filterByType = $data['type'] ?? $data['--type'] ?? null;

        if ($filterByType && !in_array($filterByType, ['local', 'vendor', 'mixed'])) {
            return false;
        }

        return true;
    }

    public function __toString(): string
    {
        return json_encode($this->toArray());
    }

    public function usage(): string
    {
        return "Usage: module:status module_name=module [--details] [--conflicts] [--type=local|vendor|mixed]";
    }
}