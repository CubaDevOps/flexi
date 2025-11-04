<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Factories;

use CubaDevOps\Flexi\Contracts\Interfaces\EventBusInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\ObjectBuilderInterface;
use CubaDevOps\Flexi\Infrastructure\Http\Router;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class RouterFactory
{
    private EventBusInterface $event_bus;
    private ObjectBuilderInterface $class_factory;
    private ResponseFactoryInterface $response_factory;
    private ContainerInterface $container;

    public function __construct(
        EventBusInterface $event_bus,
        ObjectBuilderInterface $class_factory,
        ResponseFactoryInterface $response_factory,
        ContainerInterface $container
    ) {
        $this->event_bus = $event_bus;
        $this->class_factory = $class_factory;
        $this->response_factory = $response_factory;
        $this->container = $container;
    }

    /**
     * @throws \JsonException
     */
    public function getInstance(
        string $routesFilePath
    ): Router {
        $router = new Router($this->event_bus, $this->class_factory, $this->response_factory, $this->container);
        $router->loadRoutesFile($routesFilePath);

        return $router;
    }
}
