<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Home\Infrastructure\Controllers;

use CubaDevOps\Flexi\Infrastructure\Classes\HttpHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthenticatedController extends HttpHandler
{
    protected function process(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->createResponse();
        $response->getBody()->write('Authorized');

        return $response;
    }
}
