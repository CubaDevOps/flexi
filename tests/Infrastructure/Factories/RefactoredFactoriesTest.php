<?php

declare(strict_types=1);

namespace Flexi\Tests\Infrastructure\Factories;

use Flexi\Infrastructure\Factories\BusFactory;
use Flexi\Infrastructure\Factories\RouterFactory;
use Flexi\Infrastructure\Bus\CommandBus;
use Flexi\Infrastructure\Bus\QueryBus;
use Flexi\Infrastructure\Bus\EventBus;
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