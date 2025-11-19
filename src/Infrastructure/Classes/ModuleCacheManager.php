<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Classes;

use CubaDevOps\Flexi\Domain\Interfaces\ModuleCacheManagerInterface;
use CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo;
use CubaDevOps\Flexi\Domain\ValueObjects\ModuleType;

/**
 * Manages module discovery cache based on composer.lock changes.
 *
 * This class provides intelligent caching of module discovery results
 * to avoid expensive filesystem scans on every request. Cache is automatically
 * invalidated when composer.lock is modified.
 */
class ModuleCacheManager implements ModuleCacheManagerInterface
{
    private string $cacheFilePath;
    private string $composerLockPath;

    public function __construct(string $cacheDir = './var', string $projectRoot = './')
    {
        $this->cacheFilePath = rtrim($cacheDir, '/') . '/modules-cache.json';
        $this->composerLockPath = rtrim($projectRoot, '/') . '/composer.lock';
    }

    /**
     * Get cached modules information.
     */
    public function getCachedModules(): array
    {
        if (!$this->cacheExists() || !$this->isCacheValid()) {
            return [];
        }

        try {
            $content = file_get_contents($this->cacheFilePath);
            if ($content === false) {
                return [];
            }

            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($data['modules']) || !is_array($data['modules'])) {
                return [];
            }

            $modules = [];
            foreach ($data['modules'] as $moduleData) {
                $modules[] = $this->deserializeModuleInfo($moduleData);
            }

            return $modules;
        } catch (\JsonException | \Exception $e) {
            // If cache is corrupted, return empty array
            return [];
        }
    }

    /**
     * Cache modules information.
     */
    public function cacheModules(array $modules): bool
    {
        try {
            // Ensure cache directory exists
            $cacheDir = dirname($this->cacheFilePath);
            if (!is_dir($cacheDir) && !mkdir($cacheDir, 0755, true)) {
                return false;
            }

            $cacheData = [
                'timestamp' => time(),
                'composer_lock_mtime' => $this->getComposerLockModificationTime(),
                'modules' => []
            ];

            foreach ($modules as $module) {
                $cacheData['modules'][] = $this->serializeModuleInfo($module);
            }

            $jsonData = json_encode($cacheData, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

            // Atomic write to prevent corruption
            $tempFile = $this->cacheFilePath . '.tmp';
            if (file_put_contents($tempFile, $jsonData, LOCK_EX) === false) {
                return false;
            }

            return rename($tempFile, $this->cacheFilePath);
        } catch (\JsonException | \Exception $e) {
            return false;
        }
    }

    /**
     * Check if cache is valid based on composer.lock modification time.
     */
    public function isCacheValid(): bool
    {
        if (!$this->cacheExists()) {
            return false;
        }

        try {
            $content = file_get_contents($this->cacheFilePath);
            if ($content === false) {
                return false;
            }

            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($data['composer_lock_mtime'])) {
                return false;
            }

            $currentComposerMtime = $this->getComposerLockModificationTime();
            return $data['composer_lock_mtime'] === $currentComposerMtime;
        } catch (\JsonException | \Exception $e) {
            return false;
        }
    }

    /**
     * Invalidate and clear the module cache.
     */
    public function invalidateCache(): bool
    {
        if (!$this->cacheExists()) {
            return true;
        }

        return unlink($this->cacheFilePath);
    }

    /**
     * Get the path to the cache file.
     */
    public function getCacheFilePath(): string
    {
        return $this->cacheFilePath;
    }

    /**
     * Check if cache file exists.
     */
    public function cacheExists(): bool
    {
        return file_exists($this->cacheFilePath);
    }

    /**
     * Get composer.lock modification time.
     */
    private function getComposerLockModificationTime(): ?int
    {
        if (!file_exists($this->composerLockPath)) {
            return null;
        }

        $mtime = filemtime($this->composerLockPath);
        return $mtime !== false ? $mtime : null;
    }

    /**
     * Serialize ModuleInfo for caching.
     */
    private function serializeModuleInfo(ModuleInfo $module): array
    {
        return [
            'name' => $module->getName(),
            'package' => $module->getPackage(),
            'type' => $module->getType()->getValue(),
            'path' => $module->getPath(),
            'version' => $module->getVersion(),
            'metadata' => $module->getMetadata()
        ];
    }

    /**
     * Deserialize cached data to ModuleInfo.
     */
    private function deserializeModuleInfo(array $data): ModuleInfo
    {
        $typeValue = $data['type'] ?? 'local';

        if ($typeValue === 'vendor') {
            $type = ModuleType::vendor();
        } elseif ($typeValue === 'mixed') {
            $type = ModuleType::mixed();
        } else {
            $type = ModuleType::local();
        }

        return new ModuleInfo(
            $data['name'] ?? '',
            $data['package'] ?? '',
            $type,
            $data['path'] ?? '',
            $data['version'] ?? 'unknown',
            false, // isActive will be determined by ModuleStateManager
            $data['metadata'] ?? []
        );
    }
}