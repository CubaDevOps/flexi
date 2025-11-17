<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Classes;

use CubaDevOps\Flexi\Infrastructure\Interfaces\ModuleStateManagerInterface;
use CubaDevOps\Flexi\Infrastructure\Factories\ModuleDetectorInterface;

/**
 * Centralized service for filtering files based on active modules.
 *
 * This class provides methods to filter file paths to include only those
 * from active modules. Uses ModuleStateManager for module state detection
 * and HybridModuleDetector for flexible module discovery.
 *
 * Supports both local modules (modules/) and vendor modules based on
 * composer.json metadata rather than path patterns.
 */
class InstalledModulesFilter
{
    private ModuleStateManagerInterface $stateManager;
    private ModuleDetectorInterface $moduleDetector;
    private static ?array $modulePathCache = null;

    public function __construct(
        ModuleStateManagerInterface $stateManager,
        ModuleDetectorInterface $moduleDetector
    ) {
        $this->stateManager = $stateManager;
        $this->moduleDetector = $moduleDetector;
    }

    /**
     * Filters files to only include those from active modules.
     *
     * Files not in module directories are always included.
     * Files in module directories are only included if the module is active.
     *
     * @param array $files List of file paths
     * @return array Filtered list of files from active modules only
     */
    public function filterFiles(array $files): array
    {
        $activeModules = $this->getActiveModules();
        $modulePathMap = $this->getModulePathMap();

        return array_filter($files, function ($file) use ($activeModules, $modulePathMap) {
            // If the file is not in a module directory, include it
            $moduleName = $this->extractModuleNameFromPath($file, $modulePathMap);
            if (!$moduleName) {
                return true;
            }

            // Only include if module is active
            return in_array($moduleName, $activeModules);
        });
    }

    /**
     * Filters files to only include those from installed (but not necessarily active) modules.
     *
     * This method provides backward compatibility and checks if modules exist
     * in the state manager, regardless of their activation status.
     *
     * @param array $files List of file paths
     * @return array Filtered list of files from known modules
     */
    public function filterInstalledFiles(array $files): array
    {
        $knownModules = $this->stateManager->getAllKnownModules();
        $modulePathMap = $this->getModulePathMap();

        return array_filter($files, function ($file) use ($knownModules, $modulePathMap) {
            // If the file is not in a module directory, include it
            $moduleName = $this->extractModuleNameFromPath($file, $modulePathMap);
            if (!$moduleName) {
                return true;
            }

            // Only include if module is known (installed)
            return in_array($moduleName, $knownModules);
        });
    }

    /**
     * Get list of active module names.
     *
     * @return array List of active module names
     */
    public function getActiveModules(): array
    {
        return $this->stateManager->getActiveModules();
    }

    /**
     * Get list of inactive module names.
     *
     * @return array List of inactive module names
     */
    public function getInactiveModules(): array
    {
        return $this->stateManager->getInactiveModules();
    }

    /**
     * Check if a specific module is active.
     *
     * @param string $moduleName Module name to check
     * @return bool True if module is active
     */
    public function isModuleActive(string $moduleName): bool
    {
        return $this->stateManager->isModuleActive($moduleName);
    }

    /**
     * Checks if a file path is from a module directory.
     *
     * @param string $file File path
     * @return bool True if file is in a module directory
     */
    public function isModuleFile(string $file): bool
    {
        $modulePathMap = $this->getModulePathMap();
        return $this->extractModuleNameFromPath($file, $modulePathMap) !== null;
    }

    /**
     * Checks if a file path is from a local module directory (modules/).
     *
     * @param string $file File path
     * @return bool True if file is in local modules directory
     */
    public function isLocalModuleFile(string $file): bool
    {
        return $this->getModuleTypeFromPath($file) === 'local';
    }

    /**
     * Checks if a file path is from a vendor module directory.
     *
     * @param string $file File path
     * @return bool True if file is in vendor modules directory
     */
    public function isVendorModuleFile(string $file): bool
    {
        return $this->getModuleTypeFromPath($file) === 'vendor';
    }

    /**
     * Extracts the module name from a file path.
     *
     * @param string $file File path
     * @return string|null Module name or null if not a module file
     */
    public function extractModuleName(string $file): ?string
    {
        $modulePathMap = $this->getModulePathMap();
        return $this->extractModuleNameFromPath($file, $modulePathMap);
    }

