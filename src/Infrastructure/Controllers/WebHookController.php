<?php

namespace CubaDevOps\Flexi\Infrastructure\Controllers;

use CubaDevOps\Flexi\Domain\Classes\Event;
use CubaDevOps\Flexi\Domain\Interfaces\EventBusInterface;
use CubaDevOps\Flexi\Infrastructure\Classes\HttpHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

class WebHookController extends HttpHandler
{
    private EventBusInterface $event_bus;

    public function __construct(EventBusInterface $event_bus)
    {
        parent::__construct();
        $this->event_bus = $event_bus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->queue->isEmpty()) {
            return $this->getNextMiddleware()->process($request, $this);
        }

        try {
            /** @var StdClass $payload */
            $payload = $request->getAttribute('payload');
            $this->event_bus->dispatch(new Event($payload->event, $payload->fired_by, (array)($payload->data ?? null)));
        } catch (\Exception $e) {
            return $this->createResponse(400, $e->getMessage());
        }

        return $this->createResponse();
    }
}