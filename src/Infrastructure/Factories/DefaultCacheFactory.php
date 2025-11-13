<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Factories;

use CubaDevOps\Flexi\Infrastructure\Classes\Configuration;
use CubaDevOps\Flexi\Infrastructure\Classes\InMemoryCache;
use Flexi\Contracts\Interfaces\CacheInterface;

/**
 * Default cache factory implementation with module-aware cache creation
 */
class DefaultCacheFactory implements CacheFactoryInterface
{
    private ModuleDetectorInterface $moduleDetector;
    private Configuration $configuration;

    public function __construct(ModuleDetectorInterface $moduleDetector, Configuration $configuration)
    {
        $this->moduleDetector = $moduleDetector;
        $this->configuration = $configuration;
    }

    public function createCache(): CacheInterface
    {
        if (!$this->moduleDetector->isModuleInstalled('cache')) {
            return new InMemoryCache();
        }

        try {
            $cacheFactoryClass = 'CubaDevOps\\Flexi\\Modules\\Cache\\Infrastructure\\Factories\\CacheFactory';
            /** @var \CubaDevOps\Flexi\Modules\Cache\Infrastructure\Factories\CacheFactory $cacheFactory */
            $cacheFactory = new $cacheFactoryClass($this->configuration);
            return $cacheFactory->getInstance();
        } catch (\Throwable $e) {
            // If Cache module fails to load, fall back to InMemoryCache
            return new InMemoryCache();
        }

    }
}