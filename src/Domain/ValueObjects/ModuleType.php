<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\ValueObjects;

/**
 * Class representing the type of module installation.
 *
 * Defines how a module is installed and where it's located:
 * - LOCAL: Module is installed in modules/ directory (development/customizable)
 * - VENDOR: Module is installed via Composer in vendor/ directory (packaged)
 * - MIXED: Module exists in both locations (conflict situation)
 */
class ModuleType
{
    public const LOCAL = 'local';
    public const VENDOR = 'vendor';
    public const MIXED = 'mixed';

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function local(): self
    {
        return new self(self::LOCAL);
    }

    public static function vendor(): self
    {
        return new self(self::VENDOR);
    }

    public static function mixed(): self
    {
        return new self(self::MIXED);
    }

    public static function fromString(string $value): self
    {
        switch ($value) {
            case self::LOCAL:
                return self::local();
            case self::VENDOR:
                return self::vendor();
            case self::MIXED:
                return self::mixed();
            default:
                throw new \InvalidArgumentException("Invalid module type: {$value}");
        }
    }

    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Get a human-readable description of the module type.
     */
    public function getDescription(): string
    {
        switch ($this->value) {
            case self::LOCAL:
                return 'Local development module (modules/)';
            case self::VENDOR:
                return 'Composer package (vendor/)';
            case self::MIXED:
                return 'Installed in both locations (conflict)';
            default:
                return 'Unknown';
        }
    }

    /**
     * Check if the module type indicates a development/customizable module.
     */
    public function isDevelopment(): bool
    {
        return $this->value === self::LOCAL || $this->value === self::MIXED;
    }

    /**
     * Check if the module type indicates a packaged module.
     */
    public function isPackaged(): bool
    {
        return $this->value === self::VENDOR || $this->value === self::MIXED;
    }

    /**
     * Check if this type represents a conflict situation.
     */
    public function hasConflict(): bool
    {
        return $this->value === self::MIXED;
    }

    public function equals(ModuleType $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}