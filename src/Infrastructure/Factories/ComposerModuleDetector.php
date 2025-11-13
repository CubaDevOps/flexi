<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Factories;

/**
 * Default implementation for module detection using composer.json
 */
class ComposerModuleDetector implements ModuleDetectorInterface
{
    private static ?array $installedModules = null;

    public function isModuleInstalled(string $moduleName): bool
    {
        if (self::$installedModules === null) {
            self::$installedModules = [];
            $composerJsonPath = './composer.json';

            if (file_exists($composerJsonPath)) {
                try {
                    $composerData = json_decode(
                        file_get_contents($composerJsonPath),
                        true,
                        512,
                        JSON_THROW_ON_ERROR
                    );

                    if (isset($composerData['require'])) {
                        foreach ($composerData['require'] as $package => $version) {
                            // Check if it's a flexi module package
                            if (preg_match('/^cubadevops\/flexi-module-(.+)$/', $package, $matches)) {
                                self::$installedModules[$matches[1]] = true;
                            }
                        }
                    }
                } catch (\JsonException $e) {
                    // If composer.json is invalid, assume no modules are installed
                    return false;
                }
            }
        }

        return isset(self::$installedModules[strtolower($moduleName)]);
    }
}