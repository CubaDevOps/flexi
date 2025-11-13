<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Factories;

/**
 * Interface for module detection
 */
interface ModuleDetectorInterface
{
    public function isModuleInstalled(string $moduleName): bool;
}