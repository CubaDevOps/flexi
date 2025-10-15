<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Http;

use CubaDevOps\Flexi\Domain\Classes\Event;
use CubaDevOps\Flexi\Domain\Classes\ObjectCollection;
use CubaDevOps\Flexi\Domain\Classes\Route;
use CubaDevOps\Flexi\Domain\Interfaces\EventBusInterface;
use CubaDevOps\Flexi\Domain\Interfaces\ObjectBuilderInterface;
use CubaDevOps\Flexi\Domain\Interfaces\SessionStorageInterface;
use CubaDevOps\Flexi\Infrastructure\Utils\GlobFileReader;
use CubaDevOps\Flexi\Infrastructure\Utils\JsonFileReader;
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
    protected SessionStorageInterface $session;
    protected EventBusInterface $event_bus;
    protected ObjectBuilderInterface $class_factory;
    protected ResponseFactoryInterface $response_factory;
    protected ContainerInterface $container;

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
        $this->routes_indexed_by_name = new ObjectCollection(Route::class);
        $this->routes_indexed_by_path = new ObjectCollection(Route::class);
    }

    /**
     * @throws \JsonException
     */
    public function loadRoutesFile(string $routesFilePath): void
    {
        $routes = $this->readJsonFile($routesFilePath);

        foreach ($routes['routes'] as $defined_route) {
            if ($this->isGlob($defined_route)) {
                $this->loadGlobRoutes($defined_route['glob']);
                continue;
            }
            $this->addRoute(
                new Route(
                    $defined_route['name'],
                    $defined_route['path'],
                    $defined_route['controller'],
                    $defined_route['method'],
                    $defined_route['parameters'] ?? [],
                    $defined_route['middlewares'] ?? []
                )
            );
        }
    }

    protected function isGlob($definition): bool
    {
        return isset($definition['glob']);
    }

    /**
     * @throws \JsonException
     */
    public function loadGlobRoutes(string $glob_path): void
    {
        $routes_files = $this->readGlob($glob_path);
        foreach ($routes_files as $file) {
            $this->loadRoutesFile($file);
        }
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
        $event = new Event('core.onRequest', __CLASS__, ['request' => $request]);
        $this->event_bus->dispatch($event);
        $this->assertThatRouteCollectionIsNotEmpty();

        if (!$this->routes_indexed_by_path->offsetExists($request_path)) {
            return $this->redirectToNotFound($request, $request_path);
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

    public function redirectToNotFound(
        ServerRequestInterface $request,
        string $previous_route
    ): ResponseInterface {
        $this->session->set('previous_route', $previous_route);
        $not_found_route = $this->getByName('404');
        $event = new Event('core.redirect', __CLASS__, [
            'from' => $previous_route,
            'to' => $not_found_route->getPath(),
        ]);
        $this->event_bus->dispatch($event);
        $response = $this->response_factory->createResponse();

        return $response
            ->withHeader(
                'Location',
                $not_found_route->getAbsoluteUrl(self::getUrlBase())
            )
            ->withStatus(301);
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

        $event = new Event('core.onResponse', __CLASS__, ['response' => $response]);
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

        return $route->hasMiddlewares() ? $route->throughMiddlewares(
            $this->container,
            $this->class_factory,
            $handler
        )->handle($request) : $handler->handle($request);
    }
}
