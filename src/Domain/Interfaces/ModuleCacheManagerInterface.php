<?php

declare(strict_types=1);

namespace Flexi\Domain\Interfaces;

use Flexi\Domain\ValueObjects\ModuleInfo;

/**
 * Interface for managing module discovery cache.
 *
 * This interface defines methods for caching module discovery results
 * to improve performance by avoiding filesystem scans on every request.
 */
interface ModuleCacheManagerInterface
{
    /**
     * Get cached modules information for a specific type.
     *
     * @param string|null $type Module type ('local', 'vendor', or null for all)
     * @return ModuleInfo[] Array of cached module information
     */
    public function getCachedModules(?string $type = null): array;

    /**
     * Cache modules information for a specific type.
     *
     * @param ModuleInfo[] $modules Array of modules to cache
     * @param string $type Module type ('local' or 'vendor')
     * @return bool True if cache was successfully written
     */
    public function cacheModules(array $modules, string $type): bool;

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
