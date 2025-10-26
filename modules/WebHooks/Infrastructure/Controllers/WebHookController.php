<?php

namespace CubaDevOps\Flexi\Modules\WebHooks\Infrastructure\Controllers;

use CubaDevOps\Flexi\Domain\Events\Event;
use CubaDevOps\Flexi\Contracts\EventBusContract;
use CubaDevOps\Flexi\Infrastructure\Classes\HttpHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

class WebHookController extends HttpHandler
{
    private EventBusContract $event_bus;

    public function __construct(EventBusContract $event_bus)
    {
        parent::__construct();
        $this->event_bus = $event_bus;
    }

    protected function process(ServerRequestInterface $request): ResponseInterface
    {
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