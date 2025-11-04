<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Application\UseCase;

use CubaDevOps\Flexi\Contracts\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Contracts\Interfaces\DTOInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\HandlerInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\MessageInterface;
use RuntimeException;

/**
 * Use case for uninstalling a Flexi module.
 */
class UninstallModule implements HandlerInterface
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
     * @param DTOInterface $dto
     *
     * @return MessageInterface
     *
     * @throws \JsonException
     */
    public function handle(DTOInterface $dto): MessageInterface
    {
        $module_name = $dto->get('module_name');
        $module_name = ucfirst($module_name);
        $module_path = $this->modules_path.'/'.$module_name;
        $module_composer = $module_path.'/composer.json';

        if (!file_exists($module_composer)) {
            throw new RuntimeException("Module '{$module_name}' has no composer.json");
        }

        $module_data = json_decode(file_get_contents($module_composer), true, 512, JSON_THROW_ON_ERROR);
        $package_name = $module_data['name'] ?? null;

        if (!$package_name) {
            throw new RuntimeException("Module '{$module_name}' composer.json has no 'name' field");
        }

        $composer_data = json_decode(file_get_contents($this->composer_json_path), true, 512, JSON_THROW_ON_ERROR);

        if (!isset($composer_data['require'][$package_name])) {
            $result = [
                'success' => true,
                'message' => "Module '{$module_name}' is not installed",
                'package' => $package_name,
                'action' => 'none',
            ];

            return new PlainTextMessage(json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        }

        unset($composer_data['require'][$package_name]);

        if (isset($composer_data['repositories'])) {
            $composer_data['repositories'] = array_filter(
                $composer_data['repositories'],
                function ($repo) use ($module_name) {
                    return !isset($repo['url']) || $repo['url'] !== "./modules/{$module_name}";
                }
            );
            $composer_data['repositories'] = array_values($composer_data['repositories']);
        }

        $this->writeComposerJson($composer_data);

        $remove_command = sprintf(
            'cd %s && composer remove %s 2>&1',
            escapeshellarg($this->root_path),
            escapeshellarg($package_name)
        );

        $output = [];
        $return_code = 0;
        exec($remove_command, $output, $return_code);

        if (0 !== $return_code) {
            throw new RuntimeException("Failed to uninstall module '{$module_name}': ".implode("\n", $output));
        }

        $result = [
            'success' => true,
            'message' => "Module '{$module_name}' uninstalled successfully",
            'package' => $package_name,
            'action' => 'uninstalled',
            'output' => $output,
        ];

        return new PlainTextMessage(json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
    }

    private function writeComposerJson(array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (false === file_put_contents($this->composer_json_path, $json."\n")) {
            throw new RuntimeException('Failed to write composer.json');
        }
    }
}
