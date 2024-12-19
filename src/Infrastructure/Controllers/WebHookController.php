<?php

namespace CubaDevOps\Flexi\Infrastructure\Controllers;

use CubaDevOps\Flexi\Domain\Classes\Event;
use CubaDevOps\Flexi\Domain\Classes\EventBus;
use CubaDevOps\Flexi\Infrastructure\Classes\HttpHandler;
use Exception;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
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

        //Todo: move the logic to a Command to allow reuse it from terminal or other entry points, the controller should only handle the request and response

        try {
            $validatedData = $this->validate($request); //Todo: maybe we can use the static `fromArray` method from the Event class to create the Event object to ensure consistency on validation data across the application
        } catch (Exception $e) {
            return $this->createResponse(400, $e->getMessage());
        }

        $event = new Event(
            $validatedData['event_name'],
            $validatedData['trigger_date'], // Todo: fix wrong parameter, should be `fired_by` to indicate the source of the event (who fired it)
            $validatedData['data']
        );

        try {
            $this->eventBus->dispatch($event);
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface|\ReflectionException $e) {
            return $this->createResponse(400, $e->getMessage());
        }

        return $this->createResponse();
    }

    /**
     * @throws \JsonException
     */
    private function validate(ServerRequestInterface $request): array
    {
        $data = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

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
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }

        return [
            'event_name'    => $data['event_name'],
            'trigger_date'  => $data['trigger_date'],
            'data'          => $data['data'],
        ];
    }
}