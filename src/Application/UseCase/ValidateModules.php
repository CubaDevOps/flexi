<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Application\UseCase;

use CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo;
use CubaDevOps\Flexi\Domain\Interfaces\ModuleDetectorInterface;
use CubaDevOps\Flexi\Domain\Interfaces\ModuleStateManagerInterface;
use Flexi\Contracts\Classes\PlainTextMessage;
use Flexi\Contracts\Interfaces\DTOInterface;
use Flexi\Contracts\Interfaces\HandlerInterface;
use Flexi\Contracts\Interfaces\MessageInterface;

/**
 * UseCase for validating all discovered modules in the system.
 *
 * This validates module structure, configuration, dependencies,
 * and reports any issues found.
 */
class ValidateModules implements HandlerInterface
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
     * Handle the validate modules command.
     *
     * @param DTOInterface $dto
     * @return MessageInterface
     * @throws \JsonException
     */
    public function handle(DTOInterface $dto): MessageInterface
    {
        $allModules = $this->moduleDetector->getAllModules();
        $results = [
            'total' => count($allModules),
            'valid' => 0,
            'invalid' => 0,
            'warnings' => 0,
            'modules' => [],
        ];

        foreach ($allModules as $moduleInfo) {
            $validation = $this->validateModule($moduleInfo);
            $results['modules'][$moduleInfo->getName()] = $validation;

            if ($validation['valid']) {
                $results['valid']++;
            } else {
                $results['invalid']++;
            }

            $results['warnings'] += count($validation['warnings']);
        }

        $json = json_encode($results, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

        return new PlainTextMessage($json);
    }

    /**
     * Validate a single module using ModuleInfo.
     *
     * @param ModuleInfo $moduleInfo
     * @return array Validation result
     */
    private function validateModule($moduleInfo): array
    {
        $result = [
            'name' => $moduleInfo->getName(),
            'package' => $moduleInfo->getPackage(),
            'version' => $moduleInfo->getVersion(),
            'type' => $moduleInfo->getType()->getValue(),
            'path' => $moduleInfo->getPath(),
            'valid' => true,
            'errors' => [],
            'warnings' => [],
        ];

        // Check if module has conflicts
        if ($moduleInfo->hasConflict()) {
            $result['warnings'][] = 'Module has conflicts between local and vendor versions';
            $result['conflict_info'] = [
                'local_path' => $moduleInfo->getMetadataValue('local_path'),
                'vendor_path' => $moduleInfo->getMetadataValue('vendor_path'),
                'resolution_strategy' => $moduleInfo->getMetadataValue('resolution_strategy'),
            ];
        }

        // Validate composer.json exists and is parseable
        $composerPath = $moduleInfo->getPath() . '/composer.json';
        if (!file_exists($composerPath)) {
            $result['valid'] = false;
            $result['errors'][] = 'composer.json not found';
            return $result;
        }

        // Try to parse composer.json
        try {
            $composerData = json_decode(
                file_get_contents($composerPath),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $e) {
            $result['valid'] = false;
            $result['errors'][] = 'Invalid JSON in composer.json: ' . $e->getMessage();
            return $result;
        }

        // Validate required fields
        $requiredFields = ['name', 'version', 'type', 'require', 'autoload'];
        foreach ($requiredFields as $field) {
            if (!isset($composerData[$field])) {
                $result['valid'] = false;
                $result['errors'][] = "Missing required field: {$field}";
            }
        }

        // Validate package name format
        if (isset($composerData['name'])) {
            if (!preg_match('/^cubadevops\/flexi-module-[a-z]+$/', $composerData['name'])) {
                $result['warnings'][] = 'Package name does not follow convention: cubadevops/flexi-module-{name}';
            }
        }

        // Validate type
        if (isset($composerData['type']) && 'flexi-module' !== $composerData['type']) {
            $result['warnings'][] = 'Type should be "flexi-module"';
        }

        // Validate flexi-contracts dependency
        if (isset($composerData['require'])) {
            if (!isset($composerData['require']['cubadevops/flexi-contracts'])) {
                $result['valid'] = false;
                $result['errors'][] = 'Missing required dependency: cubadevops/flexi-contracts';
            }
        }

        // Validate flexi metadata
        if (isset($composerData['extra']['flexi'])) {
            $flexiMeta = $composerData['extra']['flexi'];

            // Check module-name matches directory
            if (isset($flexiMeta['module-name']) && $flexiMeta['module-name'] !== $moduleInfo->getName()) {
                $result['warnings'][] = "Module name in metadata ('{$flexiMeta['module-name']}') doesn't match directory name ('{$moduleInfo->getName()}')";
            }

            // Validate config files exist
            if (isset($flexiMeta['config-files'])) {
                foreach ($flexiMeta['config-files'] as $configFile) {
                    $configPath = $moduleInfo->getPath() . '/' . $configFile;
                    if (!file_exists($configPath)) {
                        $result['warnings'][] = "Config file not found: {$configFile}";
                    }
                }
            }
        } else {
            $result['warnings'][] = 'Missing flexi metadata in extra section';
        }

        // Validate directory structure
        $requiredDirs = ['Domain', 'Infrastructure', 'Config'];
        foreach ($requiredDirs as $dir) {
            $dirPath = $moduleInfo->getPath() . '/' . $dir;
            if (!is_dir($dirPath)) {
                $result['warnings'][] = "Missing recommended directory: {$dir}";
            }
        }

        // Check if module is active
        $result['active'] = $this->stateManager->isModuleActive($moduleInfo->getName());
        $moduleState = $this->stateManager->getModuleState($moduleInfo->getName());

        if ($moduleState) {
            $result['state_info'] = [
                'last_modified' => $moduleState->getLastModified()->format('Y-m-d H:i:s'),
                'modified_by' => $moduleState->getModifiedBy(),
            ];
        }

        return $result;
    }
}
