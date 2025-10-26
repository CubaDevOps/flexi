<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Classes;

use CubaDevOps\Flexi\Contracts\ServiceDefinitionContract;

class ServiceFactoryDefinition implements ServiceDefinitionContract
{
    private string $class;
    private string $method;
    private array $arguments;

    public function __construct(string $class, string $method, array $arguments)
    {
        $this->class = $class;
        $this->method = $method;
        $this->arguments = $arguments;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }
}
