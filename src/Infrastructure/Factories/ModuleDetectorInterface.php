<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Factories;

use CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo;

/**
 * Interface for module detection
 *
 * Defines the contract for detecting and retrieving information about
 * installed modules regardless of their installation method (local, vendor, hybrid).
 */
interface ModuleDetectorInterface
{
    /**
     * Check if a module is installed.
     *
     * @param string $moduleName The name of the module to check
     * @return bool True if the module is installed, false otherwise
     */
    public function isModuleInstalled(string $moduleName): bool;

    /**
     * Get all available modules.
     *
     * @return ModuleInfo[] Array of ModuleInfo objects indexed by module name
     */
    public function getAllModules(): array;

    /**
     * Get information about a specific module.
     *
     * @param string $moduleName The name of the module
     * @return ModuleInfo|null ModuleInfo object if found, null otherwise
     */
    public function getModuleInfo(string $moduleName): ?ModuleInfo;

    /**
     * Get statistics about module distribution and types.
     *
     * @return array Array containing statistics about modules
     */
    public function getModuleStatistics(): array;
}