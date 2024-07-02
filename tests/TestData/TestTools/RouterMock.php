<?php

namespace CubaDevOps\Flexi\Test\TestData\TestTools;

use CubaDevOps\Flexi\Domain\Classes\Route;
use CubaDevOps\Flexi\Domain\Classes\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RouterMock extends Router
{
    public bool $redirect_to_not_found_spy = false;
    public int $route_counter = 0;

    public function redirectToNotFound(
        ServerRequestInterface $request,
        string $previous_route
    ): ResponseInterface {
        $response = $this->response_factory->createResponse(404);
        $this->redirect_to_not_found_spy = true;
        return $response;
    }

    public function addRoute(Route $route): Router
    {
        parent::addRoute($route);
        $this->route_counter++;
        return $this;
    }
}