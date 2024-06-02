<?php

namespace CubaDevOps\Flexi\Domain\Classes;

use CubaDevOps\Flexi\Domain\DTO\CliDTO;
use CubaDevOps\Flexi\Domain\Interfaces\BusInterface;
use CubaDevOps\Flexi\Domain\Interfaces\DTOInterface;
use CubaDevOps\Flexi\Domain\Interfaces\EventBusInterface;
use CubaDevOps\Flexi\Domain\Interfaces\HandlerInterface;
use CubaDevOps\Flexi\Domain\Interfaces\MessageInterface;
use CubaDevOps\Flexi\Domain\Utils\ClassFactory;
use CubaDevOps\Flexi\Domain\Utils\GlobFileReader;
use CubaDevOps\Flexi\Domain\Utils\JsonFileReader;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;

class CommandBus implements BusInterface
{
    use JsonFileReader;
    use GlobFileReader;

    private array $commands = [];
    private array $aliases = [];
    private ContainerInterface $container;
    private EventBusInterface $event_bus;
    private ClassFactory $class_factory;

    /**
     * @param EventBus $event_bus
     */
    public function __construct(ContainerInterface $container, EventBusInterface $event_bus, ClassFactory $class_factory)
    {
        $this->container = $container;
        $this->event_bus = $event_bus;
        $this->class_factory = $class_factory;
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function loadHandlersFromJsonFile(string $file): void
    {
        $handlers = $this->readJsonFile($file);

        foreach ($handlers['handlers'] as $entry) {
            if ($this->isGlob($entry)) {
                $this->loadGlobHandlers($entry['glob']);
                continue;
            }
            $this->addEntry($entry);
        }
    }

    private function isGlob(array $definition): bool
    {
        return isset($definition['glob']);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws JsonException
     */
    private function loadGlobHandlers(string $glob_path): void
    {
        $handlers = $this->readGlob($glob_path);
        foreach ($handlers as $handler) {
            $this->loadHandlersFromJsonFile($handler);
        }
    }

    private function addEntry(array $entry): void
    {
        $this->register($entry['id'], $entry['handler']);
        if (isset($entry['cli_alias'])) {
            $this->registerCliAlias($entry['cli_alias'], $entry['handler']);
        }
    }

    public function register(
        string $identifier,
        string $handler
    ): void {
        $this->commands[$identifier] = $handler;
    }

    public function registerCliAlias(string $alias, string $handler): void
    {
        $this->aliases[$alias] = $handler;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public function execute(DTOInterface $dto): MessageInterface
    {
        $identifier = get_class($dto);

        $handler = $this->getHandler($identifier);

        /** @var HandlerInterface $handler_obj */
        $handler_obj = $this->class_factory->build($this->container, $handler);

        $event_before = new Event('core.command.before_execute',__CLASS__, [
            'command' => $identifier,
        ]);
        $this->event_bus->notify($event_before);
        $response = $handler_obj->handle($dto);
        $event_after = new Event('core.command.after_execute',__CLASS__, [
            'command' => $identifier,
        ]);
        $this->event_bus->notify($event_after);

        return $response;
    }

    public function getHandler(string $identifier): string
    {
        $this->assertHandlerExist($identifier);

        return $this->aliases[$identifier] ?? $this->commands[$identifier];
    }

    private function assertHandlerExist(string $commandClass): void
    {
        if (!$this->hasHandler($commandClass)) {
            throw new \RuntimeException("Not handler found for $commandClass command");
        }
    }

    public function hasHandler(string $identifier): bool
    {
        return isset($this->commands[$identifier]) || isset($this->aliases[$identifier]);
    }

    public function getHandlersDefinition(bool $with_aliases = false): array
    {
        $list = $this->commands;
        if ($with_aliases) {
            $list = array_merge($list, $this->aliases);
        }
        return $list;
    }
}
