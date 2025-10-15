<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Factories;

use CubaDevOps\Flexi\Infrastructure\Http\Router;
use CubaDevOps\Flexi\Domain\Interfaces\EventBusInterface;
use CubaDevOps\Flexi\Domain\Interfaces\ObjectBuilderInterface;
use CubaDevOps\Flexi\Domain\Interfaces\SessionStorageInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class RouterFactory
{
    private static Router $instance;

    /**
     * @throws \JsonException
     */
    public static function getInstance(
        SessionStorageInterface $session,
        EventBusInterface $event_bus,
        ObjectBuilderInterface $class_factory,
        ResponseFactoryInterface $response_factory,
        ContainerInterface $container,
        string $routesFilePath
    ): Router {
        if (!isset(self::$instance)) {
            self::$instance = new Router($session, $event_bus, $class_factory, $response_factory, $container);
            self::$instance->loadRoutesFile($routesFilePath);
        }

        return self::$instance;
    }
}
