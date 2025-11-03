<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Classes;

use CubaDevOps\Flexi\Contracts\Classes\Traits\InstalledModulesProviderTrait;

/**
 * Centralized service for filtering files based on installed modules.
 *
 * This class provides methods to filter file paths to include only those
 * from installed modules. Uses InstalledModulesProviderTrait for module detection.
 */
class InstalledModulesFilter
{
    use InstalledModulesProviderTrait;

    private const MODULE_PATH_PATTERN = '#/modules/([^/]+)/#';

    /**
     * Filters files to only include those from installed modules.
     *
     * Files not in module directories are always included.
     * Files in module directories are only included if the module is installed.
     *
     * @param array $files List of file paths
     * @return array Filtered list of files from installed modules only
     */
    public function filterFiles(array $files): array
    {
        $installedModules = $this->getInstalledModules();

        return array_filter($files, function ($file) use ($installedModules) {
            // If the file is not in a module directory, include it
            if (!$this->isModuleFile($file)) {
                return true;
            }

            // Extract module name from file path
            $moduleName = ucfirst(strtolower($this->extractModuleName($file)));

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
    public function isModuleFile(string $file): bool
    {
        return (bool) preg_match(self::MODULE_PATH_PATTERN, $file);
    }

    /**
     * Extracts the module name from a file path.
     *
     * @param string $file File path
     * @return string|null Module name or null if not a module file
     */
    public function extractModuleName(string $file): ?string
    {
        if (preg_match(self::MODULE_PATH_PATTERN, $file, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
