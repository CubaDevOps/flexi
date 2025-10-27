<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Auth\Infrastructure\Controllers;

use CubaDevOps\Flexi\Contracts\Classes\HttpHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthenticatedController extends HttpHandler
{
    public function __construct(ResponseFactoryInterface $response_factory)
    {
        parent::__construct($response_factory);
    }

    protected function process(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->createResponse();
        $response->getBody()->write('Authorized');

        return $response;
    }
}
