<?php

namespace CubaDevOps\Flexi\Infrastructure\Factories;

use CubaDevOps\Flexi\Infrastructure\Classes\Configuration;

class ConfigurationFactory
{
    private static ?Configuration $instance = null;

    public static function getInstance(): Configuration
    {
        if (!isset(self::$instance)) {
            self::$instance = new Configuration();
        }

        return self::$instance;
    }
}
