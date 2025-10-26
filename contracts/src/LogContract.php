<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts;

use CubaDevOps\Flexi\Contracts\ValueObjects\LogLevel;

interface LogContract
{
    public function getLogLevel(): LogLevel;

    public function getMessage(): MessageContract;

    public function getContext(): array;

    public function __toString(): string;
}
