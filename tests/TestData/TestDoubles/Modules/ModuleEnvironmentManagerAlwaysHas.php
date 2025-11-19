<?php

declare(strict_types=1);

namespace Flexi\Test\TestData\TestDoubles\Modules;

use Flexi\Infrastructure\Classes\ModuleEnvironmentManager;

final class ModuleEnvironmentManagerAlwaysHas extends ModuleEnvironmentManager
{
    public function hasModuleEnvironment(string $moduleName): bool
    {
        return true;
    }
}
