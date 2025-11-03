<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Classes\Traits;

/**
 * Trait for providing information about installed Flexi modules.
 *
 * This trait reads composer.json to determine which modules are installed
 * and provides methods to check module availability. Can be used by both
 * core and modules to verify module dependencies.
 */
trait InstalledModulesProviderTrait
{
    use FileHandlerTrait;
    private ?array $installedModules = null;

    /**
     * Gets the path to composer.json.
     *
     * @return string Path to composer.json
     */
    private function getComposerJsonPath(): string
    {
        return $this->normalize('./composer.json');
    }

    /**
     * Gets the package prefix for Flexi modules.
     *
     * @return string Module package prefix
     */
    private function getModulePackagePrefix(): string
    {
        return 'cubadevops/flexi-module-';
    }

    /**
     * Gets the list of installed modules from composer.json.
     *
     * Returns an associative array where keys are module names and values are package names.
     * Example: ['Auth' => 'cubadevops/flexi-module-auth']
     *
     * @return array Associative array of installed module packages
     */
    protected function getInstalledModules(): array
    {
        if ($this->installedModules !== null) {
            return $this->installedModules;
        }

        $composerJsonPath = $this->getComposerJsonPath();

        if (!file_exists($composerJsonPath)) {
            $this->installedModules = [];
            return $this->installedModules;
        }

        try {
            $composerData = json_decode(
                file_get_contents($composerJsonPath),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $e) {
            $this->installedModules = [];
            return $this->installedModules;
        }

        $this->installedModules = [];

        if (isset($composerData['require'])) {
            foreach ($composerData['require'] as $package => $version) {
                if ($this->isFlexiModulePackage($package)) {
                    $moduleName = $this->extractModuleNameFromPackage($package);
                    $this->installedModules[$moduleName] = $package;
                }
            }
        }

        return $this->installedModules;
    }

    /**
     * Checks if a specific module is installed.
     *
     * @param string $moduleName Module name (e.g., 'Auth', 'ErrorHandling')
     * @return bool True if the module is installed
     */
    protected function isModuleInstalled(string $moduleName): bool
    {
        $installedModules = $this->getInstalledModules();
        return isset($installedModules[$moduleName]);
    }

    /**
     * Clears the cached list of installed modules.
     * Useful for testing or when composer.json changes during runtime.
     */
    protected function clearModulesCache(): void
    {
        $this->installedModules = null;
    }

    /**
     * Checks if a package is a Flexi module package.
     *
     * @param string $package Package name
     * @return bool True if it's a Flexi module package
     */
    private function isFlexiModulePackage(string $package): bool
    {
        return str_starts_with($package, $this->getModulePackagePrefix());
    }

    /**
     * Extracts the module name from a package name.
     *
     * Converts package name format to module name format.
     * Example: 'cubadevops/flexi-module-auth' -> 'Auth'
     *
     * @param string $package Package name
     * @return string Module name
     */
    private function extractModuleNameFromPackage(string $package): string
    {
        $moduleName = str_replace($this->getModulePackagePrefix(), '', $package);
        return ucfirst($moduleName);
    }
}
