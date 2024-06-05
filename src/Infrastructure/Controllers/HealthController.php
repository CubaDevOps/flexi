<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Controllers;

use CubaDevOps\Flexi\Domain\Classes\QueryBus;
use CubaDevOps\Flexi\Domain\DTO\EmptyVersionDTO;
use CubaDevOps\Flexi\Infrastructure\Classes\HttpHandler;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HealthController extends HttpHandler
{
    private QueryBus $query_bus;

    public function __construct(QueryBus $query_bus)
    {
        $this->query_bus = $query_bus;
        parent::__construct();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $version = $this->query_bus->execute(new EmptyVersionDTO());
        $response = $this->createResponse();
        $response->getBody()->write('Version: '.$version);

        return $response;
    }
}
