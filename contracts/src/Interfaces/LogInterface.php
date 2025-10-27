<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Interfaces;

use CubaDevOps\Flexi\Contracts\ValueObjects\LogLevel;

interface LogInterface
{
    public function getLogLevel(): LogLevel;

    public function getMessage(): MessageInterface;

    public function getContext(): array;

    public function __toString(): string;
}
