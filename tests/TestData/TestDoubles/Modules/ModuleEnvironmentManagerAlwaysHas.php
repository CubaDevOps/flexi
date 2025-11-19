<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\TestData\TestDoubles\Modules;

use CubaDevOps\Flexi\Infrastructure\Classes\ModuleEnvironmentManager;

final class ModuleEnvironmentManagerAlwaysHas extends ModuleEnvironmentManager
{
    public function hasModuleEnvironment(string $moduleName): bool
    {
        return true;
    }
}
