<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Auth\Infrastructure\Middlewares;

use CubaDevOps\Flexi\Contracts\Interfaces\SecretProviderInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JWTAuthMiddleware implements MiddlewareInterface
{
    private SecretProviderInterface $secret_provider;

    private ResponseFactoryInterface $response_factory;

    public function __construct(
        SecretProviderInterface $secret_provider,
        ResponseFactoryInterface $response_factory
    ) {
        $this->secret_provider = $secret_provider;
        $this->response_factory = $response_factory;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->response_factory->createResponse(401, 'Authorization header not found')->withHeader(
                'WWW-Authenticate',
                'Bearer'
            );
        }

        $jwt = $matches[1];
        $key = $this->secret_provider->getSecret();
        try {
            $payload = JWT::decode($jwt, new Key($key, 'HS256'));
            // attach payload to the request
            $request = $request->withAttribute('payload', $payload);
        } catch (\LogicException|\UnexpectedValueException $e) {
            return $this->response_factory->createResponse(401, $e->getMessage());
        }

        return $handler->handle($request);
    }
}
