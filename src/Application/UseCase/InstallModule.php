<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Application\UseCase;

use CubaDevOps\Flexi\Application\Commands\InstallModuleCommand;
use CubaDevOps\Flexi\Contracts\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Contracts\Interfaces\DTOInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\HandlerInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\MessageInterface;
use RuntimeException;

/**
 * Use case for installing a Flexi module by updating composer.json and running composer install.
 */
class InstallModule implements HandlerInterface
{
    private string $modules_path;
    private string $composer_json_path;
    private string $root_path;

    public function __construct(
        string $modules_path = './modules',
        string $composer_json_path = './composer.json',
        string $root_path = '.'
    ) {
        $this->modules_path = rtrim($modules_path, '/');
        $this->composer_json_path = $composer_json_path;
        $this->root_path = rtrim($root_path, '/');
    }

    /**
     * Handle the install module command.
     *
     * @param InstallModuleCommand $dto
     *
     * @return MessageInterface
     *
     * @throws \JsonException
     */
    public function handle(DTOInterface $dto): MessageInterface
    {
        $module_name = $dto->get('module_name');

        // Normalize module name (capitalize first letter)
        $module_name = ucfirst($module_name);

        $module_path = $this->modules_path.'/'.$module_name;

        // Check if module exists
        if (!is_dir($module_path)) {
            throw new RuntimeException("Module '{$module_name}' not found in {$this->modules_path}");
        }

        // Check if module has composer.json
        $module_composer = $module_path.'/composer.json';
        if (!file_exists($module_composer)) {
            throw new RuntimeException("Module '{$module_name}' has no composer.json");
        }

        // Read module's composer.json
        $module_data = json_decode(
            file_get_contents($module_composer),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $package_name = $module_data['name'] ?? null;
        if (!$package_name) {
            throw new RuntimeException("Module '{$module_name}' composer.json has no 'name' field");
        }

        // Read main composer.json
        $composer_data = json_decode(
            file_get_contents($this->composer_json_path),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        // Check if already installed
        if (isset($composer_data['require'][$package_name])) {
            $result = [
                'success' => true,
                'message' => "Module '{$module_name}' is already installed",
                'package' => $package_name,
                'action' => 'none',
            ];

            $json = json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

            return new PlainTextMessage($json);
        }

        // Add repository if not exists
        $repository_exists = false;
        $repository_config = [
            'type' => 'path',
            'url' => "./modules/{$module_name}",
            'options' => [
                'symlink' => true,
            ],
        ];

        if (!isset($composer_data['repositories'])) {
            $composer_data['repositories'] = [];
        }

        foreach ($composer_data['repositories'] as $repo) {
            if (isset($repo['url']) && $repo['url'] === $repository_config['url']) {
                $repository_exists = true;
                break;
            }
        }

        if (!$repository_exists) {
            $composer_data['repositories'][] = $repository_config;
        }

        // Add to require
        $version = $module_data['version'] ?? '@dev';
        $composer_data['require'][$package_name] = '@dev'; // Always use @dev for local development

        // Write updated composer.json
        $this->writeComposerJson($composer_data);

        // Run composer update for this package
        $update_command = sprintf(
            'cd %s && composer update %s 2>&1',
            escapeshellarg($this->root_path),
            escapeshellarg($package_name)
        );

        $output = [];
        $return_code = 0;
        exec($update_command, $output, $return_code);

        if (0 !== $return_code) {
            throw new RuntimeException(
                "Failed to install module '{$module_name}': ".implode("\n", $output)
            );
        }

        $result = [
            'success' => true,
            'message' => "Module '{$module_name}' installed successfully",
            'package' => $package_name,
            'version' => $version,
            'action' => 'installed',
            'output' => $output,
        ];

        $json = json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

        return new PlainTextMessage($json);
    }

    /**
     * Write composer.json with proper formatting.
     *
     * @param array $data
     */
    private function writeComposerJson(array $data): void
    {
        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        if (false === file_put_contents($this->composer_json_path, $json."\n")) {
            throw new RuntimeException('Failed to write composer.json');
        }
    }
}
