<?php

declare(strict_types=1);

namespace Flexi\Test\Infrastructure\DependencyInjection;

use Flexi\Contracts\Interfaces\CacheInterface;
use Flexi\Contracts\Interfaces\ConfigurationInterface;
use Flexi\Contracts\Interfaces\ObjectBuilderInterface;
use Flexi\Infrastructure\DependencyInjection\Service;
use Flexi\Infrastructure\DependencyInjection\ServiceClassDefinition;
use Flexi\Domain\Exceptions\ServiceNotFoundException;
use Flexi\Domain\ValueObjects\ServiceType;
use Flexi\Infrastructure\Bus\CommandBus;
use Flexi\Infrastructure\Bus\EventBus;
use Flexi\Infrastructure\Bus\QueryBus;
use Flexi\Infrastructure\Classes\Configuration;
use Flexi\Infrastructure\DependencyInjection\Container;
use Flexi\Infrastructure\Factories\ContainerFactory;
use Flexi\Test\TestData\TestDoubles\DummyCache;
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

    /**
     * Test self-referencing service does not register (returns early).
     */
    public function testSelfReferencingServiceDoesNotRegister(): void
    {
        // Try to set container - should return early without registering
        $this->container->set('container', new \stdClass());

        // Container should still return itself
        $result = $this->container->get('container');
        $this->assertSame($this->container, $result);
    }

    /**
     * Test self-referencing with ContainerInterface does not register.
     */
    public function testSelfReferencingContainerInterfaceDoesNotRegister(): void
    {
        // Try to set ContainerInterface - should return early
        $this->container->set(\Psr\Container\ContainerInterface::class, new \stdClass());

        // Should still return container itself
        $result = $this->container->get(\Psr\Container\ContainerInterface::class);
        $this->assertSame($this->container, $result);
    }

    /**
     * Test getting cache service.
     */
    public function testGetCacheService(): void
    {
        $cache = $this->container->get('cache');
        $this->assertInstanceOf(\Flexi\Contracts\Interfaces\CacheInterface::class, $cache);
    }

    /**
     * Test getting factory service.
     */
    public function testGetFactoryService(): void
    {
        $factory = $this->container->get('factory');
        $this->assertInstanceOf(\Flexi\Contracts\Interfaces\ObjectBuilderInterface::class, $factory);
    }

    /**
     * Test resolving alias that cannot be resolved throws ServiceNotFoundException.
     */
    public function testResolveInvalidAliasThrowsException(): void
    {
        // Register a service with an alias that points to non-existent service
        $reflection = new \ReflectionClass(Container::class);
        $property = $reflection->getProperty('serviceDefinitions');
        $property->setAccessible(true);

        $definitions = $property->getValue($this->container);
        $definitions['invalid_alias'] = 'non_existent_service';
        $property->setValue($this->container, $definitions);

        // The actual exception thrown is ServiceNotFoundException
        $this->expectException(\Flexi\Domain\Exceptions\ServiceNotFoundException::class);
        $this->container->get('invalid_alias');
    }

    /**
     * Test set with object instance caches it directly.
     */
    public function testSetWithObjectInstanceCachesDirectly(): void
    {
        $service = new \stdClass();
        $service->test = 'value';

        $this->container->set('test_object', $service);

        $retrieved = $this->container->get('test_object');
        $this->assertSame($service, $retrieved);
        $this->assertEquals('value', $retrieved->test);
    }

    /**
     * Test set with callable is stored in definitions.
     */
    public function testSetWithCallableIsStoredInDefinitions(): void
    {
        $callable = function () {
            return new \stdClass();
        };

        $this->container->set('test_callable', $callable);
        $this->assertTrue($this->container->has('test_callable'));
    }

    /**
     * Test set does not overwrite existing service.
     */
    public function testSetDoesNotOverwriteExistingService(): void
    {
        $first = new \stdClass();
        $first->value = 'first';

        $this->container->set('test_service', $first);

        $second = new \stdClass();
        $second->value = 'second';

        // Try to set again - should not overwrite
        $this->container->set('test_service', $second);

        $retrieved = $this->container->get('test_service');
        $this->assertSame($first, $retrieved);
        $this->assertEquals('first', $retrieved->value);
    }

    /**
     * Test get with array definition builds from definition.
     */
    public function testGetWithArrayDefinitionBuildsFromDefinition(): void
    {
        $this->assertTrue($this->container->has(CommandBus::class));
        $service = $this->container->get(CommandBus::class);
        $this->assertInstanceOf(CommandBus::class, $service);
    }

    /**
     * Test has returns true for cached services.
     */
    public function testHasReturnsTrueForCachedServices(): void
    {
        $service = new \stdClass();
        $this->container->set('cached_test', $service);

        $this->assertTrue($this->container->has('cached_test'));
    }

    /**
     * Test getting ContainerInterface returns container itself.
     */
    public function testGetContainerInterfaceReturnsSelf(): void
    {
        $container = $this->container->get(\Psr\Container\ContainerInterface::class);
        $this->assertSame($this->container, $container);
    }
}
