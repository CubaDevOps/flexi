<?php

namespace CubaDevOps\Flexi\Modules\Home\Infrastructure\Controllers;

use CubaDevOps\Flexi\Domain\Classes\QueryBus;
use CubaDevOps\Flexi\Domain\Utils\FileHandlerTrait;
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
    use FileHandlerTrait;
    private QueryBus $query_bus;

    public function __construct(QueryBus $query_bus)
    {
        $this->query_bus = $query_bus;
    }

    /**
     * @throws NotFoundExceptionInterface|ReflectionException|ContainerExceptionInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = (new HttpFactory())->createResponse();
        $template_path = $this->normalize('./modules/Home/Infrastructure/UI/Templates/home.html');
        $dto = new HomePageDTO($template_path);

        $response->getBody()->write((string)$this->query_bus->execute($dto));

        return $response;
    }
}
