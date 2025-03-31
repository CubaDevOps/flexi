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
    use CacheKeyGeneratorTrait;
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @return object|mixed
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws InvalidArgumentException
     */
    public function build(
        ContainerInterface $container,
        string $className,
        array $arguments = []
    ) {
        $key = $this->getCacheKey($className, '__construct', $arguments);
        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }

        $reflectionClass = new \ReflectionClass($className);

        if (!$reflectionClass->isInstantiable()) {
            throw new \RuntimeException('Class is not instantiable: '.$className);
        }

        $constructor = $reflectionClass->getConstructor();

        if (null === $constructor) {
            return $reflectionClass->newInstanceWithoutConstructor();
        }

        $args = $this->resolveArguments(
            $constructor,
            $container,
            $arguments
        );

        $instance = $reflectionClass->newInstanceArgs($args);
        $this->cache->set($key, $instance);

        return $instance;
    }

    /**
     * @return (array|false|mixed|string)[]
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     * @psalm-return list{0?: array|false|mixed|string,...}
     */
    private function resolveArguments(
        \ReflectionFunctionAbstract $method,
        ContainerInterface $container,
        array $arguments
    ): array {
        $dependencies = [];
        foreach ($method->getParameters() as $index => $parameter) {
            if (isset($arguments[$index])) {
                $dependencies[] = $this->resolveFromArguments($arguments[$index], $container);
            } elseif ($container->has($parameter->getName())) {
                $dependencies[] = $container->get($parameter->getName());
            } elseif (
                $this->isObject($parameter)
                && $container->has($this->getParameterClassName($parameter))
            ) {
                $dependencies[] = $container->get(
                    $this->getParameterClassName($parameter)
                );
            } elseif ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                throw new \RuntimeException('Unable to resolve dependency: '.$parameter->getName());
            }
        }

        return $dependencies;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function resolveFromArguments($argument, ContainerInterface $container)
    {
        if (is_array($argument)) {
            return $argument;
        }

        $dependency = $argument;
        if ($this->isEnvArg($argument)) {
            $dependency = getenv(
                str_replace(['env.', 'ENV.'], '', $argument)
            );
        } elseif ($this->isServiceArg($argument)) {
            $id = ltrim($argument, '@');
            $dependency = $container->get($id);
        }

        return $dependency;
    }

    private function isEnvArg(string $id): bool
    {
        return 0 === strpos($id, 'ENV.') || 0 === strpos($id, 'env.');
    }

    private function isServiceArg(string $id): bool
    {
        return 0 === strpos($id, '@');
    }

    private function isObject(\ReflectionParameter $parameter): bool
    {
        return $parameter->getType() && (class_exists($parameter->getType()->getName()) || interface_exists($parameter->getType()->getName()));
    }

    private function getParameterClassName(\ReflectionParameter $parameter): string
    {
        return $parameter->getType() ? $parameter->getType()->getName() : '';
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws InvalidArgumentException
     */
    public function buildFromFactory(
        ContainerInterface $container,
        string $class,
        string $method,
        array $params = []
    ) {
        $key = $this->getCacheKey($class, $method, $params);
        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }

        $reflection = new \ReflectionMethod($class, $method);
        if (!$reflection->isStatic()) {
            throw new \RuntimeException("$method need to be declared as static to use as factory method");
        }
        if (count($params) < $reflection->getNumberOfRequiredParameters()) {
            throw new \RuntimeException("$method has {$reflection->getNumberOfRequiredParameters()} required parameters");
        }

        $args = $this->resolveArguments($reflection, $container, $params);
        $instance = $reflection->invokeArgs(null, $args);
        $this->cache->set($key, $instance);

        return $instance;
    }
}
