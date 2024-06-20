<?php

namespace CubaDevOps\Flexi\Test\Domain\Utils;

use CubaDevOps\Flexi\Domain\Utils\ClassFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use ReflectionType;

class ClassFactoryTest extends TestCase
{
    private ClassFactory $classFactory;

    private ReflectionMethod $reflectionMethod;

    public function setUp(): void
    {
        $this->classFactory = new ClassFactory();
        $class = new \ReflectionClass(ClassFactory::class);

        $this->reflectionMethod = $class->getMethod('resolveArguments');
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

        $dependencies = $this->reflectionMethod
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

        $dependencies = $this->reflectionMethod
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

        $this->reflectionMethod->invokeArgs($this->classFactory, [$method, $container, []]);
    }
}
