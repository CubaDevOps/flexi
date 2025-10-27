<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Home\Infrastructure\Controllers;

use CubaDevOps\Flexi\Contracts\Classes\Traits\FileHandlerTrait;
use CubaDevOps\Flexi\Infrastructure\Bus\QueryBus;
use CubaDevOps\Flexi\Infrastructure\Classes\HttpHandler;
use CubaDevOps\Flexi\Modules\Home\Domain\HomePageDTO;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HomeController extends HttpHandler
{
    use FileHandlerTrait;
    private QueryBus $query_bus;

    public function __construct(QueryBus $query_bus)
    {
        parent::__construct();
        $this->query_bus = $query_bus;
    }

    /**
     * @throws NotFoundExceptionInterface|\ReflectionException|ContainerExceptionInterface
     */
    protected function process(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->createResponse();
        $template_path = $this->normalize('./modules/Home/Infrastructure/UI/Templates/home.html');
        $dto = new HomePageDTO($template_path);

        $response->getBody()->write((string) $this->query_bus->execute($dto));

        return $response;
    }
}
