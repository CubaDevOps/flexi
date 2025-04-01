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
use CubaDevOps\Flexi\Domain\Utils\ClassFactory;
use CubaDevOps\Flexi\Domain\ValueObjects\ServiceType;
use CubaDevOps\Flexi\Infrastructure\Classes\Configuration;
use CubaDevOps\Flexi\Infrastructure\Factories\CacheFactory;
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
        $this->container = new Container(CacheFactory::getInstance());

        $this->container->loadServices('./src/Config/services.json');
    }

    /**
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
        $this->assertInstanceOf(
            ResponseFactoryInterface::class,
            $this->container->get(ResponseFactoryInterface::class)
        );
        $this->assertInstanceOf(
            ServerRequestFactoryInterface::class,
            $this->container->get(ServerRequestFactoryInterface::class)
        );
    }

    /**
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetServiceReturnSelf(): void
    {
        $this->assertInstanceOf(Container::class, $this->container->get('container'));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws \ReflectionException
     * @throws NotFoundExceptionInterface
     */
    public function testGetServiceDoesNotExist(): void
    {
        $service = Service::class;
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Service not found: ' . $service);
        $this->container->get($service);
    }

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

    public function testIsAlias(): void
    {
        $this->assertTrue($this->container->isAlias('session'));
        $this->assertFalse($this->container->isAlias('logger'));
    }

    public function testAddService(): void
    {
        $this->container->addService(
            'test',
            new Service(
                'test', new ServiceType('alias'), new ServiceClassDefinition(
                Configuration::class, []
            )
            )
        );
        $this->assertTrue($this->container->has('test'));
    }
}
