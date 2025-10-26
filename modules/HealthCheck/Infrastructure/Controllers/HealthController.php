<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\HealthCheck\Infrastructure\Controllers;

use CubaDevOps\Flexi\Infrastructure\Bus\QueryBus;
use CubaDevOps\Flexi\Infrastructure\Classes\HttpHandler;
use CubaDevOps\Flexi\Modules\HealthCheck\Application\Queries\GetVersionQuery;
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
    protected function process(ServerRequestInterface $request): ResponseInterface
    {
        $version = $this->query_bus->execute(new GetVersionQuery());
        $response = $this->createResponse();
        $response->getBody()->write('Version: '.$version);

        return $response;
    }
}
