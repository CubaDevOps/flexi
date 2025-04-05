<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Utils;

use CubaDevOps\Flexi\Domain\Interfaces\CacheInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;

class ClassFactory
{
    private const ERROR_NOT_INSTANTIABLE = 'Class is not instantiable: %s';
    private const ERROR_UNRESOLVED_DEPENDENCY = 'Unable to resolve dependency: %s';
    private const ERROR_PARAMETER_NO_TYPE = 'Parameter %s has no type';
    private const ERROR_INVALID_DEFINITION = 'Invalid service definition';

    public function __construct()
    {
    }

    /**
     * Builds an instance of the given class name.
     *
     * @param ContainerInterface $container
     * @param string $className
     * @return object
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function build(ContainerInterface $container, string $className): object
    {
        $reflectionClass = new \ReflectionClass($className);

        if (!$reflectionClass->isInstantiable()) {
            throw new \RuntimeException(sprintf(self::ERROR_NOT_INSTANTIABLE, $className));
        }

        $constructor = $reflectionClass->getConstructor();

        if (null === $constructor || empty($constructor->getParameters())) {
            return new $className;
        }

        $dependencies = $this->resolveConstructorDependencies($constructor->getParameters(), $container);

        return $reflectionClass->newInstanceArgs($dependencies);
    }

    /**
     * Builds an instance from a service definition.
     *
     * @param ContainerInterface $container
     * @param array $serviceDefinition
     * @return object
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
     * @param ContainerInterface $container
     * @return array
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
     *
     * @param ContainerInterface $container
     * @param string $typeName
     * @param string $parameterName
     * @return mixed
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
     *
     * @param ContainerInterface $container
     * @param array $factoryDefinition
     * @return mixed
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
     *
     * @param ContainerInterface $container
     * @param array $classDefinition
     * @return object
     */
    private function buildFromClass(ContainerInterface $container, array $classDefinition): object
    {
        $class = $classDefinition['name'];
        $arguments = $this->resolveArguments($classDefinition['arguments'] ?? [], $container);

        return new $class(...$arguments);
    }

    /**
     * Resolves arguments for a service definition.
     *
     * @param array $args
     * @param ContainerInterface $container
     * @return array
     */
    private function resolveArguments(array $args, ContainerInterface $container): array
    {
        return array_map(function ($arg) use ($container) {
            if (is_string($arg)) {
                if ($this->isServiceArg($arg)) {
                    return $container->get(ltrim($arg, '@'));
                }

                if ($this->isEnvArg($arg)) {
                    return getenv(substr($arg, 4));
                }
            }

            return $arg;
        }, $args);
    }

    /**
     * Checks if the argument is an environment variable.
     *
     * @param string $id
     * @return bool
     */
    private function isEnvArg(string $id): bool
    {
        return str_starts_with($id, 'ENV.') || str_starts_with($id, 'env.');
    }

    /**
     * Checks if the argument is a service reference.
     *
     * @param string $id
     * @return bool
     */
    private function isServiceArg(string $id): bool
    {
        return str_starts_with($id, '@');
    }
}
