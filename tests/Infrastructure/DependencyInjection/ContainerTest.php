<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Infrastructure\DependencyInjection;

use Flexi\Contracts\Interfaces\CacheInterface;
use Flexi\Contracts\Interfaces\ConfigurationInterface;
use Flexi\Contracts\Interfaces\ObjectBuilderInterface;
use CubaDevOps\Flexi\Infrastructure\DependencyInjection\Service;
use CubaDevOps\Flexi\Infrastructure\DependencyInjection\ServiceClassDefinition;
use CubaDevOps\Flexi\Domain\Exceptions\ServiceNotFoundException;
use CubaDevOps\Flexi\Domain\ValueObjects\ServiceType;
use CubaDevOps\Flexi\Infrastructure\Bus\CommandBus;
use CubaDevOps\Flexi\Infrastructure\Bus\EventBus;
use CubaDevOps\Flexi\Infrastructure\Bus\QueryBus;
use CubaDevOps\Flexi\Infrastructure\Classes\Configuration;
use CubaDevOps\Flexi\Infrastructure\DependencyInjection\Container;
use CubaDevOps\Flexi\Infrastructure\Factories\ContainerFactory;
use CubaDevOps\Flexi\Test\TestData\TestDoubles\DummyCache;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;

class ContainerTest extends TestCase
{
    private Container $container;

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws \JsonException
     */
    public function setUp(): void
    {
        // Create a fresh container instance for each test
        $this->container = ContainerFactory::createDefault('./src/Config/services.json');

        // Replace the cache with DummyCache to avoid cache interference in tests
        $this->container->set(CacheInterface::class, new DummyCache());
    }

    /**
     * Test retrieving services from the container.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function testGetService(): void
    {
        $this->assertInstanceOf(ConfigurationInterface::class, $this->container->get(ConfigurationInterface::class));
        // SessionStorageInterface removed - requires Session module
        $this->assertInstanceOf(ObjectBuilderInterface::class, $this->container->get(ObjectBuilderInterface::class));
        // router service is alias - tested via router factory
        // html_render removed - no longer exists in services
        $this->assertInstanceOf(CommandBus::class, $this->container->get(CommandBus::class));
        $this->assertInstanceOf(QueryBus::class, $this->container->get(QueryBus::class));
        $this->assertInstanceOf(EventBus::class, $this->container->get(EventBus::class));
        // LogRepositoryInterface removed - requires Logging module
        $this->assertInstanceOf(ResponseFactoryInterface::class, $this->container->get(ResponseFactoryInterface::class));
        $this->assertInstanceOf(ServerRequestFactoryInterface::class, $this->container->get(ServerRequestFactoryInterface::class));
    }

    /**
     * Test retrieving the container itself.
     *
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetServiceReturnSelf(): void
    {
        $this->assertInstanceOf(Container::class, $this->container->get('container'));
    }

    /**
     * Test retrieving a service that does not exist.
     *
     * @throws ContainerExceptionInterface
     * @throws \ReflectionException
     * @throws NotFoundExceptionInterface
     */
    public function testGetServiceDoesNotExist(): void
    {
        $service = 'non_existent_service';
        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage(sprintf('Class %s does not exist', $service));
        $this->container->get($service);
    }

    /**
     * Test checking if services exist in the container.
     */
    public function testHasService(): void
    {
        $this->assertTrue($this->container->has(ConfigurationInterface::class), ConfigurationInterface::class.' not found');
        // SessionStorageInterface removed - requires Session module
        $this->assertTrue($this->container->has(ObjectBuilderInterface::class), ObjectBuilderInterface::class.' not found');
        // router is alias - tested via router factory
        // html_render removed - no longer exists in services
        $this->assertTrue($this->container->has(CommandBus::class), CommandBus::class.' not found');
        $this->assertTrue($this->container->has(QueryBus::class), QueryBus::class.' not found');
        $this->assertTrue($this->container->has(EventBus::class), EventBus::class.' not found');
        // logger and LogRepositoryInterface removed - require Logging module
        $this->assertTrue($this->container->has(ResponseFactoryInterface::class), ResponseFactoryInterface::class.' not found');
        $this->assertTrue($this->container->has(ServerRequestFactoryInterface::class), ServerRequestFactoryInterface::class.' not found');
    }

    /**
     * Test adding a new service to the container.
     */
    public function testAddService(): void
    {
        $this->container->set(
            'test',
            new Service(
                'test',
                new ServiceType('alias'),
                new ServiceClassDefinition(Configuration::class, [])
            )
        );
        $this->assertTrue($this->container->has('test'));
    }

    /**
     * Test resolving aliases.
     */
    public function testResolveAlias(): void
    {
        $this->container->set('alias_service', ConfigurationInterface::class);
        $this->assertInstanceOf(ConfigurationInterface::class, $this->container->get('alias_service'));
    }

    /**
     * Test invalid service definition.
     */
    public function testInvalidServiceDefinition(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Service definition must be an object, an array, or a string class name.');
        $this->container->set('invalid_service', 12345);
    }
}
