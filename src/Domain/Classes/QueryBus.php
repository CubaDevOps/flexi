<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Classes;

use CubaDevOps\Flexi\Domain\DTO\NotFoundCliCommand;
use CubaDevOps\Flexi\Domain\Interfaces\BusInterface;
use CubaDevOps\Flexi\Domain\Interfaces\DTOInterface;
use CubaDevOps\Flexi\Domain\Interfaces\EventBusInterface;
use CubaDevOps\Flexi\Domain\Interfaces\HandlerInterface;
use CubaDevOps\Flexi\Domain\Interfaces\MessageInterface;
use CubaDevOps\Flexi\Domain\Utils\ClassFactory;
use CubaDevOps\Flexi\Domain\Utils\GlobFileReader;
use CubaDevOps\Flexi\Domain\Utils\JsonFileReader;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class QueryBus implements BusInterface
{
    use JsonFileReader;
    use GlobFileReader;

    private array $queries = [];
    private array $aliases = [];

    private ContainerInterface $container;

    private EventBusInterface $event_bus;
    private ClassFactory $class_factory;

    public function __construct(
        ContainerInterface $container,
        EventBusInterface $event_bus,
        ClassFactory $class_factory
    ) {
        $this->container = $container;
        $this->event_bus = $event_bus;
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
        $handlers = $this->readJsonFile($file);
        foreach ($handlers['handlers'] as $entry) {
            if ($this->isGlob($entry)) {
                $this->loadGlobFiles($entry);
                continue;
            }
            $this->addEntry($entry);
        }
    }

    private function isGlob(array $handler): bool
    {
        return isset($handler['glob']) && $handler['glob'];
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws \JsonException
     */
    private function loadGlobFiles(array $handler): void
    {
        $files = $this->readGlob($handler['glob']);
        foreach ($files as $file) {
            $this->loadHandlersFromJsonFile($file);
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
        $this->queries[$identifier] = $handler;
    }

    public function registerCliAlias(string $alias, string $handler): void
    {
        $this->aliases[$alias] = $handler;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     */
    public function execute(DTOInterface $dto): MessageInterface
    {
        $identifier = get_class($dto);

        $handler = $this->getHandler($identifier);

        /** @var HandlerInterface $handler_obj */
        $handler_obj = $this->class_factory->build(
            $this->container,
            $handler
        );

        $event_before = new Event("core.query.before_execute.$identifier", __CLASS__, [
            'command' => $identifier,
        ]);
        $this->event_bus->dispatch($event_before);

        $response = $handler_obj->handle($dto);

        $event_after = new Event("core.query.after_execute.$identifier", __CLASS__, [
            'command' => $identifier,
        ]);
        $this->event_bus->dispatch($event_after);

        return $response;
    }

    public function getHandler(string $identifier): string
    {
        $this->assertHandlerExist($identifier);

        return $this->aliases[$identifier] ?? $this->queries[$identifier];
    }

    private function assertHandlerExist(string $commandClass): void
    {
        if (!$this->hasHandler($commandClass)) {
            throw new \RuntimeException("Not handler found for {$commandClass} command");
        }
    }

    public function hasHandler(string $identifier): bool
    {
        return isset($this->queries[$identifier]) || isset($this->aliases[$identifier]);
    }

    public function getHandlersDefinition(bool $with_aliases = true): array
    {
        $list = $this->queries;
        if ($with_aliases) {
            $list = array_merge($list, $this->aliases);
        }

        return $list;
    }

    public function getDtoClassFromAlias(string $id): string
    {
        $handler = $this->aliases[$id];
        $dto = array_search($handler, $this->queries, true);
        return $dto ?: NotFoundCliCommand::class;
    }
}
