<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\DependencyInjection;

use Flexi\Contracts\Interfaces\ServiceDefinitionInterface;
use CubaDevOps\Flexi\Domain\ValueObjects\ServiceType;

class Service
{
    private ServiceType $type;
    private ServiceDefinitionInterface $definition;
    private string $name;

    public function __construct(
        string $name,
        ServiceType $type,
        ServiceDefinitionInterface $definition
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->definition = $definition;
    }

    public function getType(): ServiceType
    {
        return $this->type;
    }

    public function getDefinition(): ServiceDefinitionInterface
    {
        return $this->definition;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @throws \JsonException
     */
    public function __toString(): string
    {
        // TODO: better implementation maybe using serialization
        return json_encode($this, JSON_THROW_ON_ERROR);
    }
}