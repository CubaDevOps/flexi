<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts;

interface ServiceDefinitionContract
{
    public function getClass(): string;

    public function getMethod(): string;

    public function getArguments(): array;
}