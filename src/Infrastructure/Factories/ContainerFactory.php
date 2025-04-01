<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Factories;

use CubaDevOps\Flexi\Infrastructure\Factories\CacheFactory;
use CubaDevOps\Flexi\Domain\Classes\Container;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ContainerFactory
{
    private static ?Container $instance = null;

    /**
     * @throws ContainerExceptionInterface
     * @throws \JsonException
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public static function getInstance(string $file = ''): Container
    {
        if (!self::$instance) {
            $cache = CacheFactory::getInstance();
            $container = new Container($cache);
            if ($file) {
                $container->loadServices($file);
            }
            self::$instance = $container;
        }

        return self::$instance;
    }
}
