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
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class EventBus implements EventBusInterface, EventDispatcherInterface
{
    use JsonFileReader;
    use GlobFileReader;

    private array $events = [];
    private ContainerInterface $container;
    private ClassFactory $class_factory;
    private int $dispatch_mode;

    public const DISPATCH_SYNC = 0;
    public const DISPATCH_ASYNC = 1;
    /**
     * @var mixed
     */
    private LoggerInterface $logger;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container, ClassFactory $class_factory, int $dispatch_mode)
    {
        $this->container = $container;
        $this->class_factory = $class_factory;
        $this->dispatch_mode = $dispatch_mode;
        $this->logger = $container->get('logger'); //Todo: inject dependency
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
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function execute(DTOInterface $dto): void
    {
        if ($dto instanceof EventInterface) {
            $this->dispatch($dto);
        }
    }

    /**
     * Dispatches an event to all relevant listeners.
     *
     * @param object $event The event object to dispatch.
     * @return object The event after processing by listeners.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function dispatch(object $event): object
    {
        $identifier = $event instanceof EventInterface ? $event->getName() : get_class($event);

        $listeners = array_merge($this->getListeners($identifier), $this->getListeners('*'));

        if (empty($listeners)) {
            return $event;
        }

        if (self::DISPATCH_ASYNC === $this->dispatch_mode) {
            $this->dispatchAsync($listeners, $event);
        } else {
            $this->notifyListeners($listeners, $event);
        }

        return $event;
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

    /**
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function notifyListeners(array $listeners, object $event): void
    {
        foreach ($listeners as $listener) {
            $this->handleListener($listener, $event);
        }

    }

    /**
     * @throws ContainerExceptionInterface
     * @throws \ReflectionException
     * @throws NotFoundExceptionInterface
     */
    private function handleListener(string $listener, object $event): void
    {
        if (!($event instanceof EventInterface) || $event->isPropagationStopped()) {
            return;
        }

        /** @var EventListenerInterface $listener_obj */
        $listener_obj = $this->class_factory->build($this->container, $listener);
        $listener_obj->handle($event);
    }

    /**
     * @return void
     */
    private function closeBuffers(): void
    {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();  // Flush all data to the client
        }

        // Close the standard file descriptors to prevent writing to the console
        try {
            fclose(STDIN);
            fclose(STDOUT);
            fclose(STDERR);
        } catch (\Exception $e) {
            $this->logger->warning("Error closing file descriptors: " . $e->getMessage(),[__CLASS__]);
        } finally {
            fclose(STDIN);
            fclose(STDOUT);
            fclose(STDERR);
        }
    }

    /**
     * @param array $listeners
     * @param object $event
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    private function dispatchAsync(array $listeners, object $event): void
    {
        $pid = pcntl_fork();

        if ($pid === -1) {
            $this->logger->error("Could not fork process");
            throw new \RuntimeException("Could not fork process");
        }

        if ($pid === 0) {
            $this->notifyListeners($listeners, $event);

            $this->closeBuffers(); // Close the standard file descriptors to prevent blank output on parent process

            exit(0);
        }
    }

    public function dispatchMode(): int
    {
        return $this->dispatch_mode;
    }
}
