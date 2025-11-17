<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Application\UseCase;

use CubaDevOps\Flexi\Infrastructure\Factories\ModuleDetectorInterface;
use CubaDevOps\Flexi\Infrastructure\Interfaces\ModuleStateManagerInterface;
use Flexi\Contracts\Classes\PlainTextMessage;
use Flexi\Contracts\Interfaces\DTOInterface;
use Flexi\Contracts\Interfaces\HandlerInterface;
use Flexi\Contracts\Interfaces\MessageInterface;

/**
 * UseCase for getting module status information.
 *
 * Handles the business logic for retrieving comprehensive information
 * about module status, including active/inactive state, conflicts, and details.
 */
class GetModuleStatus implements HandlerInterface
{
    private ModuleStateManagerInterface $stateManager;
    private ModuleDetectorInterface $moduleDetector;

    public function __construct(
        ModuleStateManagerInterface $stateManager,
        ModuleDetectorInterface $moduleDetector
    ) {
        $this->stateManager = $stateManager;
        $this->moduleDetector = $moduleDetector;
    }

    /**
     * Handle the get module status command.
     *
     * @param DTOInterface $dto ModuleStatusCommand
     * @return MessageInterface
     */
    public function handle(DTOInterface $dto): MessageInterface
    {
        $moduleName = $dto->get('module_name');
        $showDetails = $dto->get('details') ?? false;
        $showConflicts = $dto->get('conflicts') ?? false;
        $filterByType = $dto->get('type');

        // If specific module is requested
        if ($moduleName) {
            return $this->getSpecificModuleStatus($moduleName, $showDetails);
        }

        // Get all modules status
        return $this->getAllModulesStatus($showDetails, $showConflicts, $filterByType);
    }

    /**
     * Get status for a specific module.
     */
    private function getSpecificModuleStatus(string $moduleName, bool $showDetails): MessageInterface
    {
        $moduleInfo = $this->stateManager->getModuleState($moduleName);

        if (!$moduleInfo) {
            return new PlainTextMessage(json_encode([
                'success' => false,
                'error' => "Module '{$moduleName}' not found",
                'module' => $moduleName
            ]));
        }

        $isActive = $this->stateManager->isModuleActive($moduleName);

        $response = [
            'success' => true,
            'module' => [
                'name' => $moduleInfo->getModuleName(),
                'status' => $isActive ? 'active' : 'inactive',
                'type' => $moduleInfo->getType()->getValue(),
                'package' => $moduleInfo->getMetadataValue('package'),
                'version' => $moduleInfo->getMetadataValue('version'),
                'path' => $moduleInfo->getMetadataValue('path'),
            ]
        ];

        if ($showDetails) {
            $response['module']['details'] = [
                'metadata' => $moduleInfo->getMetadata(),
                'last_modified' => $moduleInfo ? $moduleInfo->getLastModified()->format('Y-m-d H:i:s') : null,
                'modified_by' => $moduleInfo ? $moduleInfo->getModifiedBy() : null,
                'has_conflict' => $moduleInfo->getMetadataValue('has_conflict'),
            ];

            if ($moduleInfo->getMetadataValue('has_conflict')) {
                $response['module']['conflict_info'] = [
                    'local_path' => $moduleInfo->getMetadataValue('local_path'),
                    'vendor_path' => $moduleInfo->getMetadataValue('vendor_path'),
                    'resolution_strategy' => $moduleInfo->getMetadataValue('resolution_strategy'),
                ];
            }
        }

        return new PlainTextMessage(json_encode($response, JSON_PRETTY_PRINT));
    }

    /**
     * Get status for all modules.
     */
    private function getAllModulesStatus(bool $showDetails, bool $showConflicts, ?string $filterByType): MessageInterface
    {
        $allModules = $this->moduleDetector->getAllModules();
        $moduleStats = $this->moduleDetector->getModuleStatistics();

        $modules = [];
        $activeCount = 0;
        $inactiveCount = 0;
        $conflictsFound = [];

        foreach ($allModules as $moduleInfo) {
            // Apply type filter if specified
            if ($filterByType && $moduleInfo->getType()->getValue() !== $filterByType) {
                continue;
            }

            $isActive = $this->stateManager->isModuleActive($moduleInfo->getName());
            $moduleState = $this->stateManager->getModuleState($moduleInfo->getName());

            if ($isActive) {
                $activeCount++;
            } else {
                $inactiveCount++;
            }

            $moduleData = [
                'name' => $moduleInfo->getName(),
                'status' => $isActive ? 'active' : 'inactive',
                'type' => $moduleInfo->getType()->getValue(),
                'package' => $moduleInfo->getPackage(),
                'version' => $moduleInfo->getVersion(),
            ];

            if ($showDetails) {
                $moduleData['details'] = [
                    'path' => $moduleInfo->getPath(),
                    'metadata' => $moduleInfo->getMetadata(),
                    'last_modified' => $moduleState ? $moduleState->getLastModified()->format('Y-m-d H:i:s') : null,
                    'modified_by' => $moduleState ? $moduleState->getModifiedBy() : null,
                ];
            }

            // Handle conflicts
            if ($moduleInfo->hasConflict()) {
                $conflictsFound[] = $moduleInfo->getName();

                if ($showConflicts || $showDetails) {
                    $moduleData['conflict'] = [
                        'has_conflict' => true,
                        'local_path' => $moduleInfo->getMetadataValue('local_path'),
                        'vendor_path' => $moduleInfo->getMetadataValue('vendor_path'),
                        'resolution_strategy' => $moduleInfo->getMetadataValue('resolution_strategy'),
                    ];
                }
            }

            $modules[] = $moduleData;
        }

        // Sort modules by name for consistent output
        usort($modules, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        $response = [
            'success' => true,
            'summary' => [
                'total_modules' => count($modules),
                'active' => $activeCount,
                'inactive' => $inactiveCount,
                'conflicts' => count($conflictsFound),
                'types' => $moduleStats
            ],
            'modules' => $modules
        ];

        if (!empty($conflictsFound)) {
            $response['conflicts_found'] = $conflictsFound;
        }

        if ($filterByType) {
            $response['filter'] = ['type' => $filterByType];
        }

        return new PlainTextMessage(json_encode($response, JSON_PRETTY_PRINT));
    }
}