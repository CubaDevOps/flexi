<?php

declare(strict_types=1);

namespace Flexi\Domain\Interfaces;

use Flexi\Domain\ValueObjects\ConfigurationType;

/**
 * Interface for providing configuration files from active modules
 */
interface ConfigurationFilesProviderInterface
{
    /**
     * Get configuration files of specified type from active modules and core
     *
     * @param ConfigurationType $configType Configuration file type
     * @param bool $includeCoreConfig Whether to include core configuration file
     * @return array Array of absolute file paths to configuration files
     */
    public function getConfigurationFiles(ConfigurationType $configType, bool $includeCoreConfig = true): array;

    /**
     * Get all configuration files from active modules grouped by type
     *
     * @param bool $includeCoreConfig Whether to include core configuration files
     * @return array Associative array with config types as keys and file paths as values
     */
    public function getAllConfigurationFiles(bool $includeCoreConfig = true): array;

    /**
     * Check if a configuration file exists for a specific module
     *
     * @param string $moduleName Module name
     * @param ConfigurationType $configType Configuration type
     * @return bool Whether the configuration file exists
     */
    public function hasModuleConfiguration(string $moduleName, ConfigurationType $configType): bool;

    /**
     * Get the path to a specific module's configuration file
     *
     * @param string $moduleName Module name
     * @param ConfigurationType $configType Configuration type
     * @return string|null Path to configuration file or null if not found
     */
    public function getModuleConfigurationFile(string $moduleName, ConfigurationType $configType): ?string;
}
