<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Classes;

use CubaDevOps\Flexi\Contracts\Interfaces\LogInterface;;
use CubaDevOps\Flexi\Contracts\Interfaces\MessageInterface;;
use CubaDevOps\Flexi\Contracts\ValueObjects\LogLevel;

class Log implements LogInterface
{
    private LogLevel $log_level;
    private MessageInterface $message;
    private array $context;

    public function __construct(
        LogLevel $log_level,
        MessageInterface $message,
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

    public function getMessage(): MessageInterface
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
