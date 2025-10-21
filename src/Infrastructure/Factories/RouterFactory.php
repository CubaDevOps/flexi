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

    private SessionStorageInterface $session;
    private EventBusInterface $event_bus;
    private ObjectBuilderInterface $class_factory;
    private ResponseFactoryInterface $response_factory;
    private ContainerInterface $container;

    public function __construct(
        SessionStorageInterface $session,
        EventBusInterface $event_bus,
        ObjectBuilderInterface $class_factory,
        ResponseFactoryInterface $response_factory,
        ContainerInterface $container
    ) {
        $this->session = $session;
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

        $router = new Router($this->session, $this->event_bus, $this->class_factory, $this->response_factory, $this->container);
        $router->loadRoutesFile($routesFilePath);

        return $router;
    }
}
