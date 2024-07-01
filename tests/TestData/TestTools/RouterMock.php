<?php

namespace CubaDevOps\Flexi\Test\TestData\TestTools;

use CubaDevOps\Flexi\Domain\Classes\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RouterMock extends Router
{
    public bool $redirect_to_not_found_spy = false;

    public function redirectToNotFound(
        ServerRequestInterface $request,
        string $previous_route
    ): ResponseInterface {
        $response = parent::redirectToNotFound($request, $previous_route);
        $this->redirect_to_not_found_spy = true;
        return $response;
    }
}