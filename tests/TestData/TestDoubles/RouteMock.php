<?php

declare(strict_types=1);

namespace Flexi\Test\TestData\TestDoubles;

use Flexi\Infrastructure\Http\Route;

class RouteMock extends Route
{
    public bool $has_middlewares_spy = false;

    public function hasMiddlewares(): bool
    {
        $this->has_middlewares_spy = parent::hasMiddlewares();

        return $this->has_middlewares_spy;
    }
}