    /**
     * Get the module type based on file path.
     *
     * @param string $file File path
     * @return string|null 'local', 'vendor', 'mixed', or null if not a module file
     */
    public function getModuleTypeFromPath(string $file): ?string
    {
        $modulePathMap = $this->getModulePathMap();

        foreach ($modulePathMap as $moduleName => $moduleData) {
            if (str_starts_with($file, $moduleData['path'])) {
                return $moduleData['type'];
            }
        }

        return null;
    }

    /**
     * Filter files by specific module activation status.
     *
     * @param array $files List of file paths
     * @param bool $activeOnly True to include only active modules, false for inactive only
     * @return array Filtered list of files
     */
    public function filterByActivationStatus(array $files, bool $activeOnly = true): array
    {
        $targetModules = $activeOnly ? $this->getActiveModules() : $this->getInactiveModules();
        $modulePathMap = $this->getModulePathMap();

        return array_filter($files, function ($file) use ($targetModules, $modulePathMap) {
            // If the file is not in a module directory, include it
            $moduleName = $this->extractModuleNameFromPath($file, $modulePathMap);
            if (!$moduleName) {
                return true;
            }

            // Only include if module matches the activation status
            return in_array($moduleName, $targetModules);
        });
    }

    /**
     * Get statistics about module file filtering.
     *
     * @param array $files List of file paths to analyze
     * @return array Statistics about module files
     */
    public function getFilteringStatistics(array $files): array
    {
        $modulePathMap = $this->getModulePathMap();

        $stats = [
            'total_files' => count($files),
            'core_files' => 0,
            'module_files' => 0,
            'local_module_files' => 0,
            'vendor_module_files' => 0,
            'mixed_module_files' => 0,
            'active_module_files' => 0,
            'inactive_module_files' => 0,
            'modules_found' => [],
            'modules_by_type' => [
                'local' => [],
                'vendor' => [],
                'mixed' => []
            ]
        ];

        foreach ($files as $file) {
            $moduleName = $this->extractModuleNameFromPath($file, $modulePathMap);

            if (!$moduleName) {
                $stats['core_files']++;
            } else {
                $stats['module_files']++;
                $moduleType = $this->getModuleTypeFromPath($file);

                // Count by type
                if ($moduleType === 'local') {
                    $stats['local_module_files']++;
                } elseif ($moduleType === 'vendor') {
                    $stats['vendor_module_files']++;
                } elseif ($moduleType === 'mixed') {
                    $stats['mixed_module_files']++;
                }

                // Track modules by type
                if (!in_array($moduleName, $stats['modules_by_type'][$moduleType] ?? [])) {
                    $stats['modules_by_type'][$moduleType][] = $moduleName;
                }

                // Track all modules found
                if (!in_array($moduleName, $stats['modules_found'])) {
                    $stats['modules_found'][] = $moduleName;
                }

                // Count by activation status
                if ($this->isModuleActive($moduleName)) {
                    $stats['active_module_files']++;
                } else {
                    $stats['inactive_module_files']++;
                }
            }
        }

        return $stats;
    }

    /**
     * Get module path mapping for efficient file path lookups.
     *
     * @return array Map of module name => [path, type]
     */
    private function getModulePathMap(): array
    {
        if (self::$modulePathCache === null) {
            self::$modulePathCache = [];

            $allModules = $this->moduleDetector->getAllModules();

            foreach ($allModules as $module) {
                self::$modulePathCache[$module->getName()] = [
                    'path' => rtrim($module->getPath(), '/') . '/',
                    'type' => $module->getType()->getValue()
                ];
            }
        }

        return self::$modulePathCache;
    }

    /**
     * Extract module name from file path using module path map.
     *
     * @param string $file File path
     * @param array $modulePathMap Module path mapping
     * @return string|null Module name or null if not a module file
     */
    private function extractModuleNameFromPath(string $file, array $modulePathMap): ?string
    {
        foreach ($modulePathMap as $moduleName => $moduleData) {
            if (str_starts_with($file, $moduleData['path'])) {
                return $moduleName;
            }
        }

        return null;
    }

    /**
     * Clear the module path cache (useful when modules are added/removed).
     */
    public function clearCache(): void
    {
        self::$modulePathCache = null;
    }

    /**
     * Refresh module detection and clear cache.
     */
    public function refreshModules(): void
    {
        // Clear cache if the detector supports it (for HybridModuleDetector compatibility)
        if (method_exists($this->moduleDetector, 'clearCache')) {
            $this->moduleDetector->clearCache();
        }
        $this->clearCache();
    }
}
