<?php

declare(strict_types=1);

namespace Flexi\Test\TestData\TestDoubles;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Dummy PSR-7 Response implementation for testing.
 * Minimal implementation of ResponseInterface without external dependencies.
 * Only implements the methods needed for testing HTTP responses.
 */
class DummyResponse implements ResponseInterface
{
    private int $statusCode = 200;
    private string $reasonPhrase = 'OK';
    private array $headers = [];
    private DummyStream $body;
    private string $protocolVersion = '1.1';

    public function __construct(int $code = 200, array $headers = [], ?StreamInterface $body = null, string $version = '1.1', string $reason = '')
    {
        $this->statusCode = $code;
        $this->reasonPhrase = $reason ?: $this->getDefaultReasonPhrase($code);
        $this->headers = $headers;
        $this->body = $body ?? new DummyStream();
        $this->protocolVersion = $version;
    }

    private function getDefaultReasonPhrase(int $code): string
    {
        $phrases = [
            200 => 'OK',
            201 => 'Created',
            204 => 'No Content',
            301 => 'Moved Permanently',
            302 => 'Found',
            304 => 'Not Modified',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
        ];

        return $phrases[$code] ?? '';
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion($version): ResponseInterface
    {
        $clone = clone $this;
        $clone->protocolVersion = (string) $version;
        return $clone;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader($name): bool
    {
        $normalized = strtolower($name);
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $normalized) {
                return true;
            }
        }
        return false;
    }

    public function getHeader($name): array
    {
        $normalized = strtolower($name);
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $normalized) {
                return (array) $value;
            }
        }
        return [];
    }

    public function getHeaderLine($name): string
    {
        $values = $this->getHeader($name);
        return implode(', ', $values);
    }

    public function withHeader($name, $value): ResponseInterface
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    public function withAddedHeader($name, $value): ResponseInterface
    {
        $clone = clone $this;
        if (isset($clone->headers[$name])) {
            $clone->headers[$name] = array_merge((array) $clone->headers[$name], (array) $value);
        } else {
            $clone->headers[$name] = $value;
        }
        return $clone;
    }

    public function withoutHeader($name): ResponseInterface
    {
        $clone = clone $this;
        $normalized = strtolower($name);
        foreach (array_keys($clone->headers) as $key) {
            if (strtolower($key) === $normalized) {
                unset($clone->headers[$key]);
            }
        }
        return $clone;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): ResponseInterface
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus($code, $reasonPhrase = ''): ResponseInterface
    {
        $clone = clone $this;
        $clone->statusCode = (int) $code;
        $clone->reasonPhrase = $reasonPhrase ?: $this->getDefaultReasonPhrase((int) $code);
        return $clone;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }
}
