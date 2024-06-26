<?php

namespace CubaDevOps\Flexi\Test\TestData\TestTools\RouteVisitor;

use CubaDevOps\Flexi\Test\TestData\TestTools\RouteVisitor\MiddlewareVisitorInterface;

class MiddlewareTestVisitor implements MiddlewareVisitorInterface
{
    private array $middlewares = [];

    public function visit(array $middlewares): void
    {
        $this->middlewares = $middlewares;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}