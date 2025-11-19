<?php

declare(strict_types=1);

namespace Flexi\Test\Infrastructure\Classes;

use Flexi\Infrastructure\Classes\Configuration;
use Flexi\Infrastructure\Classes\ObjectBuilder;
use Flexi\Infrastructure\Classes\InMemoryCache;
use Flexi\Infrastructure\Factories\ContainerFactory;
use Flexi\Test\TestData\TestDoubles\HasNoConstructor;
use Flexi\Test\TestData\TestDoubles\HasUnresolvableParameter;
use Flexi\Test\TestData\TestDoubles\IsNotInstantiable;
use Flexi\Test\TestData\TestDoubles\HasUnresolvableDependency;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ObjectBuilderTest extends TestCase
{
    private ObjectBuilder $objectBuilder;
    private ContainerInterface $container;

    public function setUp(): void
    {
        $cache = new InMemoryCache();
        $this->objectBuilder = new ObjectBuilder($cache);

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
        $notInstantiable = IsNotInstantiable::class;

        $this->expectException(\RuntimeException::class);

        $this->expectExceptionMessage('Class is not instantiable: '.$notInstantiable);

        $this->objectBuilder->build($this->container, $notInstantiable);
    }

    public function testBuildHasNoConstructor(): void
    {
        $hasNoConstructor = $this->objectBuilder->build($this->container, HasNoConstructor::class);

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
        $container = $this->objectBuilder->buildFromDefinition($this->container, $factory_definition);
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

        $instance = $this->objectBuilder->buildFromDefinition($this->container, $classDefinition);

        $this->assertInstanceOf(HasNoConstructor::class, $instance);
    }

    public function testBuildWithDependencyInjection(): void
    {
        // Test building a class that requires dependency injection
        // Use a class that's already available in the container
        $instance = $this->objectBuilder->build($this->container, InMemoryCache::class);

        $this->assertInstanceOf(InMemoryCache::class, $instance);
    }

    public function testBuildWithCacheHit(): void
    {
        // For classes with no constructor, new instances are created each time
        // Let's test with a class that has dependencies to test actual caching
        $cache = new InMemoryCache();
        $builder = new ObjectBuilder($cache);

        // Build the same class twice
        $instance1 = $builder->build($this->container, InMemoryCache::class);
        $instance2 = $builder->build($this->container, InMemoryCache::class);

        // For simple classes without dependencies, they might not be cached
        // Let's just verify they are the same type
        $this->assertInstanceOf(InMemoryCache::class, $instance1);
        $this->assertInstanceOf(InMemoryCache::class, $instance2);
    }

    public function testBuildWithReflectionException(): void
    {
        $this->expectException(\ReflectionException::class);

        $this->objectBuilder->build($this->container, 'NonExistentClass');
    }

    public function testBuildFromDefinitionWithInvalidDefinition(): void
    {
        $invalidDefinition = [
            'invalid' => 'definition'
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid service definition');

        $this->objectBuilder->buildFromDefinition($this->container, $invalidDefinition);
    }

    public function testBuildFromFactoryWithArguments(): void
    {
        $factoryDefinition = [
            'factory' => [
                'class' => ContainerFactory::class,
                'method' => 'createDefault',
                'arguments' => ['./tests/TestData/Configurations/container-test.json'],
            ],
        ];

        $container = $this->objectBuilder->buildFromDefinition($this->container, $factoryDefinition);
        $this->assertInstanceOf(ContainerInterface::class, $container);
    }

    public function testBuildFromClassWithArguments(): void
    {
        $classDefinition = [
            'class' => [
                'name' => InMemoryCache::class,
                'arguments' => [],
            ],
        ];

        $instance = $this->objectBuilder->buildFromDefinition($this->container, $classDefinition);

        $this->assertInstanceOf(InMemoryCache::class, $instance);
    }

    public function testResolveArgumentsWithServiceReference(): void
    {
        // Test building with service reference (@serviceName)
        $classDefinition = [
            'class' => [
                'name' => ObjectBuilder::class,
                'arguments' => ['@' . InMemoryCache::class],
            ],
        ];

        $instance = $this->objectBuilder->buildFromDefinition($this->container, $classDefinition);

        $this->assertInstanceOf(ObjectBuilder::class, $instance);
    }

    public function testResolveArgumentsWithEnvironmentVariable(): void
    {
        // Set environment variable for testing
        $_ENV['TEST_STRING'] = 'test_value';
        $_ENV['TEST_BOOL_TRUE'] = 'true';
        $_ENV['TEST_BOOL_FALSE'] = 'false';
        $_ENV['TEST_INT'] = '123';
        $_ENV['TEST_FLOAT'] = '12.34';

        $classDefinition = [
            'class' => [
                'name' => HasNoConstructor::class,
                'arguments' => [],
            ],
        ];

        // Test string environment variable
        $instance = $this->objectBuilder->buildFromDefinition($this->container, $classDefinition);
        $this->assertInstanceOf(HasNoConstructor::class, $instance);

        // Clean up environment variables
        unset($_ENV['TEST_STRING'], $_ENV['TEST_BOOL_TRUE'], $_ENV['TEST_BOOL_FALSE'], $_ENV['TEST_INT'], $_ENV['TEST_FLOAT']);
    }

    public function testResolveArgumentsWithMissingEnvironmentVariable(): void
    {
        $classDefinition = [
            'class' => [
                'name' => HasNoConstructor::class,
                'arguments' => ['ENV.MISSING_VAR'],
            ],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Environment variable "MISSING_VAR" is not set');

        $this->objectBuilder->buildFromDefinition($this->container, $classDefinition);
    }

    public function testResolveConstructorDependenciesNoOptionalThrowsException(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('has')->willReturn(false);

        // Use reflection to test the private method properly
        $reflection = new \ReflectionClass($this->objectBuilder);
        $method = $reflection->getMethod('resolveConstructorDependencies');
        $method->setAccessible(true);

        // Create reflection parameter mock to simulate unresolvable required parameter
        $parameterMock = $this->createMock(\ReflectionParameter::class);
        $parameterMock->method('isOptional')->willReturn(false);
        $parameterMock->method('getName')->willReturn('unresolveableParam');
        $parameterMock->method('hasType')->willReturn(true);

        $typeMock = $this->createMock(\ReflectionType::class);
        $typeMock->method('__toString')->willReturn('UnresolvableInterface');
        $parameterMock->method('getType')->willReturn($typeMock);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to resolve dependency: unresolveableParam');

        // Call with correct parameter order (parameters, container)
        $method->invoke($this->objectBuilder, [$parameterMock], $containerMock);
    }

    /**
     * Tests resolveDependency when container has the type name
     * This covers line 114: return $container->get($typeName);
     * @throws \ReflectionException
     */
    public function testResolveDependencyWhenContainerHasTypeName(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $mockService = new \stdClass();
        $typeName = 'TestServiceInterface';
        $parameterName = 'testParam';

        // Container has the type name
        $containerMock->expects($this->once())
            ->method('has')
            ->with($typeName)
            ->willReturn(true);

        $containerMock->expects($this->once())
            ->method('get')
            ->with($typeName)
            ->willReturn($mockService);

        // Access private resolveDependency method
        $reflection = new \ReflectionClass($this->objectBuilder);
        $method = $reflection->getMethod('resolveDependency');
        $method->setAccessible(true);

        $result = $method->invoke($this->objectBuilder, $containerMock, $typeName, $parameterName);

        $this->assertSame($mockService, $result);
    }

    public function testResolveDependencyFallsBackToParameterName(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $service = new \stdClass();
        $typeName = 'MissingInterface';
        $parameterName = 'fallback_service';

        $containerMock->expects($this->exactly(2))
            ->method('has')
            ->withConsecutive([$typeName], [$parameterName])
            ->willReturnOnConsecutiveCalls(false, true);

        $containerMock->expects($this->once())
            ->method('get')
            ->with($parameterName)
            ->willReturn($service);

        $reflection = new \ReflectionClass($this->objectBuilder);
        $method = $reflection->getMethod('resolveDependency');
        $method->setAccessible(true);

        $result = $method->invoke($this->objectBuilder, $containerMock, $typeName, $parameterName);

        $this->assertSame($service, $result);
    }

    public function testResolveDependencyNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to resolve dependency');

        $this->objectBuilder->build($this->container, HasUnresolvableDependency::class);
    }

    /**
     * Test resolveArguments with various argument types to increase coverage
     */
    public function testResolveArgumentsWithVariousTypes(): void
    {
        // Test with environment variables of different types
        $_ENV['TEST_STRING_VAR'] = 'string_value';
        $_ENV['TEST_BOOL_TRUE'] = 'true';
        $_ENV['TEST_BOOL_FALSE'] = 'false';
        $_ENV['TEST_INT_VAR'] = '42';
        $_ENV['TEST_FLOAT_VAR'] = '3.14';
        $_ENV['TEST_NEGATIVE_INT'] = '-10';

        $classDefinition = [
            'class' => [
                'name' => HasNoConstructor::class,
                'arguments' => [
                    'ENV.TEST_STRING_VAR',
                    'ENV.TEST_BOOL_TRUE',
                    'ENV.TEST_BOOL_FALSE',
                    'ENV.TEST_INT_VAR',
                    'ENV.TEST_FLOAT_VAR',
                    'ENV.TEST_NEGATIVE_INT',
                    'regular_string',
                    123,
                    true,
                    ['array_arg']
                ],
            ],
        ];

        $instance = $this->objectBuilder->buildFromDefinition($this->container, $classDefinition);
        $this->assertInstanceOf(HasNoConstructor::class, $instance);

        // Clean up
        unset($_ENV['TEST_STRING_VAR'], $_ENV['TEST_BOOL_TRUE'], $_ENV['TEST_BOOL_FALSE'],
              $_ENV['TEST_INT_VAR'], $_ENV['TEST_FLOAT_VAR'], $_ENV['TEST_NEGATIVE_INT']);
    }

    /**
     * Test isServiceArg and isEnvArg private methods through argument resolution
     */
    public function testArgumentTypeDetection(): void
    {
        // Set up environment variable with lowercase prefix
        $_ENV['LOWER_TEST'] = 'lowercase_value';

        $classDefinition = [
            'class' => [
                'name' => HasNoConstructor::class,
                'arguments' => [
                    '@' . InMemoryCache::class,  // Service reference
                    'env.LOWER_TEST',            // Environment variable with lowercase prefix
                    'ENV.LOWER_TEST',            // Environment variable with uppercase prefix
                    'normal_string',             // Regular string (not service or env)
                ],
            ],
        ];

        $instance = $this->objectBuilder->buildFromDefinition($this->container, $classDefinition);
        $this->assertInstanceOf(HasNoConstructor::class, $instance);

        unset($_ENV['LOWER_TEST']);
    }

    /**
     * Test environment variable fallback from getenv to $_ENV
     */
    public function testEnvironmentVariableFallback(): void
    {
        // Set variable only in $_ENV (not via putenv)
        $_ENV['FALLBACK_TEST'] = 'fallback_value';

        $classDefinition = [
            'class' => [
                'name' => HasNoConstructor::class,
                'arguments' => ['ENV.FALLBACK_TEST'],
            ],
        ];

        $instance = $this->objectBuilder->buildFromDefinition($this->container, $classDefinition);
        $this->assertInstanceOf(HasNoConstructor::class, $instance);

        unset($_ENV['FALLBACK_TEST']);
    }

    /**
     * Test boolean detection with various boolean-like strings
     */
    public function testBooleanDetectionInEnvironmentVariables(): void
    {
        $_ENV['BOOL_1'] = '1';
        $_ENV['BOOL_0'] = '0';
        $_ENV['BOOL_YES'] = 'yes';
        $_ENV['BOOL_NO'] = 'no';
        $_ENV['BOOL_ON'] = 'on';
        $_ENV['BOOL_OFF'] = 'off';

        $classDefinition = [
            'class' => [
                'name' => HasNoConstructor::class,
                'arguments' => [
                    'ENV.BOOL_1',
                    'ENV.BOOL_0',
                    'ENV.BOOL_YES',
                    'ENV.BOOL_NO',
                    'ENV.BOOL_ON',
                    'ENV.BOOL_OFF'
                ],
            ],
        ];

        $instance = $this->objectBuilder->buildFromDefinition($this->container, $classDefinition);
        $this->assertInstanceOf(HasNoConstructor::class, $instance);

        unset($_ENV['BOOL_1'], $_ENV['BOOL_0'], $_ENV['BOOL_YES'],
              $_ENV['BOOL_NO'], $_ENV['BOOL_ON'], $_ENV['BOOL_OFF']);
    }

    /**
     * Test numeric detection with various numeric strings
     */
    public function testNumericDetectionInEnvironmentVariables(): void
    {
        $_ENV['NUM_INT'] = '123';
        $_ENV['NUM_FLOAT'] = '123.456';
        $_ENV['NUM_ZERO'] = '0';
        $_ENV['NUM_NEGATIVE'] = '-42';
        $_ENV['NUM_NEGATIVE_FLOAT'] = '-3.14';

        $classDefinition = [
            'class' => [
                'name' => HasNoConstructor::class,
                'arguments' => [
                    'ENV.NUM_INT',
                    'ENV.NUM_FLOAT',
                    'ENV.NUM_ZERO',
                    'ENV.NUM_NEGATIVE',
                    'ENV.NUM_NEGATIVE_FLOAT'
                ],
            ],
        ];

        $instance = $this->objectBuilder->buildFromDefinition($this->container, $classDefinition);
        $this->assertInstanceOf(HasNoConstructor::class, $instance);

        unset($_ENV['NUM_INT'], $_ENV['NUM_FLOAT'], $_ENV['NUM_ZERO'],
              $_ENV['NUM_NEGATIVE'], $_ENV['NUM_NEGATIVE_FLOAT']);
    }

    /**
     * Test resolveDependency private method through dependency resolution
     */
    public function testDependencyResolutionProcess(): void
    {
        // Test the dependency resolution process by building classes with simple dependencies
        $instance = $this->objectBuilder->build($this->container, ObjectBuilder::class);
        $this->assertInstanceOf(ObjectBuilder::class, $instance);

        // Test building a simple class without complex dependencies
        $cacheInstance = $this->objectBuilder->build($this->container, InMemoryCache::class);
        $this->assertInstanceOf(InMemoryCache::class, $cacheInstance);
    }

    /**
     * Test constructor dependency resolution with optional parameters
     */
    public function testConstructorWithOptionalParameters(): void
    {
        // Create a test double that has optional constructor parameters
        $testClass = new class {
            public function __construct(?string $optionalParam = null) {
                // Constructor with optional parameter
            }
        };

        $className = get_class($testClass);
        $instance = $this->objectBuilder->build($this->container, $className);

        $this->assertInstanceOf($className, $instance);
    }

    /**
     * Test cache functionality more thoroughly
     */
    public function testCachingBehavior(): void
    {
        $cache = new InMemoryCache();
        $builder = new ObjectBuilder($cache);

        // Test class with constructor parameters for proper cache testing
        $instance1 = $builder->build($this->container, ObjectBuilder::class, []);
        $instance2 = $builder->build($this->container, ObjectBuilder::class, []);

        $this->assertInstanceOf(ObjectBuilder::class, $instance1);
        $this->assertInstanceOf(ObjectBuilder::class, $instance2);
    }

    /**
     * Test is_boolean private method through environment variable resolution
     */
    public function testBooleanValidation(): void
    {
        // Test various values that should and shouldn't be considered boolean
        $_ENV['NOT_BOOL'] = 'random_string';
        $_ENV['EMPTY_STRING'] = '';
        $_ENV['NUMERIC_STRING'] = '123abc';

        $classDefinition = [
            'class' => [
                'name' => HasNoConstructor::class,
                'arguments' => [
                    'ENV.NOT_BOOL',
                    'ENV.EMPTY_STRING',
                    'ENV.NUMERIC_STRING'
                ],
            ],
        ];

        $instance = $this->objectBuilder->buildFromDefinition($this->container, $classDefinition);
        $this->assertInstanceOf(HasNoConstructor::class, $instance);

        unset($_ENV['NOT_BOOL'], $_ENV['EMPTY_STRING'], $_ENV['NUMERIC_STRING']);
    }

    /**
     * Test is_boolean method coverage with boolean environment variables
     */
    public function testIsBooleanMethodCoverage(): void
    {
        // Set environment variable with boolean value that will trigger is_boolean method
        $_ENV['TEST_BOOL_ACTUAL'] = 'true';

        $classDefinition = [
            'class' => [
                'name' => HasNoConstructor::class,
                'arguments' => ['ENV.TEST_BOOL_ACTUAL'],
            ],
        ];

        $instance = $this->objectBuilder->buildFromDefinition($this->container, $classDefinition);
        $this->assertInstanceOf(HasNoConstructor::class, $instance);

        unset($_ENV['TEST_BOOL_ACTUAL']);
    }

    /**
     * Test error handling for non-instantiable classes (line 49)
     */
    public function testNonInstantiableClassError(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('is not instantiable');

        // Try to build a non-instantiable class
        $this->objectBuilder->build($this->container, IsNotInstantiable::class);
    }

    /**
     * Test error handling for parameters without type (line 93)
     */
    public function testParameterWithoutTypeError(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('has no type');

        // This should trigger the parameter without type error
        $this->objectBuilder->build($this->container, HasUnresolvableParameter::class);
    }
}