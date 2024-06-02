<?php

namespace CubaDevOps\Flexi\Modules\Home\Infrastructure\Controllers;

use CubaDevOps\Flexi\Infrastructure\Classes\HttpHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthenticatedController extends HttpHandler
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->queue->isEmpty()) {
            return $this->getNextMiddleware()->process($request, $this);
        }

        $response = $this->createResponse();
        $response->getBody()->write('Authorized');

        return $response;
    }
}
