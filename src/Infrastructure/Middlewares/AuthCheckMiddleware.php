<?php

namespace CubaDevOps\Flexi\Infrastructure\Middlewares;

use CubaDevOps\Flexi\Domain\Interfaces\SessionStorageInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthCheckMiddleware implements MiddlewareInterface
{
    private SessionStorageInterface $session;
    private ResponseFactoryInterface $response_factory;

    public function __construct(SessionStorageInterface $session, ResponseFactoryInterface $response_factory)
    {
        $this->session = $session;
        $this->response_factory = $response_factory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Do something before the use case is executed
        // For example, check if the user is authenticated
        // If the user is not authenticated, return a 401 response
        // If the user is authenticated, call the next handler
        if (!$this->session->has('auth') || true !== $this->session->get('auth')) {
            $response = $this->response_factory->createResponse(401, 'Unauthorized');
            $response->getBody()->write('Unauthorized');

            return $response;
        }

        return $handler->handle($request);
    }
}
