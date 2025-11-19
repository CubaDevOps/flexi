<?php

declare(strict_types=1);

namespace Flexi\Test\TestData\TestDoubles;

use Flexi\Contracts\Classes\HttpHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TestHttpHandler extends HttpHandler
{
    private ?ResponseInterface $mockResponse = null;

    public function __construct(?ResponseFactoryInterface $response_factory = null)
    {
        parent::__construct($response_factory ?? new DummyResponseFactory());
    }

    public function setMockResponse(ResponseInterface $response): void
    {
        $this->mockResponse = $response;
    }

    protected function process(ServerRequestInterface $request): ResponseInterface
    {
        if (null !== $this->mockResponse) {
            return $this->mockResponse;
        }

        return $this->createResponse(200, 'OK');
    }
}
