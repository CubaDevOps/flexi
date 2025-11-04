<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Application\UseCase;

use CubaDevOps\Flexi\Contracts\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Contracts\Interfaces\DTOInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\HandlerInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\MessageInterface;

/**
 * Use case for validating all modules configuration.
 */
class ValidateModules implements HandlerInterface
{
    private string $modules_path;

    public function __construct(string $modules_path = './modules')
    {
        $this->modules_path = rtrim($modules_path, '/');
    }

    /**
     * Handle the validate modules command.
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
        $results = [
            'total' => count($modules),
            'valid' => 0,
            'invalid' => 0,
            'warnings' => 0,
            'modules' => [],
        ];

        foreach ($modules as $module_name => $module_path) {
            $validation = $this->validateModule($module_name, $module_path);

            $results['modules'][$module_name] = $validation;

            if ($validation['valid']) {
                ++$results['valid'];
            } else {
                ++$results['invalid'];
            }

            $results['warnings'] += count($validation['warnings']);
        }

        $json = json_encode($results, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

        return new PlainTextMessage($json);
    }

    /**
     * Validate a single module.
     *
     * @param string $module_name
     * @param string $module_path
     *
     * @return array Validation result
     */
    private function validateModule(string $module_name, string $module_path): array
    {
        $result = [
            'name' => $module_name,
            'path' => $module_path,
            'valid' => true,
            'errors' => [],
            'warnings' => [],
        ];

        // Check composer.json exists
        $composer_json = $module_path.'/composer.json';
        if (!file_exists($composer_json)) {
            $result['valid'] = false;
            $result['errors'][] = 'composer.json not found';

            return $result;
        }

        // Try to parse composer.json
        try {
            $composer_data = json_decode(
                file_get_contents($composer_json),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $e) {
            $result['valid'] = false;
            $result['errors'][] = 'Invalid JSON in composer.json: '.$e->getMessage();

            return $result;
        }

        // Validate required fields
        $required_fields = ['name', 'version', 'type', 'require', 'autoload'];
        foreach ($required_fields as $field) {
            if (!isset($composer_data[$field])) {
                $result['valid'] = false;
                $result['errors'][] = "Missing required field: {$field}";
            }
        }

        // Validate package name format
        if (isset($composer_data['name'])) {
            if (!preg_match('/^cubadevops\/flexi-module-[a-z]+$/', $composer_data['name'])) {
                $result['warnings'][] = 'Package name does not follow convention: cubadevops/flexi-module-{name}';
            }
        }

        // Validate type
        if (isset($composer_data['type']) && 'flexi-module' !== $composer_data['type']) {
            $result['warnings'][] = 'Type should be "flexi-module"';
        }

        // Validate flexi-contracts dependency
        if (isset($composer_data['require'])) {
            if (!isset($composer_data['require']['cubadevops/flexi-contracts'])) {
                $result['valid'] = false;
                $result['errors'][] = 'Missing required dependency: cubadevops/flexi-contracts';
            }
        }

        // Validate autoload PSR-4
        if (isset($composer_data['autoload']['psr-4'])) {
            $expected_namespace = "CubaDevOps\\Flexi\\Modules\\{$module_name}\\";
            if (!isset($composer_data['autoload']['psr-4'][$expected_namespace])) {
                $result['warnings'][] = "PSR-4 namespace should be: {$expected_namespace}";
            }
        }

        // Validate flexi metadata
        if (isset($composer_data['extra']['flexi'])) {
            $flexi_meta = $composer_data['extra']['flexi'];

            // Check module-name matches directory
            if (isset($flexi_meta['module-name']) && $flexi_meta['module-name'] !== $module_name) {
                $result['warnings'][] = "Module name in metadata ('{$flexi_meta['module-name']}') doesn't match directory name ('{$module_name}')";
            }

            // Validate config files exist
            if (isset($flexi_meta['config-files'])) {
                foreach ($flexi_meta['config-files'] as $config_file) {
                    $config_path = $module_path.'/'.$config_file;
                    if (!file_exists($config_path)) {
                        $result['warnings'][] = "Config file not found: {$config_file}";
                    }
                }
            }
        } else {
            $result['warnings'][] = 'Missing flexi metadata in extra section';
        }

        // Validate directory structure
        $required_dirs = ['Domain', 'Infrastructure', 'Config'];
        foreach ($required_dirs as $dir) {
            $dir_path = $module_path.'/'.$dir;
            if (!is_dir($dir_path)) {
                $result['warnings'][] = "Missing recommended directory: {$dir}";
            }
        }

        return $result;
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
}
