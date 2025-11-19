<?php

declare(strict_types=1);

namespace Flexi\Domain\ValueObjects;

use DateTimeImmutable;

/**
 * Value Object representing the state of a module.
 *
 * Contains activation status and metadata about when
 * the state was last modified and by what operation.
 */
class ModuleState
{
    private string $moduleName;
    private bool $active;
    private ModuleType $type;
    private DateTimeImmutable $lastModified;
    private ?string $modifiedBy;
    private array $metadata;

    public function __construct(
        string $moduleName,
        bool $active = true,
        ?ModuleType $type = null,
        ?DateTimeImmutable $lastModified = null,
        ?string $modifiedBy = null,
        array $metadata = []
    ) {
        $this->moduleName = $moduleName;
        $this->active = $active;
        $this->type = $type ?? ModuleType::local();
        $this->lastModified = $lastModified ?? new DateTimeImmutable();
        $this->modifiedBy = $modifiedBy;
        $this->metadata = $metadata;
    }

    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getType(): ModuleType
    {
        return $this->type;
    }

    public function getLastModified(): DateTimeImmutable
    {
        return $this->lastModified;
    }

    public function getModifiedBy(): ?string
    {
        return $this->modifiedBy;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getMetadataValue(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Create a new state with activation status changed.
     */
    public function activate(?string $modifiedBy = null): self
    {
        return new self(
            $this->moduleName,
            true,
            $this->type,
            new DateTimeImmutable(),
            $modifiedBy ?? $this->modifiedBy,
            $this->metadata
        );
    }

    /**
     * Create a new state with deactivation status changed.
     */
    public function deactivate(?string $modifiedBy = null): self
    {
        return new self(
            $this->moduleName,
            false,
            $this->type,
            new DateTimeImmutable(),
            $modifiedBy ?? $this->modifiedBy,
            $this->metadata
        );
    }

    /**
     * Create a new state with different type.
     */
    public function withType(ModuleType $type, ?string $modifiedBy = null): self
    {
        return new self(
            $this->moduleName,
            $this->active,
            $type,
            new DateTimeImmutable(),
            $modifiedBy ?? $this->modifiedBy,
            $this->metadata
        );
    }

    /**
     * Create a new state with updated metadata.
     */
    public function withMetadata(array $metadata, ?string $modifiedBy = null): self
    {
        return new self(
            $this->moduleName,
            $this->active,
            $this->type,
            new DateTimeImmutable(),
            $modifiedBy ?? $this->modifiedBy,
            array_merge($this->metadata, $metadata)
        );
    }

    /**
     * Convert to array representation for persistence.
     */
    public function toArray(): array
    {
        return [
            'active' => $this->active,
            'type' => $this->type->getValue(),
            'lastModified' => $this->lastModified->format(DateTimeImmutable::ATOM),
            'modifiedBy' => $this->modifiedBy,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Create from array representation.
     */
    public static function fromArray(string $moduleName, array $data): self
    {
        $lastModified = isset($data['lastModified'])
            ? new DateTimeImmutable($data['lastModified'])
            : new DateTimeImmutable();

        $type = isset($data['type'])
            ? ModuleType::fromString($data['type'])
            : ModuleType::local();

        return new self(
            $moduleName,
            $data['active'] ?? true,
            $type,
            $lastModified,
            $data['modifiedBy'] ?? null,
            $data['metadata'] ?? []
        );
    }

    /**
     * Create default active state for a module.
     */
    public static function createActive(string $moduleName, ModuleType $type = null): self
    {
        return new self(
            $moduleName,
            true,
            $type ?? ModuleType::local(),
            new DateTimeImmutable(),
            'system',
            []
        );
    }

    /**
     * Create default inactive state for a module.
     */
    public static function createInactive(string $moduleName, ModuleType $type = null): self
    {
        return new self(
            $moduleName,
            false,
            $type ?? ModuleType::local(),
            new DateTimeImmutable(),
            'system',
            []
        );
    }
}