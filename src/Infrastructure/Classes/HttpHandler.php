<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Classes;

use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class HttpHandler implements RequestHandlerInterface
{
    /**
     * @var RequestFactoryInterface|ResponseFactoryInterface|ServerRequestFactoryInterface|StreamFactoryInterface|UploadedFileFactoryInterface|UriFactoryInterface
     */
    protected $response_factory;

    protected \SplQueue $queue;

    public function __construct()
    {
        $this->queue = new \SplQueue();
        $this->response_factory = new HttpFactory();
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

    protected function getNextMiddleware(): MiddlewareInterface
    {
        return $this->queue->dequeue();
    }

    protected function createResponse(int $code = 200, string $reasonPhrase = 'OK'): ResponseInterface
    {
        return $this->response_factory->createResponse($code, $reasonPhrase);
    }
}
