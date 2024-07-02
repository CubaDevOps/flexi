<?php

namespace CubaDevOps\Flexi\Test\TestData\TestDoubles;

use CubaDevOps\Flexi\Domain\Classes\Route;

class RouteMock extends Route
{
    public bool $has_middlewares_spy = false;

    public function hasMiddlewares(): bool
    {
        $this->has_middlewares_spy = parent::hasMiddlewares();
        return $this->has_middlewares_spy;
    }
}