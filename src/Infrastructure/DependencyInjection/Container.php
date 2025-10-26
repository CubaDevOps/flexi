<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\DependencyInjection;

use CubaDevOps\Flexi\Contracts\CacheContract;
use CubaDevOps\Flexi\Contracts\ObjectBuilderContract;
use CubaDevOps\Flexi\Domain\Exceptions\ServiceNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Container implements ContainerInterface
{
    private const CONTAINER_CACHE_KEY = 'container';
    private const SERVICE_CACHE_KEY_PREFIX = 'service.';
    private const SERVICE_DEFINITIONS_KEY = 'service_definitions';
    private const ERROR_SELF_REFERENCE = 'Cannot register self-referencing service: %s';
    private const ERROR_INVALID_DEFINITION = 'Service definition must be an object, an array, or a string class name.';

    private array $serviceDefinitions;
    private array $selfReference = ['container', ContainerInterface::class];

    private ObjectBuilderContract $factory;
    private CacheContract $cache;

    public function __construct(CacheContract $cache, ObjectBuilderContract $factory)
    {
        $this->cache = $cache;
        $this->factory = $factory;

        // Initialize the container with the default service definitions
        $this->serviceDefinitions = $cache->get(self::SERVICE_DEFINITIONS_KEY, []);

        $this->set('cache', $cache);
        $this->set('factory', $this->factory);
    }

    /**
     * Register a service definition.
     *
     * @param string              $id                The service ID
     * @param string|array|object $serviceDefinition The service definition
     */
    public function set(string $id, $serviceDefinition): void
    {
        if ($this->has($id)) {
            return;
        }

        $this->validateServiceDefinition($id, $serviceDefinition);

        if (is_object($serviceDefinition) && !is_callable($serviceDefinition)) {
            $this->cacheServiceInstance($id, $serviceDefinition);

            return;
        }

        $this->serviceDefinitions[$id] = $serviceDefinition;
        $this->cache->set(self::SERVICE_DEFINITIONS_KEY, $this->serviceDefinitions);
    }

    /**
     * Check if the container can provide a service with the given ID.
     */
    public function has(string $id): bool
    {
        return in_array($id, $this->selfReference, true)
            || isset($this->serviceDefinitions[$id])
            || $this->cache->has($this->generateServiceCacheKey($id));
    }

    /**
     * Get a service instance from the container.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws \InvalidArgumentException
     */
    public function get(string $id): object
    {
        if (in_array($id, $this->selfReference, true)) {
            return $this;
        }
        if ('cache' === $id || $id instanceof CacheContract) {
            return $this->cache;
        }
        if ('factory' === $id || $id instanceof ObjectBuilderContract) {
            return $this->factory;
        }

        $cacheKey = $this->generateServiceCacheKey($id);
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        try {
            $serviceInstance = $this->resolveServiceInstance($id);
        } catch (\Throwable $th) {
            throw new ServiceNotFoundException(sprintf('Class %s does not exist', $id), 0, $th);
        }

        $this->cacheServiceInstance($id, $serviceInstance);

        return $serviceInstance;
    }

    /**
     * Resolves a service instance from its definition or builds it.
     *
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws \InvalidArgumentException
     */
    private function resolveServiceInstance(string $id): object
    {
        if (isset($this->serviceDefinitions[$id])) {
            $definition = $this->serviceDefinitions[$id];

            if (is_array($definition)) {
                return $this->factory->buildFromDefinition($this, $definition);
            }

            if (is_string($definition)) {
                return $this->resolveAlias($definition);
            }
        }

        return $this->factory->build($this, $id);
    }

    /**
     * Resolves an alias to another service.
     */
    private function resolveAlias(string $alias): object
    {
        if ($this->has($alias)) {
            return $this->get($alias);
        }

        throw new \InvalidArgumentException(sprintf('Service alias "%s" cannot be resolved.', $alias));
    }

    /**
     * Validates a service definition.
     */
    private function validateServiceDefinition(string $id, $serviceDefinition): void
    {
        if (in_array($id, $this->selfReference, true)) {
            throw new \InvalidArgumentException(sprintf(self::ERROR_SELF_REFERENCE, $id));
        }

        if (!is_object($serviceDefinition) && !is_array($serviceDefinition) && !is_string($serviceDefinition)) {
            throw new \InvalidArgumentException(self::ERROR_INVALID_DEFINITION);
        }
    }

    /**
     * Caches a service instance.
     */
    private function cacheServiceInstance(string $id, object $serviceInstance): void
    {
        $this->cache->set($this->generateServiceCacheKey($id), $serviceInstance);
    }

    /**
     * Generate a cache key for a service.
     */
    private function generateServiceCacheKey(string $id): string
    {
        return self::SERVICE_CACHE_KEY_PREFIX.md5($id);
    }
}
