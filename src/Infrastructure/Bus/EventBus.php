<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Bus;

use CubaDevOps\Flexi\Contracts\ConfigurationRepositoryContract;
use CubaDevOps\Flexi\Contracts\DTOContract;
use CubaDevOps\Flexi\Contracts\EventBusContract;
use CubaDevOps\Flexi\Contracts\EventContract;
use CubaDevOps\Flexi\Contracts\EventListenerContract;
use CubaDevOps\Flexi\Contracts\ObjectBuilderContract;
use CubaDevOps\Flexi\Domain\DTO\NotFoundCliCommand;
use CubaDevOps\Flexi\Infrastructure\Utils\GlobFileReader;
use CubaDevOps\Flexi\Infrastructure\Utils\JsonFileReader;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

class EventBus implements EventBusContract
{
    use JsonFileReader;
    use GlobFileReader;

    private array $events = [];
    private ContainerInterface $container;
    private ObjectBuilderContract $class_factory;
    private LoggerInterface $logger;
    private ConfigurationRepositoryContract $configuration;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container, ObjectBuilderContract $class_factory, LoggerInterface $logger, ConfigurationRepositoryContract $configuration_repository)
    {
        $this->container = $container;
        $this->class_factory = $class_factory;
        $this->logger = $logger;
        $this->configuration = $configuration_repository;
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
    public function execute(DTOContract $dto): void
    {
        if ($dto instanceof EventContract) {
            $this->dispatch($dto);
        }
    }

    /**
     * Dispatches an event to all relevant listeners.
     *
     * @param object $event the event object to dispatch
     *
     * @return object the event after processing by listeners
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function dispatch(object $event): object
    {
        $identifier = $event instanceof EventContract ? $event->getName() : get_class($event);

        $listeners = array_merge($this->getListeners($identifier), $this->getListeners('*'));

        if (empty($listeners)) {
            return $event;
        }

        if ($this->asyncMode()) {
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
        if (!($event instanceof EventContract) || $event->isPropagationStopped()) {
            return;
        }

        /** @var EventListenerContract $listener_obj */
        $listener_obj = $this->class_factory->build($this->container, $listener);
        $listener_obj->handle($event);
    }

    private function closeBuffers(): void
    {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();  // Flush all data to the client
        }

        // Close the standard file descriptors to prevent writing to the console
        try {
            if (defined('STDIN') && is_resource(STDIN)) {
                fclose(STDIN);
            }

            if (defined('STDOUT') && is_resource(STDOUT)) {
                fclose(STDOUT);
            }

            if (defined('STDERR') && is_resource(STDERR)) {
                fclose(STDERR);
            }
        } catch (\Exception $e) {
            $this->logger->warning('Error closing file descriptors: '.$e->getMessage(), [__CLASS__]);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    private function dispatchAsync(array $listeners, object $event): void
    {
        $pid = pcntl_fork();

        if (-1 === $pid) {
            $this->logger->error('Could not fork process');
            throw new \RuntimeException('Could not fork process');
        }

        if (0 === $pid) {
            $this->notifyListeners($listeners, $event);

            $this->closeBuffers(); // Close the standard file descriptors to prevent blank output on parent process

            exit(0);
        }
        pcntl_waitpid($pid, $status, WNOHANG);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function asyncMode(): bool
    {
        return PHP_SAPI === 'cli' && ($this->configuration->has('dispatch_mode') && (int) $this->configuration->get('dispatch_mode'));
    }
}
