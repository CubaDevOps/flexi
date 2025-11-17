<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Application\UseCase;

use CubaDevOps\Flexi\Infrastructure\Interfaces\ModuleEnvironmentManagerInterface;
use CubaDevOps\Flexi\Infrastructure\Factories\HybridModuleDetector;
use CubaDevOps\Flexi\Infrastructure\Interfaces\ModuleStateManagerInterface;
use Flexi\Contracts\Classes\PlainTextMessage;
use Flexi\Contracts\Interfaces\DTOInterface;
use Flexi\Contracts\Interfaces\HandlerInterface;
use Flexi\Contracts\Interfaces\MessageInterface;

/**
 * Use case for updating module environment variables.
 *
 * Handles updating environment variables from a module's .env file
 * while preserving user modifications in the main .env file.
 */
class UpdateModuleEnvironment implements HandlerInterface
{
    private ModuleEnvironmentManagerInterface $envManager;
    private HybridModuleDetector $moduleDetector;
    private ModuleStateManagerInterface $stateManager;

    public function __construct(
        ModuleEnvironmentManagerInterface $envManager,
        HybridModuleDetector $moduleDetector,
        ModuleStateManagerInterface $stateManager
    ) {
        $this->envManager = $envManager;
        $this->moduleDetector = $moduleDetector;
        $this->stateManager = $stateManager;
    }

    /**
     * Handle the update module environment command.
     *
     * @param DTOInterface $dto UpdateModuleEnvironmentCommand
     * @return MessageInterface
     */
    public function handle(DTOInterface $dto): MessageInterface
    {
        $moduleName = $dto->get('module_name');
        $modifiedBy = $dto->get('modified_by') ?? 'user';
        $forceUpdate = $dto->get('force') ?? false;

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

        // Check if module is active
        if (!$this->stateManager->isModuleActive($moduleName)) {
            return new PlainTextMessage(json_encode([
                'success' => false,
                'error' => "Module '{$moduleName}' is not active. Activate it first to update environment variables.",
                'module' => $moduleName
            ]));
        }

        // Check if module has .env file
        if (!$this->envManager->hasModuleEnvFile($moduleInfo->getPath())) {
            return new PlainTextMessage(json_encode([
                'success' => false,
                'error' => "Module '{$moduleName}' does not have a .env file.",
                'module' => $moduleName
            ]));
        }

        // Read module environment variables
        $moduleEnvVars = $this->envManager->readModuleEnvironment($moduleInfo->getPath(), $moduleName);
        if (empty($moduleEnvVars)) {
            return new PlainTextMessage(json_encode([
                'success' => false,
                'error' => "Module '{$moduleName}' .env file is empty or invalid.",
                'module' => $moduleName
            ]));
        }

        $warnings = [];
        $beforeVars = [];
        $afterVars = [];

        // Get current environment variables if they exist
        if ($this->envManager->hasModuleEnvironment($moduleName)) {
            $beforeVars = $this->envManager->getModuleEnvironment($moduleName);
        }

        // Update environment variables
        $success = false;
        if ($forceUpdate) {
            // Force update: replace all variables with module defaults
            $success = $this->envManager->removeModuleEnvironment($moduleName) &&
                      $this->envManager->addModuleEnvironment($moduleName, $moduleEnvVars);
            $afterVars = $moduleEnvVars;
        } else {
            // Gentle update: preserve user modifications
            $success = $this->envManager->updateModuleEnvironment($moduleName, $moduleEnvVars);
            if ($success) {
                $afterVars = $this->envManager->getModuleEnvironment($moduleName);
            }
        }

        if (!$success) {
            return new PlainTextMessage(json_encode([
                'success' => false,
                'error' => "Failed to update environment variables for module '{$moduleName}'",
                'module' => $moduleName
            ]));
        }

        // Identify changes
        $addedVars = array_diff_key($afterVars, $beforeVars);
        $removedVars = array_diff_key($beforeVars, $afterVars);
        $modifiedVars = [];
        $preservedVars = [];

        foreach ($beforeVars as $key => $oldValue) {
            if (isset($afterVars[$key])) {
                if ($afterVars[$key] !== $oldValue) {
                    $modifiedVars[$key] = [
                        'old' => $oldValue,
                        'new' => $afterVars[$key]
                    ];
                } else {
                    $preservedVars[] = $key;
                }
            }
        }

        $response = [
            'success' => true,
            'message' => "Environment variables for module '{$moduleName}' updated successfully",
            'module' => $moduleName,
            'update_mode' => $forceUpdate ? 'force' : 'preserve_user_changes',
            'details' => [
                'total_vars_before' => count($beforeVars),
                'total_vars_after' => count($afterVars),
                'added_vars' => count($addedVars),
                'removed_vars' => count($removedVars),
                'modified_vars' => count($modifiedVars),
                'preserved_vars' => count($preservedVars)
            ],
            'changes' => [
                'added' => array_keys($addedVars),
                'removed' => array_keys($removedVars),
                'modified' => array_keys($modifiedVars),
                'preserved' => $preservedVars
            ]
        ];

        // Add warnings if any
        if (!empty($warnings)) {
            $response['warnings'] = $warnings;
        }

        // Add detailed change information if there are modifications
        if (!empty($modifiedVars) && !$forceUpdate) {
            $response['modification_details'] = $modifiedVars;
        }

        return new PlainTextMessage(json_encode($response, JSON_PRETTY_PRINT));
    }
}