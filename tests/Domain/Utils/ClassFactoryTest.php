<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Domain\Utils;

use CubaDevOps\Flexi\Infrastructure\Classes\Configuration;
use CubaDevOps\Flexi\Infrastructure\Classes\ConfigurationRepository;
use CubaDevOps\Flexi\Infrastructure\Classes\ObjectBuilder;
use CubaDevOps\Flexi\Modules\Cache\Infrastructure\Factories\CacheFactory;
use CubaDevOps\Flexi\Infrastructure\Factories\ContainerFactory;
use CubaDevOps\Flexi\Test\TestData\TestDoubles\HasNoConstructor;
use CubaDevOps\Flexi\Test\TestData\TestDoubles\IsNotInstantiable;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ClassFactoryTest extends TestCase
{
    private ObjectBuilder $classFactory;
    private ContainerInterface $container;

    public function setUp(): void
    {
        $cache = (new CacheFactory(new Configuration(new ConfigurationRepository())))->getInstance();
        $this->classFactory = new ObjectBuilder($cache);

        $this->container = ContainerFactory::createDefault('./tests/TestData/Configurations/container-test.json');
    }

    public function testBuild(): void
    {
        // Use container to get Configuration since it requires ConfigurationRepositoryInterface
        $configuration = $this->container->get(Configuration::class);

        $this->assertInstanceOf(Configuration::class, $configuration);

        $this->assertNotEmpty($configuration->get('debug'));

        $this->assertEquals('[{level} - {time}]: {message} - {context}', $configuration->get('log_format'));
    }

    public function testBuildIsNotInstantiable(): void
    {
        $notIstantiable = IsNotInstantiable::class;

        $this->expectException(\RuntimeException::class);

        $this->expectExceptionMessage('Class is not instantiable: '.$notIstantiable);

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
                'class' => ContainerFactory::class,
                'method' => 'createDefault',
                'arguments' => [],
            ],
        ];
        $container = $this->classFactory->buildFromDefinition($this->container, $factory_definition);
        $this->assertInstanceOf(ContainerInterface::class, $container);
    }

    public function testBuildFromClassDefinition(): void
    {
        $classDefinition = [
            'class' => [
                'name' => HasNoConstructor::class,
                'arguments' => [],
            ],
        ];

        $instance = $this->classFactory->buildFromDefinition($this->container, $classDefinition);

        $this->assertInstanceOf(HasNoConstructor::class, $instance);
    }
}
