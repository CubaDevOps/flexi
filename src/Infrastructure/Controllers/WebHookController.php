<?php

namespace CubaDevOps\Flexi\Infrastructure\Controllers;

use CubaDevOps\Flexi\Domain\Classes\Event;
use CubaDevOps\Flexi\Domain\Classes\EventBus;
use CubaDevOps\Flexi\Infrastructure\Classes\HttpHandler;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class WebHookController extends HttpHandler
{
    private EventBus $eventBus;

    public function __construct(EventBus $eventBus)
    {
        parent::__construct();
        $this->eventBus = $eventBus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->queue->isEmpty()) {
            return $this->getNextMiddleware()->process($request, $this);
        }

        try {
            $validatedData = $this->validate($request);
        } catch (Exception $e) {
            return $this->createResponse(400, $e->getMessage());
        }

        $event = new Event(
            $validatedData['event_name'],
            $validatedData['trigger_date'],
            $validatedData['data']
        );

        try {
            $this->eventBus->dispatch($event);
        } catch (Exception $e) {
            return $this->createResponse(400, $e->getMessage());
        }

        return $this->createResponse();
    }

    private function validate(ServerRequestInterface $request): array
    {
        $data = json_decode($request->getBody()->getContents(), true);

        if (empty($data['event_name']) || !is_string($data['event_name'])) {
            $errors['event_name'] = 'The event_name field is required and must be a string.';
        }

        if (empty($data['trigger_date']) || !strtotime($data['trigger_date'])) {
            $errors['trigger_date'] = 'The trigger_date field is required and must be a valid date.';
        }

        if (empty($data['data'])) {
            $errors['data'] = 'The data field is required and must be a valid JSON string.';
        }

        if (!empty($errors)) {
            throw new InvalidArgumentException(json_encode($errors));
        }

        return [
            'event_name'    => $data['event_name'],
            'trigger_date'  => $data['trigger_date'],
            'data'          => $data['data'],
        ];
    }
}