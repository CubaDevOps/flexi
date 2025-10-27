<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\WebHooks\Infrastructure\Controllers;

use CubaDevOps\Flexi\Contracts\Interfaces\EventBusInterface;
use CubaDevOps\Flexi\Domain\Events\Event;
use CubaDevOps\Flexi\Infrastructure\Classes\HttpHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class WebHookController extends HttpHandler
{
    private EventBusInterface $event_bus;

    public function __construct(EventBusInterface $event_bus)
    {
        parent::__construct();
        $this->event_bus = $event_bus;
    }

    protected function process(ServerRequestInterface $request): ResponseInterface
    {
        try {
            /** @var \stdClass $payload */
            $payload = $request->getAttribute('payload');
            $this->event_bus->dispatch(new Event($payload->event, $payload->fired_by, (array) ($payload->data ?? null)));
        } catch (\Exception $e) {
            return $this->createResponse(400, $e->getMessage());
        }

        return $this->createResponse();
    }
}
