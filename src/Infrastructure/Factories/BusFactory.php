<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Factories;

use Flexi\Contracts\Interfaces\BusInterface;
use Flexi\Contracts\Interfaces\ConfigurationRepositoryInterface;
use Flexi\Contracts\Interfaces\EventBusInterface;
use Flexi\Contracts\Interfaces\ObjectBuilderInterface;
use CubaDevOps\Flexi\Infrastructure\Bus\CommandBus;
use CubaDevOps\Flexi\Infrastructure\Bus\EventBus;
use CubaDevOps\Flexi\Infrastructure\Bus\QueryBus;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\NullLogger;

class BusFactory
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return BusInterface|EventBusInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \JsonException
     * @throws \ReflectionException
     */
    public function getInstance(
        string $type,
        string $file = ''
    ): BusInterface {
        // Use NullLogger if logger service is not available (e.g., Logging module not installed)
        try {
            $logger = $this->container->get('logger');
        } catch (\Exception $e) {
            $logger = new NullLogger();
        }

        $class_factory = $this->container->get(ObjectBuilderInterface::class);
        $configuration = $this->container->get(ConfigurationRepositoryInterface::class);
        $event_bus = new EventBus($this->container, $class_factory, $logger, $configuration);
        switch ($type) {
            case CommandBus::class:
                $bus = new CommandBus($this->container, $event_bus, $class_factory);
                break;
            case QueryBus::class:
                $bus = new QueryBus($this->container, $event_bus, $class_factory);
                break;
            case EventBus::class:
                $bus = $event_bus;
                break;
            default:
                throw new \InvalidArgumentException('Invalid bus type');
        }
        $bus->loadHandlersFromJsonFile($file);

        return $bus;
    }

    /** @return CommandBus */
    public static function createCommandBus(
        ContainerInterface $container,
        string $file = ''
    ): BusInterface {
        $factory = new self($container);

        return $factory->getInstance(CommandBus::class, $file);
    }

    /** @return QueryBus */
    public static function createQueryBus(
        ContainerInterface $container,
        string $file = ''
    ): BusInterface {
        $factory = new self($container);

        return $factory->getInstance(QueryBus::class, $file);
    }

    /** @return EventBus */
    public static function createEventBus(
        ContainerInterface $container,
        string $file = ''
    ): EventBusInterface {
        $factory = new self($container);

        return $factory->getInstance(EventBus::class, $file);
    }
}
