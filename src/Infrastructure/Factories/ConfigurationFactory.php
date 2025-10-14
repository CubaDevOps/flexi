<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Factories;

use CubaDevOps\Flexi\Infrastructure\Classes\Configuration;
use CubaDevOps\Flexi\Infrastructure\Classes\ConfigurationRepository;

class ConfigurationFactory
{
    private static ?Configuration $instance = null;

    public static function getInstance(): Configuration
    {
        if (!isset(self::$instance)) {
            self::$instance = new Configuration(new ConfigurationRepository());
        }

        return self::$instance;
    }
}
