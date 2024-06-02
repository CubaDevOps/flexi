<?php

namespace CubaDevOps\Flexi\Domain\Classes;

use CubaDevOps\Flexi\Domain\Interfaces\ServiceDefinitionInterface;
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
        return json_encode($this, JSON_THROW_ON_ERROR);
    }
}
