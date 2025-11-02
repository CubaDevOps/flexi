<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Application\UseCase;

use CubaDevOps\Flexi\Contracts\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Contracts\Interfaces\DTOInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\HandlerInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\MessageInterface;
use RuntimeException;

/**
 * Use case for synchronizing all modules.
 */
class SyncModules implements HandlerInterface
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
        $discovered_modules = $this->discoverModules();
        $composer_data = json_decode(file_get_contents($this->composer_json_path), true, 512, JSON_THROW_ON_ERROR);

        $result = [
            'discovered' => count($discovered_modules),
            'added' => 0,
            'updated' => 0,
            'removed' => 0,
            'modules' => [],
        ];

        if (!isset($composer_data['repositories'])) {
            $composer_data['repositories'] = [];
        }

        $existing_repos = [];
        foreach ($composer_data['repositories'] as $repo) {
            if (isset($repo['url']) && str_starts_with($repo['url'], './modules/')) {
                $existing_repos[] = $repo['url'];
            }
        }

        $existing_packages = [];
        foreach ($composer_data['require'] ?? [] as $package => $version) {
            if (str_starts_with($package, 'cubadevops/flexi-module-')) {
                $existing_packages[$package] = $version;
            }
        }

        foreach ($discovered_modules as $module_name => $module_info) {
            $package_name = $module_info['package'];
            $repo_url = "./modules/{$module_name}";

            if (!in_array($repo_url, $existing_repos, true)) {
                $composer_data['repositories'][] = [
                    'type' => 'path',
                    'url' => $repo_url,
                    'options' => ['symlink' => true],
                ];
                $existing_repos[] = $repo_url;
            }

            if (!isset($composer_data['require'][$package_name])) {
                $composer_data['require'][$package_name] = '@dev';
                ++$result['added'];
                $result['modules'][$module_name] = 'added';
            } else {
                ++$result['updated'];
                $result['modules'][$module_name] = 'already exists';
            }
        }

        $discovered_packages = array_column($discovered_modules, 'package');
        foreach ($existing_packages as $package => $version) {
            if (!in_array($package, $discovered_packages, true)) {
                unset($composer_data['require'][$package]);
                ++$result['removed'];
            }
        }

        $composer_data['repositories'] = array_values($composer_data['repositories']);
        $this->writeComposerJson($composer_data);

        if ($result['added'] > 0 || $result['removed'] > 0) {
            $update_command = sprintf('cd %s && composer update "cubadevops/flexi-module-*" 2>&1', escapeshellarg($this->root_path));
            $output = [];
            $return_code = 0;
            exec($update_command, $output, $return_code);
            $result['composer_update'] = ['executed' => true, 'success' => 0 === $return_code, 'output' => $output];
        } else {
            $result['composer_update'] = ['executed' => false, 'reason' => 'No changes detected'];
        }

        return new PlainTextMessage(json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
    }

    private function discoverModules(): array
    {
        $modules = [];
        if (!is_dir($this->modules_path)) {
            return $modules;
        }

        foreach (array_diff(scandir($this->modules_path), ['.', '..']) as $directory) {
            $module_path = $this->modules_path.'/'.$directory;
            $composer_json = $module_path.'/composer.json';

            if (is_dir($module_path) && file_exists($composer_json)) {
                try {
                    $data = json_decode(file_get_contents($composer_json), true, 512, JSON_THROW_ON_ERROR);
                    if (isset($data['name'])) {
                        $modules[$directory] = [
                            'package' => $data['name'],
                            'version' => $data['version'] ?? '@dev',
                            'path' => $module_path,
                        ];
                    }
                } catch (\JsonException $e) {
                    continue;
                }
            }
        }

        return $modules;
    }

    private function writeComposerJson(array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (false === file_put_contents($this->composer_json_path, $json."\n")) {
            throw new RuntimeException('Failed to write composer.json');
        }
    }
}
