<?php

namespace CubaDevOps\Flexi\Test\Domain\Classes;

use CubaDevOps\Flexi\Domain\Classes\CommandBus;
use CubaDevOps\Flexi\Domain\Classes\Container;
use CubaDevOps\Flexi\Domain\Classes\EventBus;
use CubaDevOps\Flexi\Domain\Classes\HtmlRender;
use CubaDevOps\Flexi\Domain\Classes\InFileLogRepository;
use CubaDevOps\Flexi\Domain\Classes\NativeSessionStorage;
use CubaDevOps\Flexi\Domain\Classes\QueryBus;
use CubaDevOps\Flexi\Domain\Classes\Router;
use CubaDevOps\Flexi\Domain\Classes\Service;
use CubaDevOps\Flexi\Domain\Classes\ServiceClassDefinition;
use CubaDevOps\Flexi\Domain\Classes\VersionRepository;
use CubaDevOps\Flexi\Domain\Exceptions\ContainerException;
use CubaDevOps\Flexi\Domain\Exceptions\ServiceNotFoundException;
use CubaDevOps\Flexi\Domain\Utils\ClassFactory;
use CubaDevOps\Flexi\Domain\ValueObjects\ServiceType;
use CubaDevOps\Flexi\Infrastructure\Classes\Configuration;
use CubaDevOps\Flexi\Infrastructure\Factories\ContainerFactory;
use InvalidArgumentException;
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
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws \JsonException
     */
    public function setUp(): void
    {
        $this->container = ContainerFactory::getInstance('./src/Config/services.json');
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
        $this->assertInstanceOf(Configuration::class, $this->container->get(Configuration::class));
        $this->assertInstanceOf(NativeSessionStorage::class, $this->container->get(NativeSessionStorage::class));
        $this->assertInstanceOf(ClassFactory::class, $this->container->get(ClassFactory::class));
        $this->assertInstanceOf(Router::class, $this->container->get(Router::class));
        $this->assertInstanceOf(VersionRepository::class, $this->container->get(VersionRepository::class));
        $this->assertInstanceOf(HtmlRender::class, $this->container->get('html_render'));
        $this->assertInstanceOf(CommandBus::class, $this->container->get(CommandBus::class));
        $this->assertInstanceOf(QueryBus::class, $this->container->get(QueryBus::class));
        $this->assertInstanceOf(EventBus::class, $this->container->get(EventBus::class));
        $this->assertInstanceOf(InFileLogRepository::class, $this->container->get(InFileLogRepository::class));
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
        $this->assertTrue($this->container->has(Configuration::class));
        $this->assertTrue($this->container->has(NativeSessionStorage::class));
        $this->assertTrue($this->container->has(ClassFactory::class));
        $this->assertTrue($this->container->has(Router::class));
        $this->assertTrue($this->container->has(VersionRepository::class));
        $this->assertTrue($this->container->has('html_render'));
        $this->assertTrue($this->container->has(CommandBus::class));
        $this->assertTrue($this->container->has(QueryBus::class));
        $this->assertTrue($this->container->has(EventBus::class));
        $this->assertTrue($this->container->has('logger'));
        $this->assertTrue($this->container->has(InFileLogRepository::class));
        $this->assertTrue($this->container->has(ResponseFactoryInterface::class));
        $this->assertTrue($this->container->has(ServerRequestFactoryInterface::class));
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
        $this->container->set('alias_service', Configuration::class);
        $this->assertInstanceOf(Configuration::class, $this->container->get('alias_service'));
    }

    /**
     * Test invalid service definition.
     */
    public function testInvalidServiceDefinition(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service definition must be an object, an array, or a string class name.');
        $this->container->set('invalid_service', 12345);
    }
}
