<?php

declare(strict_types=1);

namespace Flexi\Domain\ValueObjects;

/**
 * Value Object representing complete information about a module.
 *
 * Contains all relevant data about a module including its location,
 * installation type, activation status, and metadata.
 */
class ModuleInfo
{
    private string $name;
    private string $package;
    private ModuleType $type;
    private string $path;
    private ?string $version;
    private bool $isActive;
    private array $metadata;

    public function __construct(
        string $name,
        string $package,
        ModuleType $type,
        string $path,
        ?string $version = null,
        bool $isActive = false,
        array $metadata = []
    ) {
        $this->name = $name;
        $this->package = $package;
        $this->type = $type;
        $this->path = $path;
        $this->version = $version;
        $this->isActive = $isActive;
        $this->metadata = $metadata;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPackage(): string
    {
        return $this->package;
    }

    public function getType(): ModuleType
    {
        return $this->type;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function isActive(): bool
    {
        return $this->isActive;
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
     * Create a copy with activation status changed.
     */
    public function withActivationStatus(bool $isActive): self
    {
        return new self(
            $this->name,
            $this->package,
            $this->type,
            $this->path,
            $this->version,
            $isActive,
            $this->metadata
        );
    }

    /**
     * Create a copy with updated metadata.
     */
    public function withMetadata(array $metadata): self
    {
        return new self(
            $this->name,
            $this->package,
            $this->type,
            $this->path,
            $this->version,
            $this->isActive,
            array_merge($this->metadata, $metadata)
        );
    }

    /**
     * Create a copy with a different type (useful for migration scenarios).
     */
    public function withType(ModuleType $type): self
    {
        return new self(
            $this->name,
            $this->package,
            $type,
            $this->path,
            $this->version,
            $this->isActive,
            $this->metadata
        );
    }

    /**
     * Create a copy with a different path (useful for migration scenarios).
     */
    public function withPath(string $path): self
    {
        return new self(
            $this->name,
            $this->package,
            $this->type,
            $path,
            $this->version,
            $this->isActive,
            $this->metadata
        );
    }

    /**
     * Check if this module has a conflict (exists in multiple locations).
     */
    public function hasConflict(): bool
    {
        return $this->type->hasConflict();
    }

    /**
     * Check if this is a development module (customizable).
     */
    public function isDevelopment(): bool
    {
        return $this->type->isDevelopment();
    }

    /**
     * Check if this is a packaged module.
     */
    public function isPackaged(): bool
    {
        return $this->type->isPackaged();
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'package' => $this->package,
            'type' => $this->type->getValue(),
            'path' => $this->path,
            'version' => $this->version,
            'isActive' => $this->isActive,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Create from array representation.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'] ?? '',
            $data['package'] ?? '',
            ModuleType::fromString($data['type'] ?? ModuleType::LOCAL),
            $data['path'] ?? '',
            $data['version'] ?? null,
            $data['isActive'] ?? false,
            $data['metadata'] ?? []
        );
    }
}