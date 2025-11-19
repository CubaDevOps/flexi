<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\TestData\TestDoubles\Modules;

use CubaDevOps\Flexi\Infrastructure\Classes\ModuleEnvironmentManager;

final class ModuleEnvironmentManagerRemovalFails extends ModuleEnvironmentManager
{
    public bool $removeCalled = false;

    public function hasModuleEnvironment(string $moduleName): bool
    {
        return true;
    }

    public function getModuleEnvironment(string $moduleName): array
    {
        return ['CURRENT' => 'value'];
    }

    public function removeModuleEnvironment(string $moduleName): bool
    {
        $this->removeCalled = true;

        return false;
    }
}
