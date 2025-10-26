<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Classes;

use CubaDevOps\Flexi\Contracts\LogContract;
use CubaDevOps\Flexi\Contracts\MessageContract;
use CubaDevOps\Flexi\Domain\ValueObjects\LogLevel;

class Log implements LogContract
{
    private LogLevel $log_level;
    private MessageContract $message;
    private array $context;

    public function __construct(
        LogLevel $log_level,
        MessageContract $message,
        array $context = []
    ) {
        $this->log_level = $log_level;
        $this->message = $message;
        $this->context = $context;
    }

    public function getLogLevel(): LogLevel
    {
        return $this->log_level;
    }

    public function getMessage(): MessageContract
    {
        return $this->message;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function __toString(): string
    {
        return $this->message->__toString();
    }
}
