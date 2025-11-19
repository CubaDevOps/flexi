<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Application\UseCase;

use CubaDevOps\Flexi\Domain\Interfaces\ModuleDetectorInterface;
use CubaDevOps\Flexi\Domain\Interfaces\ModuleStateManagerInterface;
use Flexi\Contracts\Classes\PlainTextMessage;
use Flexi\Contracts\Interfaces\DTOInterface;
use Flexi\Contracts\Interfaces\HandlerInterface;
use Flexi\Contracts\Interfaces\MessageInterface;
use RuntimeException;

/**
 * UseCase for getting detailed information about a specific module.
 *
 * Returns comprehensive module information including metadata, state,
 * configuration files, and dependency information.
 */
class GetModuleInfo implements HandlerInterface
{
    /**
     * Module state manager for activation status.
     */
    private ModuleStateManagerInterface $stateManager;

    /**
     * Module detector for discovering modules.
     */
    private ModuleDetectorInterface $moduleDetector;

    /**
     * Constructor.
     */
    public function __construct(
        ModuleStateManagerInterface $stateManager,
        ModuleDetectorInterface $moduleDetector
    ) {
        $this->stateManager = $stateManager;
        $this->moduleDetector = $moduleDetector;
    }

    /**
     * Handle the module info command.
     *
     * @param DTOInterface $dto
     * @return MessageInterface
     * @throws \JsonException
     */
    public function handle(DTOInterface $dto): MessageInterface
    {
        $module_name = $dto->get('module_name');

        // Find module using detector
        $moduleInfo = $this->moduleDetector->getModuleInfo($module_name);

        if ($moduleInfo === null) {
            return new PlainTextMessage("Module '{$module_name}' not found");
        }

        $info = [
            'name' => $moduleInfo->getName(),
            'package' => $moduleInfo->getPackage(),
            'version' => $moduleInfo->getVersion(),
            'type' => 'unknown', // Will be updated with composer.json type if available
            'path' => $moduleInfo->getPath(),
            'active' => $this->stateManager->isModuleActive($module_name),
            'installation_type' => $moduleInfo->getType()->getValue(), // Keep installation type separate
        ];

        // Add module state information
        $moduleState = $this->stateManager->getModuleState($module_name);
        if ($moduleState) {
            $info['state_info'] = [
                'last_modified' => $moduleState->getLastModified()->format('Y-m-d H:i:s'),
                'modified_by' => $moduleState->getModifiedBy(),
            ];
        }

        // Add conflict information if present
        if ($moduleInfo->hasConflict()) {
            $info['conflict'] = [
                'has_conflict' => true,
                'local_path' => $moduleInfo->getMetadataValue('local_path'),
                'vendor_path' => $moduleInfo->getMetadataValue('vendor_path'),
                'resolution_strategy' => $moduleInfo->getMetadataValue('resolution_strategy'),
            ];
        }

        // Add metadata
        $metadata = $moduleInfo->getMetadata();
        if (!empty($metadata)) {
            $info['metadata'] = $metadata;
        }

        // Read composer.json for additional details
        $composer_json = $moduleInfo->getPath() . '/composer.json';
        if (file_exists($composer_json)) {
            $composer_data = json_decode(
                file_get_contents($composer_json),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            // Update type with composer.json type if available
            $info['type'] = $composer_data['type'] ?? 'unknown';

            // Add additional composer information
            $info['description'] = $composer_data['description'] ?? '';
            $info['license'] = $composer_data['license'] ?? 'unknown';
            $info['authors'] = $composer_data['authors'] ?? [];
            $info['keywords'] = $composer_data['keywords'] ?? [];

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
                        $config_path = $moduleInfo->getPath() . '/' . $config_file;
                        $info['config_files_status'][$config_file] = file_exists($config_path);
                    }
                }
            }
        }

        // Directory structure
        $info['structure'] = $this->getDirectoryStructure($moduleInfo->getPath());

        // Statistics
        $info['statistics'] = $this->getModuleStatistics($moduleInfo->getPath());

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
