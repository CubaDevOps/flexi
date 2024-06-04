<?php

namespace CubaDevOps\Flexi\Modules\Home\Infrastructure\Controllers;

use CubaDevOps\Flexi\Domain\Classes\QueryBus;
use CubaDevOps\Flexi\Infrastructure\Classes\Configuration;
use CubaDevOps\Flexi\Modules\Home\Domain\HomePageDTO;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;

class HomeController
{
    private QueryBus $query_bus;
    private Configuration $config;

    public function __construct(QueryBus $query_bus, Configuration $config)
    {
        $this->query_bus = $query_bus;
        $this->config = $config;
    }

    /**
     * @throws NotFoundExceptionInterface|ReflectionException|ContainerExceptionInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = (new HttpFactory())->createResponse();
        $template_path = $this->config->get('MODULES_DIR') . '/Home/Infrastructure/UI/Templates/home.html';
        $dto = new HomePageDTO($template_path);

        $response->getBody()->write((string)$this->query_bus->execute($dto));

        return $response;
    }
}
