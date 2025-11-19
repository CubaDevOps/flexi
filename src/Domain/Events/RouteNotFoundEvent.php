<?php

declare(strict_types=1);

namespace Flexi\Domain\Events;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Event dispatched when a requested route is not found.
 *
 * This event allows modules to handle 404 scenarios by providing
 * a custom response. If no listener handles the event, a default
 * 404 response will be returned.
 *
 * Event data structure:
 * - 'request' (ServerRequestInterface): The HTTP request object
 * - 'requested_path' (string): The path that was not found
 * - 'response' (ResponseInterface|null): Optional response set by listeners
 */
class RouteNotFoundEvent extends Event
{
    public function __construct(
        ServerRequestInterface $request,
        string $requestedPath,
        string $firedBy
    ) {
        parent::__construct(
            'core.routeNotFound',
            $firedBy,
            [
                'request' => $request,
                'requested_path' => $requestedPath,
                'response' => null,
            ]
        );
    }

    /**
     * Get the HTTP request object.
     * Internal method for Router usage.
     */
    public function getRequest(): ServerRequestInterface
    {
        $request = $this->get('request');
        assert($request instanceof ServerRequestInterface);
        return $request;
    }

    /**
     * Get the requested path that was not found.
     * Internal method for Router usage.
     */
    public function getRequestedPath(): string
    {
        $path = $this->get('requested_path');
        assert(is_string($path));
        return $path;
    }

    /**
     * Set a response for this event.
     * Internal method for Router usage.
     */
    public function setResponse(ResponseInterface $response): void
    {
        $this->set('response', $response);
        $this->stopPropagation();
    }

    /**
     * Check if a response has been set.
     * Internal method for Router usage.
     */
    public function hasResponse(): bool
    {
        return $this->has('response') && $this->get('response') !== null;
    }

    /**
     * Get the response if one was set.
     * Internal method for Router usage.
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->get('response');
    }
}
