<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Classes;

use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
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

    /**
     * Template Method: Gestiona automáticamente la cadena de middlewares
     * y delega la lógica específica al método abstracto process()
     *
     * Este método es final para evitar que los controladores lo sobrescriban
     * y garantizar que la cadena de middlewares siempre se ejecute correctamente.
     *
     * @param ServerRequestInterface $request La petición HTTP
     * @return ResponseInterface La respuesta HTTP
     */
    final public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->queue->isEmpty()) {
            return $this->getNextMiddleware()->process($request, $this);
        }

        return $this->process($request);
    }

    /**
     * Método abstracto que cada controlador debe implementar con su lógica específica.
     *
     * Este método se invoca automáticamente después de que todos los middlewares
     * hayan sido procesados.
     *
     * @param ServerRequestInterface $request La petición HTTP (potencialmente modificada por middlewares)
     * @return ResponseInterface La respuesta HTTP
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
