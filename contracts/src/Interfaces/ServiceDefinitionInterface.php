<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Interfaces;

interface ServiceDefinitionInterface
{
    public function getClass(): string;

    public function getMethod(): string;

    public function getArguments(): array;
}
