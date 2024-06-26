<?php

namespace CubaDevOps\Flexi\Test\TestData\TestTools\RouteVisitor;

interface MiddlewareVisitorInterface
{
    public function visit(array $middlewares): void;
}