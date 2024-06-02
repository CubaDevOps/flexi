<?php

namespace CubaDevOps\Flexi\Domain\Factories;

use CubaDevOps\Flexi\Domain\Classes\Container;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;

class ContainerFactory
{
    private static ?Container $instance = null;

    /**
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public static function getInstance(string $file = ''): Container
    {
        if (!self::$instance) {
            $container = new Container();
            if ($file) {
                $container->loadServices($file);
            }
            self::$instance = $container;
        }

        return self::$instance;
    }
}
