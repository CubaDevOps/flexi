<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\ValueObjects;

use CubaDevOps\Flexi\Contracts\Interfaces\ValueObjectInterface;;

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
        0 => self::DEBUG,
        1 => self::INFO,
        2 => self::NOTICE,
        3 => self::WARNING,
        4 => self::ERROR,
        5 => self::ALERT,
        6 => self::CRITICAL,
        7 => self::EMERGENCY,
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

    public function isBelowThreshold(LogLevel $threshold): bool
    {
        $currentLevelIndex = array_search($this->level, $this->levels, true);
        $thresholdLevelIndex = array_search($threshold->getValue(), $this->levels, true);

        return $currentLevelIndex < $thresholdLevelIndex;
    }

    public function equals(ValueObjectInterface $other): bool
    {
        return $other instanceof self && $this->level === $other->getValue();
    }

    public function __toString(): string
    {
        return $this->level;
    }
}
