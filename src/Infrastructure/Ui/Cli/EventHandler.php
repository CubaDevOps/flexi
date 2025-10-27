<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Ui\Cli;

use CubaDevOps\Flexi\Contracts\Interfaces\EventBusInterface;
use CubaDevOps\Flexi\Domain\Events\Event;

class EventHandler
{
    private EventBusInterface $event_bus;

    public function __construct(EventBusInterface $event_bus)
    {
        $this->event_bus = $event_bus;
    }

    /**
     * @throws \JsonException
     */
    public function handle(CliInput $input): string
    {
        if ($input->showHelp()) {
            return 'Usage: --event|-e trigger|listeners name=event_name fired_by=cli data={"key": "value"}';
        }

        if ('trigger' === $input->getCommandName()) {
            $data = json_decode($input->getArgument('data', '{}'), true, 512, JSON_THROW_ON_ERROR);
            $event = new Event($input->getArgument('name'), $input->getArgument('fired_by', 'cli'), $data);
            $this->event_bus->dispatch($event);

            return 'Event "'.$input->getArgument('name').'" triggered: '.$event->serialize();
        }

        if ('listeners' === $input->getCommandName()) {
            $listeners = $this->event_bus->getListeners($input->getArgument('name'));

            return implode("\n", $listeners);
        }

        return $input->getCommandName().' command related to events not found';
    }
}
