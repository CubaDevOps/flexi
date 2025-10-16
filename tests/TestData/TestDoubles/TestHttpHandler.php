<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\TestData\TestDoubles;

use CubaDevOps\Flexi\Infrastructure\Classes\HttpHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TestHttpHandler extends HttpHandler
{
    private ?ResponseInterface $mockResponse = null;

    public function setMockResponse(ResponseInterface $response): void
    {
        $this->mockResponse = $response;
    }

    protected function process(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->mockResponse !== null) {
            return $this->mockResponse;
        }

        return $this->createResponse(200, 'OK');
    }
}
