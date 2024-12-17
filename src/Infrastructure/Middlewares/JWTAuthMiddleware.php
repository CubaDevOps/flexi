<?php

namespace CubaDevOps\Flexi\Infrastructure\Middlewares;

use CubaDevOps\Flexi\Infrastructure\Classes\Configuration;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JWTAuthMiddleware implements MiddlewareInterface
{
    private Configuration $configuration;

    private ResponseFactoryInterface $response_factory;

    public function __construct(
        Configuration $configuration,
        ResponseFactoryInterface $response_factory
    ) {
        $this->configuration = $configuration;
        $this->response_factory = $response_factory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $jwt = $matches[1];
            $key = $this->configuration->get('webhook_secret');
            try {
                // Decode the JWT
                $decoded = JWT::decode($jwt, new Key($key, 'HS256'));

                // You can attach user info to the request if needed
                // $request = $request->withAttribute('user', $decoded);
            } catch (\Exception $e) {
                return $this->response_factory->createResponse(401, 'Unauthorized');
            }
        } else {
            return $this->response_factory->createResponse(401, 'Authorization header not found');
        }

        return $handler->handle($request);
    }
}