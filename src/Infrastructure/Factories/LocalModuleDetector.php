<?php

declare(strict_types=1);

namespace Flexi\Infrastructure\Factories;

use Flexi\Domain\ValueObjects\ModuleInfo;
use Flexi\Domain\ValueObjects\ModuleType;
use Flexi\Domain\Interfaces\ModuleDetectorInterface;

/**
 * Module detector for locally installed modules.
 *
 * Detects modules in the modules/ directory, typically used for
 * development or customized modules that are maintained locally.
 */
class LocalModuleDetector implements ModuleDetectorInterface
{
    private string $modulesPath;
    private static ?array $localModules = null;

    public function __construct(string $modulesPath = './modules')
    {
        $this->modulesPath = rtrim($modulesPath, '/');
    }

    /**
     * Check if a module exists in the local modules directory.
     */
    public function isModuleInstalled(string $moduleName): bool
    {
        $localModules = $this->getLocalModules();
        $normalizedName = $this->normalizeModuleName($moduleName);
        return isset($localModules[$normalizedName]);
    }

    /**
     * Get all modules in local modules directory.
     *
     * @return ModuleInfo[]
     */
    public function getAllModules(): array
    {
        $modules = [];
        $localModules = $this->getLocalModules();

        foreach ($localModules as $moduleName => $moduleData) {
            $modules[$moduleName] = new ModuleInfo(
                $moduleData['name'],
                $moduleData['package'],
                ModuleType::local(),
                $moduleData['path'],
                $moduleData['version'],
                false, // Will be determined by ModuleStateManager
                $moduleData['metadata']
            );
        }

        return $modules;
    }

    /**
     * Get information about a specific local module.
     */
    public function getModuleInfo(string $moduleName): ?ModuleInfo
    {
        $localModules = $this->getLocalModules();
        $normalizedName = $this->normalizeModuleName($moduleName);

        if (!isset($localModules[$normalizedName])) {
            return null;
        }

        $moduleData = $localModules[$normalizedName];
        return new ModuleInfo(
            $moduleData['name'],
            $moduleData['package'],
            ModuleType::local(),
            $moduleData['path'],
            $moduleData['version'],
            false, // Will be determined by ModuleStateManager
            $moduleData['metadata']
        );
    }

    /**
     * Get statistics about local modules.
     */
    public function getModuleStatistics(): array
    {
        $localModules = $this->getLocalModules();

        $stats = [
            'total' => count($localModules),
            'local' => count($localModules),
            'vendor' => 0,
            'conflicts' => 0,
        ];

        // Analyze module types from metadata if available
        $typeCounts = [];
        foreach ($localModules as $moduleData) {
            $type = $moduleData['metadata']['type'] ?? 'unknown';
            $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
        }

        $stats['by_type'] = $typeCounts;

        return $stats;
    }

    /**
     * Get all local modules with their metadata.
     */
    private function getLocalModules(): array
    {
        if (self::$localModules !== null) {
            return self::$localModules;
        }

        self::$localModules = [];

        if (!is_dir($this->modulesPath)) {
            return self::$localModules;
        }

        $directories = $this->scanModulesDirectory();

        foreach ($directories as $moduleDir) {
            $modulePath = $this->modulesPath . '/' . $moduleDir;
            $composerJsonPath = $modulePath . '/composer.json';

            if (!is_dir($modulePath)) {
                continue;
            }

            try {
                $moduleData = $this->parseModuleDirectory($modulePath, $moduleDir, $composerJsonPath);
                if ($moduleData) {
                    self::$localModules[$moduleData['name']] = $moduleData;
                }
            } catch (\JsonException $e) {
                // Skip directories with invalid composer.json
                continue;
            }
        }

        return self::$localModules;
    }

    /**
     * Scan modules directory for potential module directories.
     */
    private function scanModulesDirectory(): array
    {
        $directories = [];

        try {
            $items = scandir($this->modulesPath);
            if ($items === false) {
                return $directories;
            }

            foreach ($items as $item) {
                if ($item === '.' || $item === '..' || $item === '.gitkeep') {
                    continue;
                }

                $fullPath = $this->modulesPath . '/' . $item;
                if (is_dir($fullPath)) {
                    $directories[] = $item;
                }
            }
        } catch (\Exception $e) {
            // Handle cases where modules directory is not readable
            return [];
        }

        return $directories;
    }

    /**
     * Parse module directory and extract metadata.
     */
    private function parseModuleDirectory(string $modulePath, string $moduleDir, string $composerJsonPath): ?array
    {
        $moduleName = $this->normalizeModuleName($moduleDir);

        // Default module data
        $moduleData = [
            'name' => $moduleName,
            'package' => "cubadevops/flexi-module-" . strtolower($moduleName),
            'path' => $modulePath,
            'version' => 'unknown',
            'metadata' => [
                'description' => '',
                'type' => 'flexi-module',
                'license' => '',
                'authors' => [],
                'keywords' => [],
                'flexi' => [],
                'dependencies' => 0,
                'composerExists' => file_exists($composerJsonPath),
                'source' => 'local',
                'structure' => $this->analyzeModuleStructure($modulePath)
            ]
        ];

        // If composer.json exists, parse it for additional metadata
        if (file_exists($composerJsonPath)) {
            $composerData = $this->parseComposerJson($composerJsonPath);
            if ($composerData) {
                $moduleData = array_merge($moduleData, [
                    'package' => $composerData['name'] ?? $moduleData['package'],
                    'version' => $composerData['version'] ?? $moduleData['version'],
                    'metadata' => array_merge($moduleData['metadata'], [
                        'description' => $composerData['description'] ?? '',
                        'type' => $composerData['type'] ?? 'flexi-module',
                        'license' => $composerData['license'] ?? '',
                        'authors' => $composerData['authors'] ?? [],
                        'keywords' => $composerData['keywords'] ?? [],
                        'flexi' => $composerData['extra']['flexi'] ?? [],
                        'dependencies' => isset($composerData['require']) ? count($composerData['require']) : 0,
                    ])
                ]);
            }
        }

        return $moduleData;
    }

    /**
     * Parse composer.json file.
     */
    private function parseComposerJson(string $composerJsonPath): ?array
    {
        $content = file_get_contents($composerJsonPath);
        if ($content === false) {
            return null;
        }

        try {
            return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return null;
        }
    }

    /**
     * Analyze the structure of a module directory.
     */
    private function analyzeModuleStructure(string $modulePath): array
    {
        $structure = [];

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($modulePath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    $relativePath = str_replace($modulePath . '/', '', $file->getPathname());
                    $structure['directories'][] = $relativePath;
                } else {
                    $relativePath = str_replace($modulePath . '/', '', $file->getPathname());
                    $structure['files'][] = $relativePath;
                }
            }
        } catch (\Exception $e) {
            // If we can't analyze structure, that's okay
            $structure = ['error' => 'Could not analyze structure'];
        }

        return $structure;
    }

    /**
     * Normalize module name for consistent comparison.
     */
    private function normalizeModuleName(string $moduleName): string
    {
        return ucfirst(strtolower($moduleName));
    }

    /**
     * Clear cached module data (useful for testing).
     */
    public static function clearCache(): void
    {
        self::$localModules = null;
    }
}