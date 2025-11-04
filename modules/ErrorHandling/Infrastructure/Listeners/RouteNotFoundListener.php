<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\ErrorHandling\Infrastructure\Listeners;

use CubaDevOps\Flexi\Contracts\Interfaces\EventInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\EventListenerInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\SessionStorageInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Listener that handles route not found events by redirecting to the 404 page.
 *
 * This listener:
 * - Stores the previous route in session for display in the 404 page
 * - Redirects the user to the /not-found route (404 page)
 *
 * This listener works with events that have the following data structure:
 * - 'request' (ServerRequestInterface): The HTTP request object
 * - 'requested_path' (string): The path that was not found
 */
class RouteNotFoundListener implements EventListenerInterface
{
    private SessionStorageInterface $session;
    private ResponseFactoryInterface $response_factory;

    public function __construct(
        SessionStorageInterface $session,
        ResponseFactoryInterface $response_factory
    ) {
        $this->session = $session;
        $this->response_factory = $response_factory;
    }

    public function handleEvent(EventInterface $event)
    {
        // Only handle core.routeNotFound events
        if ($event->getName() !== 'core.routeNotFound') {
            return;
        }

        // Get event data using the EventInterface contract
        $requestedPath = $event->get('requested_path');
        $request = $event->get('request');

        // Validate that we have the required data
        if (!is_string($requestedPath) || !$request instanceof ServerRequestInterface) {
            return;
        }

        // Store the requested path in session so the 404 controller can display it
        $this->session->set('previous_route', $requestedPath);

        // Build the URL base from the request (no dependency on Router class)
        $uri = $request->getUri();
        $urlBase = $uri->getScheme() . '://' . $uri->getHost();
        if ($uri->getPort() && !in_array($uri->getPort(), [80, 443])) {
            $urlBase .= ':' . $uri->getPort();
        }

        // Create redirect response to the 404 page
        $response = $this->response_factory->createResponse(302);
        $notFoundUrl = $urlBase . '/not-found';
        $response = $response->withHeader('Location', $notFoundUrl);

        // Set the response back to the event using the EventInterface contract
        $event->set('response', $response);
        $event->stopPropagation();
    }

    public function handle($dto)
    {
        if ($dto instanceof EventInterface) {
            return $this->handleEvent($dto);
        }
    }
}
