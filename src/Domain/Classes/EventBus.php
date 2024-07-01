<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Classes;

use CubaDevOps\Flexi\Domain\DTO\NotFoundCliCommand;
use CubaDevOps\Flexi\Domain\Interfaces\DTOInterface;
use CubaDevOps\Flexi\Domain\Interfaces\EventBusInterface;
use CubaDevOps\Flexi\Domain\Interfaces\EventInterface;
use CubaDevOps\Flexi\Domain\Interfaces\EventListenerInterface;
use CubaDevOps\Flexi\Domain\Utils\ClassFactory;
use CubaDevOps\Flexi\Domain\Utils\GlobFileReader;
use CubaDevOps\Flexi\Domain\Utils\JsonFileReader;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class EventBus implements EventBusInterface
{
    use JsonFileReader;
    use GlobFileReader;

    private array $events = [];
    private ContainerInterface $container;
    private ClassFactory $class_factory;

    public function __construct(ContainerInterface $container, ClassFactory $class_factory)
    {
        $this->container = $container;
        $this->class_factory = $class_factory;
    }

    /**
     * @throws \JsonException
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function loadHandlersFromJsonFile(string $file): void
    {
        $events = $this->readJsonFile($file);

        foreach ($events as $event_entry) {
            if ($this->isGlob($event_entry)) {
                $this->loadGlobListeners($event_entry);
                continue;
            }
            $this->buildDefinition($event_entry['event'], $event_entry['listeners']);
        }
    }

    private function isGlob(array $listener): bool
    {
        return isset($listener['glob']);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws \JsonException
     */
    public function loadGlobListeners(array $listener): void
    {
        $files = $this->readGlob($listener['glob']);
        foreach ($files as $file) {
            $this->loadHandlersFromJsonFile($file);
        }
    }

    public function buildDefinition(string $event, array $listeners): void
    {
        foreach ($listeners as $listener) {
            $this->register($event, $listener);
        }
    }

    public function register(
        string $identifier,
        string $handler
    ): void {
        $this->events[$identifier][] = $handler;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function execute(DTOInterface $dto): void
    {
        if ($dto instanceof EventInterface) {
            $this->notify($dto);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function notify(EventInterface $dto): void
    {
        $identifier = $dto->getName();
        if (isset($this->events[$identifier])) {
            $this->notifyListeners($this->events[$identifier], $dto);
        }
        $this->notifyListenersOffAllEvents($dto);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    private function notifyListeners(array $listeners, EventInterface $dto): void
    {
        foreach ($listeners as $listener) {
            /** @var EventListenerInterface $handler_obj */
            $listener_obj = $this->class_factory->build(
                $this->container,
                $listener
            );
            $listener_obj->handle($dto);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws \ReflectionException
     * @throws NotFoundExceptionInterface
     */
    private function notifyListenersOffAllEvents(EventInterface $dto): void
    {
        if (empty($this->events['*'])) {
            return;
        }

        $this->notifyListeners($this->events['*'], $dto);
    }

    public function hasHandler(string $identifier): bool
    {
        return isset($this->events[$identifier]);
    }

    /**
     * @throws \JsonException
     */
    public function getHandler(string $identifier): string
    {
        return json_encode($this->getListeners($identifier), JSON_THROW_ON_ERROR);
    }

    public function getListeners(string $event): array
    {
        return $this->events[$event] ?? [];
    }

    public function getDtoClassFromAlias(string $id): string
    {
        return NotFoundCliCommand::class;
    }
}
