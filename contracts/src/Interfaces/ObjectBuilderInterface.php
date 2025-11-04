<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Interfaces;

use Psr\Container\ContainerInterface;

interface ObjectBuilderInterface
{
    public function build(ContainerInterface $container, string $className): object;

    public function buildFromDefinition(ContainerInterface $container, array $serviceDefinition): object;
}
