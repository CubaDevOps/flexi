<?php

namespace CubaDevOps\Flexi\Domain\ValueObjects;

use CubaDevOps\Flexi\Domain\Interfaces\ValueObjectInterface;

/**
 * Describes log levels.
 */
class LogLevel implements ValueObjectInterface
{
    public const EMERGENCY = 'emergency';
    public const ALERT = 'alert';
    public const CRITICAL = 'critical';
    public const ERROR = 'error';
    public const WARNING = 'warning';
    public const NOTICE = 'notice';
    public const INFO = 'info';
    public const DEBUG = 'debug';

    private array $levels = [
        self::EMERGENCY,
        self::ALERT,
        self::CRITICAL,
        self::ERROR,
        self::WARNING,
        self::NOTICE,
        self::INFO,
        self::DEBUG,
    ];

    private string $level;

    public function __construct(string $level)
    {
        if (!$this->isValidLevel($level)) {
            throw new \InvalidArgumentException("Invalid log level: $level");
        }
        $this->level = $level;
    }

    public function getValue(): string
    {
        return $this->level;
    }

    private function isValidLevel(string $level): bool
    {
        return in_array($level, $this->levels, true);
    }
}
