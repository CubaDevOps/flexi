<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Interfaces;

use CubaDevOps\Flexi\Domain\ValueObjects\LogLevel;

interface LogInterface
{
    public function getLogLevel(): LogLevel;

    public function getMessage(): MessageInterface;

    public function getContext(): array;

    public function __toString(): string;
}
