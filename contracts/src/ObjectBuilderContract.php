<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts;

use Psr\Container\ContainerInterface;

interface ObjectBuilderContract
{
    public function build(ContainerInterface $container, string $className): object;

    public function buildFromDefinition(ContainerInterface $container, array $serviceDefinition): object;
}