<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\DependencyInjection;

use CubaDevOps\Flexi\Contracts\Classes\Traits\FileHandlerTrait;
use CubaDevOps\Flexi\Contracts\Classes\Traits\GlobFileReader;
use CubaDevOps\Flexi\Contracts\Classes\Traits\JsonFileReader;
use CubaDevOps\Flexi\Contracts\Interfaces\CacheInterface;

class ServicesDefinitionParser
{
    use FileHandlerTrait;
    use JsonFileReader;
    use GlobFileReader;

    private const SERVICE_DEFINITION_FILES_KEY = 'service_definition_files';
    private const SERVICES_CACHE_KEY_PREFIX = 'services_file.';
    private const ERROR_SERVICE_ALREADY_DEFINED = 'Service %s is already defined in %s';
    private const ERROR_FILE_NOT_FOUND = 'Service file not found: %s';
    private const COMPOSER_JSON_PATH = './composer.json';

    private CacheInterface $cache;
    private array $serviceDefinitions = [];
    private array $filesProcessed = [];
    private ?array $installedModules = null;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
        $this->filesProcessed = $cache->get(self::SERVICE_DEFINITION_FILES_KEY, []);
    }

    /**
     * Parses a service definition file and returns the services.
     */
    public function parse(string $filename): array
    {
        $filename = $this->normalize($filename);

        $this->ensureFileExists($filename);

        if ($this->isFileProcessed($filename)) {
            return $this->getCachedServices($filename);
        }

        $definitions = $this->readJsonFile($filename);
        $services = $this->processDefinitions($definitions, $filename);

        $this->markFileAsProcessed($filename, $services);

        return $services;
    }

    /**
     * Ensures the file exists, otherwise throws an exception.
     *
     * @throws \RuntimeException
     */
    private function ensureFileExists(string $filename): void
    {
        if (!$this->fileExists($filename)) {
            throw new \RuntimeException(sprintf(self::ERROR_FILE_NOT_FOUND, $filename));
        }
    }

    /**
     * Checks if a file has already been processed.
     */
    private function isFileProcessed(string $filename): bool
    {
        return isset($this->filesProcessed[$filename]);
    }

    /**
     * Retrieves cached services for a processed file.
     */
    private function getCachedServices(string $filename): array
    {
        $fileCacheKey = $this->getFileCacheKey($filename);

        return $this->cache->get($fileCacheKey, []);
    }

    /**
     * Processes the service definitions from the file.
     */
    private function processDefinitions(array $definitions, string $filename): array
    {
        $services = [];

        foreach ($definitions['services'] as $service) {
            if (isset($service['alias'])) {
                $services[$service['name']] = $service['alias'];
            } elseif (isset($service['factory']) || isset($service['class'])) {
                $services[$service['name']] = $service;
            } elseif (isset($service['glob'])) {
                $this->processGlobServices($service['glob'], $services, $filename);
            }
        }

        return $services;
    }

    /**
     * Processes services defined by a glob pattern.
     * Only processes files from installed modules.
     */
    private function processGlobServices(string $glob, array &$services, string $filename): void
    {
        $files = $this->readGlob($glob);

        // Filter files to only include installed modules
        $files = $this->filterInstalledModuleFiles($files);

        foreach ($files as $file) {
            $fileServices = $this->parse($file);

            foreach ($fileServices as $name => $fileService) {
                if (isset($services[$name])) {
                    throw new \RuntimeException(sprintf(self::ERROR_SERVICE_ALREADY_DEFINED, $name, $filename));
                }
                $services[$name] = $fileService;
            }
        }
    }

    /**
     * Marks a file as processed and caches its services.
     */
    private function markFileAsProcessed(string $filename, array $services): void
    {
        $fileCacheKey = $this->getFileCacheKey($filename);

        // Temporarily mark the file as processed to avoid circular references
        $this->filesProcessed[$filename] = true;

        // Store the processed services in cache
        $this->cache->set($fileCacheKey, $services);

        // Update the cache for processed files
        $cachedFilesProcessed = $this->cache->get(self::SERVICE_DEFINITION_FILES_KEY, []);
        $cachedFilesProcessed[$filename] = true;
        $this->cache->set(self::SERVICE_DEFINITION_FILES_KEY, $cachedFilesProcessed);
    }

    /**
     * Generates a cache key for a given file.
     */
    private function getFileCacheKey(string $filename): string
    {
        return self::SERVICES_CACHE_KEY_PREFIX.md5($filename);
    }

    /**
     * Filters files to only include those from installed modules.
     *
     * @param array $files List of file paths
     * @return array Filtered list of files from installed modules only
     */
    private function filterInstalledModuleFiles(array $files): array
    {
        $installedModules = $this->getInstalledModules();

        return array_filter($files, function ($file) use ($installedModules) {
            // If the file is not in a module directory, include it
            if (!$this->isModuleFile($file)) {
                return true;
            }

            // Extract module name from file path
            $moduleName = $this->extractModuleName($file);

            // Only include if module is installed
            return $moduleName && isset($installedModules[$moduleName]);
        });
    }

    /**
     * Checks if a file path is from a module directory.
     *
     * @param string $file File path
     * @return bool True if file is in modules directory
     */
    private function isModuleFile(string $file): bool
    {
        return (bool) preg_match('#/modules/([^/]+)/#', $file);
    }

    /**
     * Extracts the module name from a file path.
     *
     * @param string $file File path
     * @return string|null Module name or null if not a module file
     */
    private function extractModuleName(string $file): ?string
    {
        if (preg_match('#/modules/([^/]+)/#', $file, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Gets the list of installed modules from composer.json.
     *
     * @return array Associative array of installed module packages
     * @throws \JsonException
     */
    private function getInstalledModules(): array
    {
        if ($this->installedModules !== null) {
            return $this->installedModules;
        }

        if (!file_exists(self::COMPOSER_JSON_PATH)) {
            $this->installedModules = [];
            return $this->installedModules;
        }

        $composerData = json_decode(
            file_get_contents(self::COMPOSER_JSON_PATH),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->installedModules = [];

        if (isset($composerData['require'])) {
            foreach ($composerData['require'] as $package => $version) {
                // Only consider flexi modules
                if (str_starts_with($package, 'cubadevops/flexi-module-')) {
                    // Extract module name from package name
                    // cubadevops/flexi-module-auth -> Auth
                    $moduleName = str_replace('cubadevops/flexi-module-', '', $package);
                    $moduleName = ucfirst($moduleName);
                    $this->installedModules[$moduleName] = $package;
                }
            }
        }

        return $this->installedModules;
    }
}