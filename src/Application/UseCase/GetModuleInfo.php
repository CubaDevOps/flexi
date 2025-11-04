<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Application\UseCase;

use CubaDevOps\Flexi\Application\Commands\ModuleInfoCommand;
use Flexi\Contracts\Classes\PlainTextMessage;
use Flexi\Contracts\Interfaces\DTOInterface;
use Flexi\Contracts\Interfaces\HandlerInterface;
use Flexi\Contracts\Interfaces\MessageInterface;
use RuntimeException;

/**
 * Use case for showing detailed information about a specific module.
 */
class GetModuleInfo implements HandlerInterface
{
    private string $modules_path;

    public function __construct(string $modules_path = './modules')
    {
        $this->modules_path = rtrim($modules_path, '/');
    }

    /**
     * Handle the module info command.
     *
     * @param ModuleInfoCommand $dto
     *
     * @return MessageInterface
     *
     * @throws \JsonException
     */
    public function handle(DTOInterface $dto): MessageInterface
    {
        $module_name = $dto->get('module_name');

        $module_path = $this->modules_path.'/'.$module_name;

        if (!is_dir($module_path)) {
            throw new RuntimeException("Module '{$module_name}' not found");
        }

        $composer_json = $module_path.'/composer.json';

        if (!file_exists($composer_json)) {
            throw new RuntimeException("Module '{$module_name}' has no composer.json");
        }

        $composer_data = json_decode(
            file_get_contents($composer_json),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $info = [
            'name' => $module_name,
            'package' => $composer_data['name'] ?? 'unknown',
            'version' => $composer_data['version'] ?? 'unknown',
            'description' => $composer_data['description'] ?? '',
            'type' => $composer_data['type'] ?? 'unknown',
            'license' => $composer_data['license'] ?? 'unknown',
            'authors' => $composer_data['authors'] ?? [],
            'keywords' => $composer_data['keywords'] ?? [],
            'path' => $module_path,
        ];

        // Dependencies
        if (isset($composer_data['require'])) {
            $info['dependencies'] = $composer_data['require'];
        }

        // Dev Dependencies
        if (isset($composer_data['require-dev'])) {
            $info['dev_dependencies'] = $composer_data['require-dev'];
        }

        // Autoload
        if (isset($composer_data['autoload'])) {
            $info['autoload'] = $composer_data['autoload'];
        }

        // Flexi metadata
        if (isset($composer_data['extra']['flexi'])) {
            $info['flexi'] = $composer_data['extra']['flexi'];

            // Check if config files exist
            if (isset($composer_data['extra']['flexi']['config-files'])) {
                $info['config_files_status'] = [];
                foreach ($composer_data['extra']['flexi']['config-files'] as $config_file) {
                    $config_path = $module_path.'/'.$config_file;
                    $info['config_files_status'][$config_file] = file_exists($config_path);
                }
            }
        }

        // Directory structure
        $info['structure'] = $this->getDirectoryStructure($module_path);

        // Statistics
        $info['statistics'] = $this->getModuleStatistics($module_path);

        $json = json_encode($info, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

        return new PlainTextMessage($json);
    }

    /**
     * Get the directory structure of the module.
     *
     * @param string $path
     * @param int    $depth
     *
     * @return array
     */
    private function getDirectoryStructure(string $path, int $depth = 0): array
    {
        if ($depth > 2) {
            return [];
        }

        $structure = [];
        $items = array_diff(scandir($path), ['.', '..', 'tests', 'vendor']);

        foreach ($items as $item) {
            $full_path = $path.'/'.$item;
            if (is_dir($full_path)) {
                $structure[$item] = $this->getDirectoryStructure($full_path, $depth + 1);
            }
        }

        return $structure;
    }

    /**
     * Get module statistics (file counts, etc.).
     *
     * @param string $path
     *
     * @return array
     */
    private function getModuleStatistics(string $path): array
    {
        $stats = [
            'total_files' => 0,
            'php_files' => 0,
            'json_files' => 0,
            'test_files' => 0,
        ];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                ++$stats['total_files'];

                $extension = $file->getExtension();
                if ('php' === $extension) {
                    ++$stats['php_files'];
                    if (str_contains($file->getPathname(), '/tests/') || str_contains($file->getFilename(), 'Test')) {
                        ++$stats['test_files'];
                    }
                } elseif ('json' === $extension) {
                    ++$stats['json_files'];
                }
            }
        }

        return $stats;
    }
}
