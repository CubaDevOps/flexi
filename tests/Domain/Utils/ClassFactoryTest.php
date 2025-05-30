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

    public function testBuildIsNotInstantiable(): void
    {
        $notIstantiable = IsNotInstantiable::class;

        $this->expectException(\RuntimeException::class);

        $this->expectExceptionMessage('Class is not instantiable: ' . $notIstantiable);

        $this->classFactory->build($this->container, $notIstantiable);
    }

    public function testBuildHasNoConstructor(): void
    {
        $hasNoConstructor = $this->classFactory->build($this->container, HasNoConstructor::class);

        $this->assertInstanceOf(HasNoConstructor::class, $hasNoConstructor);
    }

    public function testBuildFromFactory(): void
    {
        $factory_definition = [
            'factory' => [
                'class' => ConfigurationFactory::class,
                'method' => 'getInstance',
                'arguments' => []
            ]
        ];
        $configuration = $this->classFactory->buildFromDefinition($this->container, $factory_definition);
        $this->assertInstanceOf(Configuration::class, $configuration);
        $this->assertNotEmpty($configuration->get('debug'));
    }

    public function testBuildFromClassDefinition(): void
    {
        $classDefinition = [
            'class' => [
                'name' => Configuration::class,
                'arguments' => []
            ]
        ];

        $configuration = $this->classFactory->buildFromDefinition($this->container, $classDefinition);

        $this->assertInstanceOf(Configuration::class, $configuration);

        $this->assertNotEmpty($configuration->get('debug'));
    }
}
