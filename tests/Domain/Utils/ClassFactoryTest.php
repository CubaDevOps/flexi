<?php

namespace CubaDevOps\Flexi\Test\Domain\Utils;

use CubaDevOps\Flexi\Domain\Factories\ContainerFactory;
use CubaDevOps\Flexi\Domain\Factories\RouterFactory;
use CubaDevOps\Flexi\Domain\Utils\ClassFactory;
use CubaDevOps\Flexi\Infrastructure\Classes\Configuration;
use CubaDevOps\Flexi\Infrastructure\Factories\ConfigurationFactory;
use CubaDevOps\Flexi\Test\TestData\TestTools\HasNoConstructor;
use CubaDevOps\Flexi\Test\TestData\TestTools\IsNotInstantiable;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionMethod;

class ClassFactoryTest extends TestCase
{
    private ClassFactory $classFactory;
    private ReflectionMethod $resolveArguments;
    private ReflectionMethod $getParameterClassName;
    private ContainerInterface $container;

    public function setUp(): void
    {
        $this->classFactory = new ClassFactory();
        $class = new \ReflectionClass(ClassFactory::class);

        $this->resolveArguments = $class->getMethod('resolveArguments');
        $this->getParameterClassName = $class->getMethod('getParameterClassName');

        $this->container = ContainerFactory::getInstance(__DIR__ .'/../../TestData/Configurations/container-test.json');
    }

    public function testBuild(): void
    {
        $configuration = $this->classFactory->build($this->container, Configuration::class);

        $this->assertInstanceOf(Configuration::class, $configuration);

        $this->assertEquals('true', $configuration->get('debug'));

        $this->assertEquals('[{level} - {time}]: {message} - {context}', $configuration->get('log_format'));
    }

    public function testBuildCache(): void
    {
        $configuration = $this->classFactory->build($this->container, Configuration::class);
        $configurationCache = $this->classFactory->build($this->container, Configuration::class);

        $this->assertInstanceOf(Configuration::class, $configurationCache);

        $this->assertEquals('true', $configurationCache->get('debug'));

        $this->assertEquals('[{level} - {time}]: {message} - {context}', $configurationCache->get('log_format'));
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

        $this->assertEquals('true', $configuration->get('debug'));

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

        $this->assertEquals('true', $configurationCache->get('debug'));

        $this->assertEquals('[{level} - {time}]: {message} - {context}', $configurationCache->get('log_format'));
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

    public function testResolveArgumentsParamName(): void
    {
        $method = $this->createMock(\ReflectionFunctionAbstract::class);
        $parameter = $this->createMock(\ReflectionParameter::class);
        $container = $this->createMock(ContainerInterface::class);

        $paramName = 'paramName';

        $method->expects($this->once())->method('getParameters')
            ->willReturn([0 => $parameter]);

        $parameter->expects($this->atLeast(2))
            ->method('getName')->willReturn($paramName);

        $container->expects($this->once())->method('has')->willReturn(true);
        $container->expects($this->once())->method('get')->willReturnSelf();

        $dependencies = $this->resolveArguments
            ->invokeArgs($this->classFactory, [$method, $container, []]);

        $this->assertNotEmpty($dependencies);
    }

    //TODO: isObject use case is challenging to hit, maybe make it more testable
//    public function testResolveArgumentsIsObject(): void
//    {
//        $method = $this->createMock(\ReflectionFunctionAbstract::class);
//        $parameter = $this->createMock(\ReflectionParameter::class);
//        $parameterType = $this->createMock(\ReflectionNamedType::class);
//        $container = $this->createMock(ContainerInterface::class);
//
//        $paramName = 'paramName';
//
//        $method->expects($this->once())->method('getParameters')
//            ->willReturn([0 => $parameter]);
//
//        $parameter->expects($this->atLeast(2))
//            ->method('getName')->willReturn($paramName);
//
//        $parameter->expects($this->atLeast(2))
//            ->method('getType')->willReturn($parameterType);
//
//        $parameterType->expects($this->atLeast(2))
//            ->method('getName')
//            ->willReturn(\ReflectionParameter::class);
//
//        $container->expects($this->atLeast(2))
//            ->method('has')->willReturn(false);
//
//        $container->expects($this->once())->method('get')->willReturnSelf();
//
//        $dependencies = $this->reflectionMethod
//            ->invokeArgs($this->classFactory, [$method, $container, []]);
//
//        $this->assertNotEmpty($dependencies);
//    }

    public function testResolveArgumentsDefaultValue(): void
    {
        $method = $this->createMock(\ReflectionFunctionAbstract::class);
        $parameter = $this->createMock(\ReflectionParameter::class);
        $container = $this->createMock(ContainerInterface::class);

        $paramName = 'paramName';

        $method->expects($this->once())->method('getParameters')
            ->willReturn([0 => $parameter]);

        $parameter->expects($this->once())
            ->method('getName')->willReturn($paramName);

        $container->expects($this->once())->method('has')->willReturn(false);

        $parameter->expects($this->once())->method('isDefaultValueAvailable')->willReturn(true);
        $parameter->expects($this->once())->method('getDefaultValue')->willReturnSelf();

        $dependencies = $this->resolveArguments
            ->invokeArgs($this->classFactory, [$method, $container, []]);

        $this->assertNotEmpty($dependencies);
    }

    public function testResolveArgumentsInvalidDependency(): void
    {
        $method = $this->createMock(\ReflectionFunctionAbstract::class);
        $parameter = $this->createMock(\ReflectionParameter::class);
        $container = $this->createMock(ContainerInterface::class);

        $paramName = 'paramName';

        $method->expects($this->once())->method('getParameters')
            ->willReturn([0 => $parameter]);

        $parameter->expects($this->exactly(2))
            ->method('getName')->willReturn($paramName);

        $container->expects($this->once())->method('has')->willReturn(false);

        $parameter->expects($this->once())
            ->method('isDefaultValueAvailable')->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to resolve dependency: '. $paramName);

        $this->resolveArguments->invokeArgs($this->classFactory, [$method, $container, []]);
    }

    public function test()
    {
        $parameterName = 'paramName';
        $parameter = $this->createMock(\ReflectionParameter::class);
        $type = $this->createMock(\ReflectionNamedType::class);

        $parameter->expects($this->exactly(2))->method('getType')->willReturn($type);

        $type->expects($this->once())->method('getName')->willReturn($parameterName);

        $name = $this->getParameterClassName
            ->invokeArgs($this->classFactory, [$parameter]);

        $this->assertEquals($parameterName, $name);
    }
}
