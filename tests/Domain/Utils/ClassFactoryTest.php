<?php

namespace CubaDevOps\Flexi\Test\Domain\Utils;

use CubaDevOps\Flexi\Domain\Factories\RouterFactory;
use CubaDevOps\Flexi\Domain\Utils\ClassFactory;
use CubaDevOps\Flexi\Infrastructure\Classes\Configuration;
use CubaDevOps\Flexi\Infrastructure\Factories\ConfigurationFactory;
use CubaDevOps\Flexi\Infrastructure\Factories\ContainerFactory;
use CubaDevOps\Flexi\Infrastructure\Factories\CacheFactory;
use CubaDevOps\Flexi\Test\TestData\TestDoubles\HasNoConstructor;
use CubaDevOps\Flexi\Test\TestData\TestDoubles\IsNotInstantiable;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ClassFactoryTest extends TestCase
{
    private ClassFactory $classFactory;
    private ContainerInterface $container;

    public function setUp(): void
    {
        $this->classFactory = new ClassFactory(CacheFactory::getInstance());

        $this->container = ContainerFactory::getInstance('./tests/TestData/Configurations/container-test.json');
    }

    public function testBuild(): void
    {
        $configuration = $this->classFactory->build($this->container, Configuration::class);

        $this->assertInstanceOf(Configuration::class, $configuration);

        $this->assertNotEmpty($configuration->get('debug'));

        $this->assertEquals('[{level} - {time}]: {message} - {context}', $configuration->get('log_format'));
    }

    public function testBuildCache(): void
    {
        $configuration = $this->classFactory->build($this->container, Configuration::class);
        $configurationCache = $this->classFactory->build($this->container, Configuration::class);

        $this->assertInstanceOf(Configuration::class, $configurationCache);

        $this->assertEquals($configuration, $configurationCache);
    }

    public function testBuildIsNotInstantiable(): void
    {
        $notIstantiable = IsNotInstantiable::class;

        $this->expectException(\RuntimeException::class);

        $this->expectExceptionMessage('Class is not instantiable: '. $notIstantiable);

        $this->classFactory->build($this->container, $notIstantiable);
    }

    public function testBuildHasNoConstructor(): void
    {
        $hasNoConstructor = $this->classFactory->build($this->container, HasNoConstructor::class);

        $this->assertInstanceOf(HasNoConstructor::class, $hasNoConstructor);
    }

    public function testBuildFromFactory(): void
    {
        $configuration = $this->classFactory->buildFromFactory(
            $this->container, ConfigurationFactory::class, 'getInstance'
        );

        $this->assertInstanceOf(Configuration::class, $configuration);

        $this->assertNotEmpty($configuration->get('debug'));

        $this->assertEquals('[{level} - {time}]: {message} - {context}', $configuration->get('log_format'));
    }

    public function testBuildFromFactoryCache(): void
    {
        $configuration = $this->classFactory->buildFromFactory(
            $this->container, ConfigurationFactory::class, 'getInstance'
        );
        $configurationCache = $this->classFactory->buildFromFactory(
            $this->container, ConfigurationFactory::class, 'getInstance'
        );

        $this->assertInstanceOf(Configuration::class, $configurationCache);

        $this->assertEquals($configuration, $configurationCache);
    }

    public function testBuildFromFactoryNotStatic(): void
    {
        $method = 'get';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("$method need to be declared as static to use as factory method");

        $this->classFactory->buildFromFactory($this->container, Configuration::class, $method);
    }

    public function testBuildFromFactoryNotEnoughArguments(): void
    {
        $method = 'getInstance';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("$method has 5 required parameters");

        $this->classFactory->buildFromFactory($this->container, RouterFactory::class, $method);
    }
}
