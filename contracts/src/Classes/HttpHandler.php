<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Classes;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class HttpHandler implements RequestHandlerInterface
{
    protected ResponseFactoryInterface $response_factory;

    protected \SplQueue $queue;

    /**
     * HttpHandler constructor.
     *
     * @param ResponseFactoryInterface $response_factory Factory for creating PSR-7 responses
     */
    public function __construct(ResponseFactoryInterface $response_factory)
    {
        $this->queue = new \SplQueue();
        $this->response_factory = $response_factory;
    }

    public function setMiddlewares(array $middlewares): void
    {
        foreach ($middlewares as $middleware) {
            $this->addMiddleware($middleware);
        }
    }

    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->queue->enqueue($middleware);

        return $this;
    }

    /**
     * Template Method: Automatically manages the middleware chain
     * and delegates specific logic to the abstract process() method.
     *
     * This method is final to prevent controllers from overriding it
     * and ensure that the middleware chain is always executed correctly.
     *
     * @param ServerRequestInterface $request The HTTP request
     *
     * @return ResponseInterface The HTTP response
     */
    final public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->queue->isEmpty()) {
            return $this->getNextMiddleware()->process($request, $this);
        }

        return $this->process($request);
    }

    /**
     * Abstract method that each controller must implement with its specific logic.
     *
     * This method is automatically invoked after all middlewares
     * have been processed.
     *
     * @param ServerRequestInterface $request The HTTP request (potentially modified by middlewares)
     *
     * @return ResponseInterface The HTTP response
     */
    abstract protected function process(ServerRequestInterface $request): ResponseInterface;

    protected function getNextMiddleware(): MiddlewareInterface
    {
        return $this->queue->dequeue();
    }

    protected function createResponse(int $code = 200, string $reasonPhrase = 'OK'): ResponseInterface
    {
        return $this->response_factory->createResponse($code, $reasonPhrase);
    }
}
