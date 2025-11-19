<?php

declare(strict_types=1);

namespace Flexi\Domain\ValueObjects;

/**
 * Value Object representing a configuration file type
 */
final class ConfigurationType
{
    private string $value;

    public const SERVICES = 'services';
    public const ROUTES = 'routes';
    public const COMMANDS = 'commands';
    public const QUERIES = 'queries';
    public const LISTENERS = 'listeners';

    private const VALID_TYPES = [
        self::SERVICES,
        self::ROUTES,
        self::COMMANDS,
        self::QUERIES,
        self::LISTENERS,
    ];

    public function __construct(string $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    /**
     * Create Services configuration type
     */
    public static function services(): self
    {
        return new self(self::SERVICES);
    }

    /**
     * Create Routes configuration type
     */
    public static function routes(): self
    {
        return new self(self::ROUTES);
    }

    /**
     * Create Commands configuration type
     */
    public static function commands(): self
    {
        return new self(self::COMMANDS);
    }

    /**
     * Create Queries configuration type
     */
    public static function queries(): self
    {
        return new self(self::QUERIES);
    }

    /**
     * Create Listeners configuration type
     */
    public static function listeners(): self
    {
        return new self(self::LISTENERS);
    }

    /**
     * Get the string value
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Get all supported configuration types
     *
     * @return string[]
     */
    public static function getAllTypes(): array
    {
        return self::VALID_TYPES;
    }

    /**
     * Check if a type is valid
     */
    public static function isValidType(string $type): bool
    {
        return in_array($type, self::VALID_TYPES, true);
    }

    /**
     * Compare with another ConfigurationType
     */
    public function equals(ConfigurationType $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Validate the configuration type
     *
     * @throws \InvalidArgumentException
     */
    private function validate(string $value): void
    {
        if (!in_array($value, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid configuration type "%s". Valid types: %s',
                    $value,
                    implode(', ', self::VALID_TYPES)
                )
            );
        }
    }
}