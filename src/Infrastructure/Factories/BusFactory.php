<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Factories;

use CubaDevOps\Flexi\Contracts\BusContract;
use CubaDevOps\Flexi\Contracts\ConfigurationRepositoryContract;
use CubaDevOps\Flexi\Contracts\EventBusContract;
use CubaDevOps\Flexi\Contracts\ObjectBuilderContract;
use CubaDevOps\Flexi\Infrastructure\Bus\CommandBus;
use CubaDevOps\Flexi\Infrastructure\Bus\EventBus;
use CubaDevOps\Flexi\Infrastructure\Bus\QueryBus;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class BusFactory
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return BusContract|EventBusContract
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \JsonException
     * @throws \ReflectionException
     */
    public function getInstance(
        string $type,
        string $file = ''
    ): BusContract {
        $logger = $this->container->get('logger');
        $class_factory = $this->container->get(ObjectBuilderContract::class);
        $configuration = $this->container->get(ConfigurationRepositoryContract::class);
        $bus = new EventBus($this->container, $class_factory, $logger, $configuration);
        switch ($type) {
            case CommandBus::class:
                $bus = new CommandBus($this->container, $bus, $class_factory);
                break;
            case QueryBus::class:
                $bus = new QueryBus($this->container, $bus, $class_factory);
                break;
            case EventBus::class:
                // event bus is already assigned
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
    ): BusContract {
        $factory = new self($container);

        return $factory->getInstance(CommandBus::class, $file);
    }

    /** @return QueryBus */
    public static function createQueryBus(
        ContainerInterface $container,
        string $file = ''
    ): BusContract {
        $factory = new self($container);

        return $factory->getInstance(QueryBus::class, $file);
    }

    /** @return EventBus */
    public static function createEventBus(
        ContainerInterface $container,
        string $file = ''
    ): EventBusContract {
        $factory = new self($container);

        return $factory->getInstance(EventBus::class, $file);
    }
}
