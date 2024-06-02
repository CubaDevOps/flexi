<?php

namespace CubaDevOps\Flexi\Domain\Interfaces;

interface ServiceDefinitionInterface
{
    public function getClass(): string;

    public function getMethod(): string;

    public function getArguments(): array;
}
