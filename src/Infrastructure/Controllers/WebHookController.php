<?php

namespace CubaDevOps\Flexi\Infrastructure\Controllers;

use CubaDevOps\Flexi\Domain\Classes\EventBus;
use CubaDevOps\Flexi\Infrastructure\Classes\HttpHandler;
use CubaDevOps\Flexi\Infrastructure\Commands\DispatchEventCommand;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;

class WebHookController extends HttpHandler
{
    private DispatchEventCommand $command;

    public function __construct(EventBus $eventBus)
    {
        parent::__construct();
        $this->command = new DispatchEventCommand($eventBus);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->queue->isEmpty()) {
            return $this->getNextMiddleware()->process($request, $this);
        }

        try {
            $data = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            $data['fired_by'] = $request->getAttribute('payload')['fired_by'] ?? null;

            $this->command->execute($data);
        } catch (InvalidArgumentException|NotFoundExceptionInterface|ContainerExceptionInterface|ReflectionException $e) {
            return $this->createResponse(400, $e->getMessage());
        }

        return $this->createResponse();
    }
}