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
        try {
            $validatedData = $this->validate($request);
        } catch (Exception $e) {
            return $this->createResponse(400, $e->getMessage());
        }

        $event = new Event(
            $validatedData['event_name'],
            $validatedData['trigger_date'],
            json_decode($validatedData['data'], true)
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
        $errors = [];

        $eventName = $request->getAttribute('event_name');
        if (empty($eventName) || !is_string($eventName)) {
            $errors['event_name'] = 'The event_name field is required and must be a string.';
        }

        $triggerDate = $request->getAttribute('trigger_date');
        if (empty($triggerDate) || !strtotime($triggerDate)) {
            $errors['trigger_date'] = 'The trigger_date field is required and must be a valid date.';
        }

        $data = $request->getAttribute('data');
        if (empty($data) || is_null(json_decode($data, true))) {
            $errors['data'] = 'The data field is required and must be a valid JSON string.';
        }

        if (!empty($errors)) {
            throw new InvalidArgumentException(json_encode($errors));
        }

        return [
            'event_name'    => $eventName,
            'trigger_date'  => $triggerDate,
            'data'          => json_decode($data, true),
        ];
    }
}