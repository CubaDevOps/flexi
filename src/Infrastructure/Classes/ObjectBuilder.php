<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Classes;

use CubaDevOps\Flexi\Contracts\Interfaces\CacheInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\ObjectBuilderInterface;
use CubaDevOps\Flexi\Infrastructure\Utils\CacheKeyGeneratorTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class ObjectBuilder implements ObjectBuilderInterface
{
    use CacheKeyGeneratorTrait;

    private const ERROR_NOT_INSTANTIABLE = 'Class is not instantiable: %s';
    private const ERROR_UNRESOLVED_DEPENDENCY = 'Unable to resolve dependency: %s';
    private const ERROR_PARAMETER_NO_TYPE = 'Parameter %s has no type';
    private const ERROR_INVALID_DEFINITION = 'Invalid service definition';

    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Builds an instance of the given class name.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws \InvalidArgumentException
     */
    public function build(ContainerInterface $container, string $className, array $arguments = []): object
    {
        $key = $this->getCacheKey($className, '__construct', $arguments);
        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }

        $reflectionClass = new \ReflectionClass($className);

        if (!$reflectionClass->isInstantiable()) {
            throw new \RuntimeException(sprintf(self::ERROR_NOT_INSTANTIABLE, $className));
        }

        $constructor = $reflectionClass->getConstructor();

        if (null === $constructor || empty($constructor->getParameters())) {
            return new $className();
        }

        $dependencies = $this->resolveConstructorDependencies($constructor->getParameters(), $container);

        $instance = $reflectionClass->newInstanceArgs($dependencies);
        $this->cache->set($key, $instance);

        return $instance;
    }

    /**
     * Builds an instance from a service definition.
     */
    public function buildFromDefinition(ContainerInterface $container, array $serviceDefinition): object
    {
        if (isset($serviceDefinition['factory'])) {
            return $this->buildFromFactory($container, $serviceDefinition['factory']);
        }

        if (isset($serviceDefinition['class'])) {
            return $this->buildFromClass($container, $serviceDefinition['class']);
        }

        throw new \RuntimeException(self::ERROR_INVALID_DEFINITION);
    }

    /**
     * Resolves constructor dependencies.
     *
     * @param \ReflectionParameter[] $parameters
     */
    private function resolveConstructorDependencies(array $parameters, ContainerInterface $container): array
    {
        return array_map(function (\ReflectionParameter $parameter) use ($container) {
            $name = $parameter->getName();
            $type = $parameter->getType();

            if (!$type) {
                throw new \Exception(sprintf(self::ERROR_PARAMETER_NO_TYPE, $name));
            }

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                return $this->resolveDependency($container, $type->getName(), $name);
            }

            if ($parameter->isOptional()) {
                return $parameter->getDefaultValue();
            }

            throw new \RuntimeException(sprintf(self::ERROR_UNRESOLVED_DEPENDENCY, $name));
        }, $parameters);
    }

    /**
     * Resolves a single dependency from the container.
     */
    private function resolveDependency(ContainerInterface $container, string $typeName, string $parameterName)
    {
        if ($container->has($typeName)) {
            return $container->get($typeName);
        }

        if ($container->has($parameterName)) {
            return $container->get($parameterName);
        }

        throw new \RuntimeException(sprintf(self::ERROR_UNRESOLVED_DEPENDENCY, $parameterName));
    }

    /**
     * Builds an instance using a factory definition.
     */
    private function buildFromFactory(ContainerInterface $container, array $factoryDefinition)
    {
        $class = $factoryDefinition['class'];
        $method = $factoryDefinition['method'];
        $arguments = $this->resolveArguments($factoryDefinition['arguments'] ?? [], $container);

        return $class::$method(...$arguments);
    }

    /**
     * Builds an instance using a class definition.
     */
    private function buildFromClass(ContainerInterface $container, array $classDefinition): object
    {
        $class = $classDefinition['name'];
        $arguments = $this->resolveArguments($classDefinition['arguments'] ?? [], $container);

        return new $class(...$arguments);
    }

    /**
     * Resolves arguments for a service definition.
     */
    private function resolveArguments(array $args, ContainerInterface $container): array
    {
        return array_map(function ($arg) use ($container) {
            if (is_string($arg)) {
                if ($this->isServiceArg($arg)) {
                    return $container->get(ltrim($arg, '@'));
                }

                if ($this->isEnvArg($arg)) {
                    $key = substr($arg, 4);
                    $value = getenv($key);

                    // Fallback to $_ENV if getenv returns false
                    if (false === $value && isset($_ENV[$key])) {
                        $value = $_ENV[$key];
                    }

                    // If still no value, throw exception for required env vars
                    if (false === $value || null === $value) {
                        throw new \RuntimeException(sprintf('Environment variable "%s" is not set', $key));
                    }

                    if ($this->is_boolean($value)) {
                        return boolval($value);
                    } elseif (is_numeric($value)) {
                        // Check if value is an integer (including negative numbers)
                        if (false !== filter_var($value, FILTER_VALIDATE_INT)) {
                            return (int) $value;
                        }

                        return (float) $value;
                    }

                    return $value;
                }
            }

            return $arg;
        }, $args);
    }

    /**
     * Checks if the argument is an environment variable.
     */
    private function isEnvArg(string $id): bool
    {
        return str_starts_with($id, 'ENV.') || str_starts_with($id, 'env.');
    }

    /**
     * Checks if the argument is a service reference.
     */
    private function isServiceArg(string $id): bool
    {
        return str_starts_with($id, '@');
    }

    private function is_boolean($variable): bool
    {
        return null !== filter_var($variable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
