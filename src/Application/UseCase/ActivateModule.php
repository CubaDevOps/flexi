<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Application\UseCase;

use CubaDevOps\Flexi\Infrastructure\Interfaces\ModuleStateManagerInterface;
use CubaDevOps\Flexi\Infrastructure\Factories\HybridModuleDetector;
use Flexi\Contracts\Classes\PlainTextMessage;
use Flexi\Contracts\Interfaces\DTOInterface;
use Flexi\Contracts\Interfaces\HandlerInterface;
use Flexi\Contracts\Interfaces\MessageInterface;

/**
 * Use case for activating a module.
 *
 * Handles the business logic for activating a specific module,
 * including validation and state management.
 */
class ActivateModule implements HandlerInterface
{
    private ModuleStateManagerInterface $stateManager;
    private HybridModuleDetector $moduleDetector;

    public function __construct(
        ModuleStateManagerInterface $stateManager,
        HybridModuleDetector $moduleDetector
    ) {
        $this->stateManager = $stateManager;
        $this->moduleDetector = $moduleDetector;
    }

    /**
     * Handle the activate module command.
     *
     * @param DTOInterface $dto ActivateModuleCommand
     * @return MessageInterface
     */
    public function handle(DTOInterface $dto): MessageInterface
    {
        $moduleName = $dto->get('module_name');
        $modifiedBy = $dto->get('modified_by') ?? 'user';

        // Validate input
        if (empty($moduleName)) {
            return new PlainTextMessage(json_encode([
                'success' => false,
                'error' => 'Module name is required',
                'module' => null
            ]));
        }

        // Check if module exists (is discoverable)
        $moduleInfo = $this->moduleDetector->getModuleInfo($moduleName);
        if (!$moduleInfo) {
            return new PlainTextMessage(json_encode([
                'success' => false,
                'error' => "Module '{$moduleName}' not found. Use 'modules:list' to see available modules.",
                'module' => $moduleName
            ]));
        }

        // Check if module is already active
        if ($this->stateManager->isModuleActive($moduleName)) {
            return new PlainTextMessage(json_encode([
                'success' => false,
                'error' => "Module '{$moduleName}' is already active",
                'module' => $moduleName,
                'status' => 'already_active'
            ]));
        }

        // Initialize module state if it doesn't exist, or just activate it
        $success = $this->stateManager->activateModule($moduleName, $modifiedBy);

        if (!$success) {
            return new PlainTextMessage(json_encode([
                'success' => false,
                'error' => "Failed to activate module '{$moduleName}'",
                'module' => $moduleName
            ]));
        }

        // Get updated state for response
        $moduleState = $this->stateManager->getModuleState($moduleName);

        $response = [
            'success' => true,
            'message' => "Module '{$moduleName}' has been activated successfully",
            'module' => $moduleName,
            'status' => 'activated',
            'details' => [
                'type' => $moduleInfo->getType()->getValue(),
                'path' => $moduleInfo->getPath(),
                'package' => $moduleInfo->getPackage(),
                'version' => $moduleInfo->getVersion(),
                'last_modified' => $moduleState ? $moduleState->getLastModified()->format('Y-m-d H:i:s') : null,
                'modified_by' => $modifiedBy
            ]
        ];

        // Add conflict warning if applicable
        if ($moduleInfo->hasConflict()) {
            $response['warning'] = "Module exists in multiple locations. Using: " . $moduleInfo->getPath();
            $response['details']['conflict'] = true;
            $response['details']['local_path'] = $moduleInfo->getMetadataValue('local_path');
            $response['details']['vendor_path'] = $moduleInfo->getMetadataValue('vendor_path');
        }

        return new PlainTextMessage(json_encode($response, JSON_PRETTY_PRINT));
    }
}