<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Interfaces;

use CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo;

/**
 * Interface for managing module discovery cache.
 *
 * This interface defines methods for caching module discovery results
 * to improve performance by avoiding filesystem scans on every request.
 */
interface ModuleCacheManagerInterface
{
    /**
     * Get cached modules information.
     *
     * @return ModuleInfo[] Array of cached module information
     */
    public function getCachedModules(): array;

    /**
     * Cache modules information.
     *
     * @param ModuleInfo[] $modules Array of modules to cache
     * @return bool True if cache was successfully written
     */
    public function cacheModules(array $modules): bool;

    /**
     * Check if cache is valid based on composer.lock modification time.
     *
     * @return bool True if cache is valid and up-to-date
     */
    public function isCacheValid(): bool;

    /**
     * Invalidate and clear the module cache.
     *
     * @return bool True if cache was successfully cleared
     */
    public function invalidateCache(): bool;

    /**
     * Get the path to the cache file.
     *
     * @return string Cache file path
     */
    public function getCacheFilePath(): string;

    /**
     * Check if cache file exists.
     *
     * @return bool True if cache file exists
     */
    public function cacheExists(): bool;
}
