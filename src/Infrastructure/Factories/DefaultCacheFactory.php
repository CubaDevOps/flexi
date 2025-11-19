<?php

declare(strict_types=1);

namespace Flexi\Infrastructure\Factories;

use Flexi\Infrastructure\Classes\Configuration;
use Flexi\Infrastructure\Classes\InMemoryCache;
use Flexi\Domain\Interfaces\ModuleDetectorInterface;
use Flexi\Contracts\Interfaces\CacheInterface;
use Psr\Container\ContainerInterface;

/**
 * Default cache factory implementation with module-aware cache creation
 */
class DefaultCacheFactory implements CacheFactoryInterface
{
    private ModuleDetectorInterface $moduleDetector;
    private ContainerInterface $container;

    public function __construct(ModuleDetectorInterface $moduleDetector, ContainerInterface $container)
    {
        $this->moduleDetector = $moduleDetector;
        $this->container = $container;
    }

    public function createCache(): CacheInterface
    {
        if (!$this->moduleDetector->isModuleInstalled('cache')) {
            return new InMemoryCache();
        }

        try {
            return $this->container->get(CacheInterface::class);
        } catch (\Throwable $e) {
            // If Cache module fails to load, fall back to InMemoryCache
            return new InMemoryCache();
        }

    }
}