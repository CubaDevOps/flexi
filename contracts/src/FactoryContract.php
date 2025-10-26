<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts;

interface FactoryContract
{
    public static function getInstance(...$args): object;
}