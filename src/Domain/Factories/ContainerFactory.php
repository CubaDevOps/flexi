<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Factories;

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
            $container = new Container(new InMemoryCache());
            if ($file) {
                $container->loadServices($file);
            }
            self::$instance = $container;
        }

        return self::$instance;
    }
}
