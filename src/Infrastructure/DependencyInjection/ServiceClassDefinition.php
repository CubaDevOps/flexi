<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\DependencyInjection;

use CubaDevOps\Flexi\Contracts\ServiceDefinitionContract;

class ServiceClassDefinition implements ServiceDefinitionContract
{
    private string $class;
    private array $arguments;

    public function __construct(string $class, array $arguments)
    {
        $this->class = $class;
        $this->arguments = $arguments;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @psalm-return ''
     */
    public function getMethod(): string
    {
        return '';
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }
}