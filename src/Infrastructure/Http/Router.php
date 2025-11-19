<?php

declare(strict_types=1);

namespace Flexi\Infrastructure\Http;

use Flexi\Contracts\Classes\ObjectCollection;
use Flexi\Contracts\Classes\Traits\CacheKeyGeneratorTrait;
use Flexi\Contracts\Classes\Traits\GlobFileReader;
use Flexi\Contracts\Classes\Traits\JsonFileReader;
use Flexi\Contracts\Interfaces\CacheInterface;
use Flexi\Contracts\Interfaces\CollectionInterface;
use Flexi\Contracts\Interfaces\EventBusInterface;
use Flexi\Contracts\Interfaces\ObjectBuilderInterface;
use Flexi\Domain\Events\Event;
use Flexi\Domain\Events\RouteNotFoundEvent;
use Flexi\Contracts\Classes\HttpHandler;
use Flexi\Infrastructure\Http\Route;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Router
{
    use JsonFileReader;
    use GlobFileReader;

    protected ObjectCollection $routes_indexed_by_name;
    protected ObjectCollection $routes_indexed_by_path;
    protected EventBusInterface $event_bus;
    protected ObjectBuilderInterface $class_factory;
    protected ResponseFactoryInterface $response_factory;
    protected ContainerInterface $container;

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
        $this->routes_indexed_by_name = new ObjectCollection(Route::class);
        $this->routes_indexed_by_path = new ObjectCollection(Route::class);
    }


    /**
     * @return static
     */
    public function addRoute(Route $route): self
    {
        $this->routes_indexed_by_name->add($route, $route->getName());
        $this->routes_indexed_by_path->add($route, $route->getPath());

        return $this;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws \JsonException
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function dispatch(
        ServerRequestInterface $request
    ): ResponseInterface {
        $request_path = $request->getUri()->getPath();
        $event = new Event('core.onRequest', __CLASS__, ['request' => $request->getUri()->__toString()]);
        $this->event_bus->dispatch($event);
        $this->assertThatRouteCollectionIsNotEmpty();

        if (!$this->routes_indexed_by_path->offsetExists($request_path)) {
            return $this->handleNotFound($request, $request_path);
        }
        /** @var Route $route */
        $route = $this->routes_indexed_by_path->get($request_path);

        return $this->processRequest($request, $route);
    }

    private function assertThatRouteCollectionIsNotEmpty(): void
    {
        if (!$this->routes_indexed_by_path->count()) {
            throw new \RuntimeException('Define at least one route');
        }
    }

    public function handleNotFound(
        ServerRequestInterface $request,
        string $requested_path
    ): ResponseInterface {
        $event = new RouteNotFoundEvent($request, $requested_path, __CLASS__);
        $this->event_bus->dispatch($event);

        // If a listener handled the event and provided a response, use it
        if ($event->hasResponse()) {
            return $event->getResponse();
        }

        // Otherwise, return a simple 404 response
        $response = $this->response_factory->createResponse(404);
        $response->getBody()->write('404 - Not Found');

        return $response;
    }

    /**
     * @throws \RuntimeException
     */
    public function getByName(string $route_name): Route
    {
        $this->assertRouteNameExist($route_name);

        return $this->routes_indexed_by_name->get($route_name);
    }

    protected function assertRouteNameExist(string $route_name): void
    {
        if (!$this->routes_indexed_by_name->offsetExists($route_name)) {
            throw new \RuntimeException("Route '{$route_name}' not found");
        }
    }

    public static function getUrlBase(): string
    {
        return $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws \JsonException
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function processRequest(
        ServerRequestInterface $request,
        Route $route
    ): ResponseInterface {
        $this->assertMethodIsAllowedForRoute($request, $route);
        $this->checkRequiredParamsWereSend($route);

        $response = $this->executeStack($route, $request);

        $event = new Event('core.onResponse', __CLASS__, ['response' => ['status' => $response->getStatusCode(), 'reason' => $response->getReasonPhrase()]]);
        $this->event_bus->dispatch($event);

        return $response;
    }

    protected function assertMethodIsAllowedForRoute(
        ServerRequestInterface $request,
        Route $route
    ): void {
        if ($request->getMethod() !== $route->getMethod()) {
            throw new \RuntimeException("Method {$request->getMethod()} is not allowed for this route");
        }
    }

    protected function checkRequiredParamsWereSend(Route $route): void
    {
        if ($route->hasParameters()) {
            foreach ($route->getParameters() as $parameter) {
                if (
                    !empty($parameter['required'])
                    && (!isset($_GET[$parameter['name']])
                        && !isset($_POST[$parameter['name']]))
                ) {
                    throw new \RuntimeException("Parameter '{$parameter['name']}' is required");
                }
            }
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws \JsonException
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    private function executeStack(
        Route $route,
        ServerRequestInterface $request
    ): ResponseInterface {
        /** @var RequestHandlerInterface $handler */
        $handler = $this->class_factory->build(
            $this->container,
            $route->getController()
        );

        // Configure middlewares if the route has them
        if ($route->hasMiddlewares()) {
            $handler = $this->configureMiddlewares($handler, $route);
        }

        return $handler->handle($request);
    }

    /**
     * Configures middlewares in the handler if it's an HttpHandler instance.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    private function configureMiddlewares(
        RequestHandlerInterface $handler,
        Route $route
    ): RequestHandlerInterface {
        if (!$handler instanceof HttpHandler) {
            return $handler;
        }

        $middlewares = [];
        foreach ($route->getMiddlewares() as $middlewareClass) {
            $middlewares[] = $this->class_factory->build($this->container, $middlewareClass);
        }

        $handler->setMiddlewares($middlewares);

        return $handler;
    }
}
