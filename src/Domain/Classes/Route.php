<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Classes;

use CubaDevOps\Flexi\Domain\Utils\ClassFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Route
{
    public const METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD', 'TRACE', 'CONNECT'];
    private string $path;
    private string $controller;
    /**
     * @var string GET|POST|PUT|PATCH|DELETE|OPTIONS|HEAD|TRACE|CONNECT
     */
    private string $method;
    private array $parameters;
    private string $name;
    private array $middlewares;

    public function __construct(
        string $name,
        string $path,
        string $controller,
        string $method = 'GET',
        array $parameters = [],
        array $middlewares = []
    ) {
        $this->validateMethod($method);
        $this->name = $name;
        $this->path = $path;
        $this->controller = $controller;
        $this->method = $method;
        $this->parameters = $parameters;
        $this->middlewares = $middlewares;
    }

    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * @return string GET|POST|PUT|PATCH|DELETE|OPTIONS|HEAD|TRACE|CONNECT
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getAbsoluteUrl(string $base_url): string
    {
        $this->assertBaseURL($base_url);

        return $base_url.$this->path;
    }

    private function assertBaseURL(string $base_url): void
    {
        if (
            empty($base_url)
            || false === strpos($base_url, $_SERVER['REQUEST_SCHEME'])
        ) {
            throw new \InvalidArgumentException('Base URL is not valid');
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function hasParameters(): bool
    {
        return !empty($this->parameters);
    }

    private function validateMethod(string $method): void
    {
        if (!in_array($method, self::METHODS, true)) {
            throw new \InvalidArgumentException("Invalid method: $method");
        }
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws \ReflectionException
     * @throws NotFoundExceptionInterface
     */
    public function throughMiddlewares(ContainerInterface $container, ClassFactory $factory, RequestHandlerInterface $handler): RequestHandlerInterface
    {
        if ($this->hasMiddlewares()) {
            $middlewares = [];
            foreach ($this->getMiddlewares() as $middleware) {
                $middlewares[] = $factory->build($container, $middleware);
            }
            $handler->setMiddlewares($middlewares);
        }

        return $handler;
    }

    public function hasMiddlewares(): bool
    {
        return !empty($this->middlewares);
    }
}
