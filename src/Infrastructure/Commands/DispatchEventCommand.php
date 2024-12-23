<?php

namespace CubaDevOps\Flexi\Infrastructure\Commands;

use CubaDevOps\Flexi\Domain\Classes\Event;
use CubaDevOps\Flexi\Domain\Classes\EventBus;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;

class DispatchEventCommand
{
    private EventBus $eventBus;

    public function __construct(EventBus $eventBus)
    {
        $this->eventBus = $eventBus;
    }

    /**
     * @param array $data
     * @return void
     * @throws \JsonException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function execute(array $data): void
    {
        $this->validate($data);

        $event = Event::fromArray($data);

        $this->eventBus->dispatch($event);
    }

    /**
     * @throws \JsonException
     */
    private function validate(array $data): void
    {
        if (empty($data['event']) || !is_string($data['event'])) {
            $errors['event'] = 'The event field is required and must be a string.';
        }

        if (empty($data['fired_by']) || !is_string($data['fired_by'])) {
            $errors['fired_by'] = 'The fired_by field is required and must be a string.';
        }

        if (empty($data['data'])) {
            $errors['data'] = 'The data field is required and must be a valid JSON string.';
        }

        if (!empty($errors)) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }
    }
}
