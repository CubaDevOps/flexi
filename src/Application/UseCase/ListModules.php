<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Application\UseCase;

use Flexi\Contracts\Classes\PlainTextMessage;
use Flexi\Contracts\Interfaces\DTOInterface;
use Flexi\Contracts\Interfaces\HandlerInterface;
use Flexi\Contracts\Interfaces\MessageInterface;

/**
 * Use case for listing all installed Flexi modules with their details.
 */
class ListModules implements HandlerInterface
{
    private string $modules_path;
    private string $vendor_path;

    public function __construct(
        string $modules_path = './modules',
        string $vendor_path = './vendor/cubadevops'
    ) {
        $this->modules_path = rtrim($modules_path, '/');
        $this->vendor_path = rtrim($vendor_path, '/');
    }

    /**
     * Handle the list modules command.
     *
     * @param DTOInterface $dto
     *
     * @return MessageInterface
     *
     * @throws \JsonException
     */
    public function handle(DTOInterface $dto): MessageInterface
    {
        $modules = $this->scanModules();
        $installed_modules = $this->getInstalledModules();

        $result = [
            'total' => count($modules),
            'installed' => count($installed_modules),
            'modules' => [],
        ];

        foreach ($modules as $module_name => $module_path) {
            $composer_json = $module_path.'/composer.json';
            $module_info = [
                'name' => $module_name,
                'path' => $module_path,
                'installed' => in_array($module_name, $installed_modules, true),
                'composer_exists' => file_exists($composer_json),
            ];

            if (file_exists($composer_json)) {
                $composer_data = json_decode(
                    file_get_contents($composer_json),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );

                $module_info['package'] = $composer_data['name'] ?? 'unknown';
                $module_info['version'] = $composer_data['version'] ?? 'unknown';
                $module_info['description'] = $composer_data['description'] ?? '';
                $module_info['type'] = $composer_data['type'] ?? 'unknown';

                // Extra flexi metadata
                if (isset($composer_data['extra']['flexi'])) {
                    $module_info['flexi'] = $composer_data['extra']['flexi'];
                }

                // Dependencies count
                $module_info['dependencies'] = isset($composer_data['require'])
                    ? count($composer_data['require'])
                    : 0;
            }

            $result['modules'][$module_name] = $module_info;
        }

        $json = json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

        return new PlainTextMessage($json);
    }

    /**
     * Scan the modules directory for available modules.
     *
     * @return array Array of module_name => module_path
     */
    private function scanModules(): array
    {
        $modules = [];

        if (!is_dir($this->modules_path)) {
            return $modules;
        }

        $directories = array_diff(
            scandir($this->modules_path),
            ['.', '..']
        );

        foreach ($directories as $directory) {
            $full_path = $this->modules_path.'/'.$directory;
            if (is_dir($full_path)) {
                $modules[$directory] = $full_path;
            }
        }

        return $modules;
    }

    /**
     * Get list of modules installed via Composer (symlinked in vendor).
     *
     * @return array List of installed module names
     */
    private function getInstalledModules(): array
    {
        $installed = [];

        if (!is_dir($this->vendor_path)) {
            return $installed;
        }

        $packages = array_diff(
            scandir($this->vendor_path),
            ['.', '..']
        );

        foreach ($packages as $package) {
            if (str_starts_with($package, 'flexi-module-')) {
                $module_name = $this->packageNameToModuleName($package);
                $installed[] = $module_name;
            }
        }

        return $installed;
    }

    /**
     * Convert package name to module name.
     * Example: flexi-module-auth => Auth
     *
     * @param string $package_name
     *
     * @return string
     */
    private function packageNameToModuleName(string $package_name): string
    {
        $name = str_replace('flexi-module-', '', $package_name);

        return ucfirst($name);
    }
}
