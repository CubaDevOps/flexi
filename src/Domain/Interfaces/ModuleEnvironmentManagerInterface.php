<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Interfaces;

/**
 * Interface for managing module environment variables.
 *
 * Handles reading module-specific .env files and integrating them
 * with the main application .env file during module activation/deactivation.
 */
interface ModuleEnvironmentManagerInterface
{
    /**
     * Read environment variables from a module's .env file.
     *
     * @param string $modulePath Path to the module directory
     * @param string $moduleName Name of the module
     * @return array Array of environment variables [key => value]
     */
    public function readModuleEnvironment(string $modulePath, string $moduleName): array;

    /**
     * Add module environment variables to the main .env file.
     *
     * @param string $moduleName Name of the module
     * @param array $envVars Environment variables to add [key => value]
     * @return bool True if successful, false otherwise
     */
    public function addModuleEnvironment(string $moduleName, array $envVars): bool;

    /**
     * Remove module environment variables from the main .env file.
     *
     * @param string $moduleName Name of the module
     * @return bool True if successful, false otherwise
     */
    public function removeModuleEnvironment(string $moduleName): bool;

    /**
     * Check if module has environment variables in the main .env file.
     *
     * @param string $moduleName Name of the module
     * @return bool True if module has environment variables, false otherwise
     */
    public function hasModuleEnvironment(string $moduleName): bool;

    /**
     * Get module environment variables from the main .env file.
     *
     * @param string $moduleName Name of the module
     * @return array Array of environment variables [key => value]
     */
    public function getModuleEnvironment(string $moduleName): array;

    /**
     * Update module environment variables in the main .env file.
     * This will preserve user modifications while updating new/removed variables.
     *
     * @param string $moduleName Name of the module
     * @param array $newEnvVars New environment variables [key => value]
     * @return bool True if successful, false otherwise
     */
    public function updateModuleEnvironment(string $moduleName, array $newEnvVars): bool;

    /**
     * Get the path to a module's .env file.
     *
     * @param string $modulePath Path to the module directory
     * @return string Path to the module's .env file
     */
    public function getModuleEnvFilePath(string $modulePath): string;

    /**
     * Check if a module has its own .env file.
     *
     * @param string $modulePath Path to the module directory
     * @return bool True if module has .env file, false otherwise
     */
    public function hasModuleEnvFile(string $modulePath): bool;
}
