<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Home\Infrastructure\Controllers;

use CubaDevOps\Flexi\Contracts\Classes\HttpHandler;
use CubaDevOps\Flexi\Contracts\Classes\Traits\FileHandlerTrait;
use CubaDevOps\Flexi\Infrastructure\Bus\QueryBus;
use CubaDevOps\Flexi\Modules\Home\Domain\HomePageDTO;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HomeController extends HttpHandler
{
    use FileHandlerTrait;
    private QueryBus $query_bus;

    public function __construct(ResponseFactoryInterface $response_factory, QueryBus $query_bus)
    {
        parent::__construct($response_factory);
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

        $response->getBody()->write($this->query_bus->execute($dto)->__toString());

        return $response;
    }
}
