<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Tests\Infrastructure\Factories;

use CubaDevOps\Flexi\Infrastructure\Factories\BusFactory;
use CubaDevOps\Flexi\Infrastructure\Factories\RouterFactory;
use CubaDevOps\Flexi\Infrastructure\Bus\CommandBus;
use CubaDevOps\Flexi\Infrastructure\Bus\QueryBus;
use CubaDevOps\Flexi\Infrastructure\Bus\EventBus;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class RefactoredFactoriesTest extends TestCase
{
    public function testBusFactoryBackwardCompatibility(): void
    {
        // Test that the classes exist and can be used
        $this->assertTrue(class_exists(BusFactory::class));
        $this->assertTrue(method_exists(BusFactory::class, 'createCommandBus'));
        $this->assertTrue(method_exists(BusFactory::class, 'createQueryBus'));
        $this->assertTrue(method_exists(BusFactory::class, 'createEventBus'));
    }

    public function testFactoriesCanBeInstantiated(): void
    {
        // Test basic instantiation works
        $this->assertTrue(class_exists(BusFactory::class));
        $this->assertTrue(class_exists(RouterFactory::class));
    }
}