<?php

declare(strict_types=1);

namespace Flexi\Test\TestData\TestDoubles;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Dummy ResponseFactory for testing.
 * Creates PSR-7 Response objects without external dependencies.
 * Uses DummyResponse and DummyStream for pure PSR standard implementations.
 * Keeps the test doubles independent from specific HTTP libraries.
 */
class DummyResponseFactory implements ResponseFactoryInterface
{
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new DummyResponse($code, [], null, '1.1', $reasonPhrase);
    }
}
